<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 0 AND profissional_id = ?", 'ii', $_POST['atendimento_id'] ?? 0, id_funcionario());
if (!$at) {
    mensagem('Atendimento não encontrado ou já finalizado.', 'listar-atendimentos');
    return;
}
if (empty(usuario()['registro'])) {
    voltar('Apenas profissionais com registro ativo no conselho podem emitir receitas.');
    return;
}

$itens = [];
$avisos = [];
foreach ($_POST['medicamento_id'] ?? [] as $i => $mid) {
    if ($mid === '') {
        continue;
    }
    $med = db_um("SELECT * FROM medicamento WHERE id_medicamento = ? AND tipo_item = 'Medicamento'", 'i', $mid);
    $posologia = trim($_POST['posologia'][$i] ?? '');
    $qtd = (int)($_POST['quantidade'][$i] ?? 0);
    $duracao = trim($_POST['duracao'][$i] ?? '');
    if (!$med || $posologia === '' || $qtd < 1 || $duracao === '') {
        voltar('Preencha posologia, quantidade e duração de todos os medicamentos escolhidos.');
        return;
    }
    $saldo = saldo_valido($med->id_medicamento);
    if ($med->controlado) {
        // RN-RF11: controlado só com estoque ativo e com confirmação do limite de dose
        if ($saldo < $qtd) {
            voltar("{$med->nm_medicamento} é controlado e só pode ser prescrito com estoque ativo suficiente (saldo atual: $saldo).");
            return;
        }
        if (!isset($_POST['confirma_dose'])) {
            voltar("Confirme que a dose de {$med->nm_medicamento} respeita o limite recomendado ({$med->dose_maxima}).");
            return;
        }
    } elseif ($saldo < $qtd) {
        $avisos[] = "{$med->nm_medicamento}: estoque atual $saldo (o paciente pode não receber tudo na farmácia da UBS)";
    }
    $itens[] = [$med->id_medicamento, $posologia, $qtd, $duracao];
}
if (!$itens) {
    voltar('Inclua pelo menos um medicamento.');
    return;
}

try {
    $conn->begin_transaction();
    $id = db_inserir("INSERT INTO prescricao (atendimento_id, paciente_id, profissional_id, dt_emissao, observacao_prescricao) VALUES (?, ?, ?, ?, ?)",
        'iiiss', $at->id_atendimento, $at->paciente_id, id_funcionario(), agora(), trim($_POST['observacao'] ?? ''));
    foreach ($itens as [$mid, $posologia, $qtd, $duracao]) {
        db("INSERT INTO prescricao_item (prescricao_id, medicamento_id, posologia, quantidade, duracao) VALUES (?, ?, ?, ?, ?)",
            'iisis', $id, $mid, $posologia, $qtd, $duracao);
    }
    $conn->commit();
    auditoria('Criar', 'Prescrição', $id, $at->paciente_id);
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    voltar('Erro ao emitir a receita.');
    return;
}
$extra = $avisos ? ' Atenção: ' . implode('; ', $avisos) . '.' : '';
mensagem('Receita emitida e enviada para a farmácia.' . $extra, 'editar-atendimento&id=' . $at->id_atendimento);
?>
