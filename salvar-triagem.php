<?php
$ag = db_um("SELECT * FROM agendamento WHERE id_agendamento = ?", 'i', $_POST['agendamento_id'] ?? 0);
$pressao = trim($_POST['pressao'] ?? '');
$risco = $_POST['risco'] ?? '';
$num = fn($campo) => ($_POST[$campo] ?? '') === '' ? null : str_replace(',', '.', $_POST[$campo]);

if (!$ag || $ag->status_agendamento !== 'Em Triagem') {
    mensagem('Este paciente não está aguardando triagem.', 'fila');
    return;
}
if (!preg_match('/^\d{2,3}\/\d{2,3}$/', $pressao) || !isset(ORDEM_RISCO[$risco]) || trim($_POST['queixa'] ?? '') === '' || $num('temperatura') === null) {
    voltar('Preencha a pressão (ex.: 120/80), a temperatura, as queixas e a classificação de risco.');
    return;
}
$funcionario_id = ($_POST['funcionario_id'] ?? '') !== '' ? (int)$_POST['funcionario_id'] : $ag->funcionario_id;
// RN-RF08: Vermelho e Laranja vão para o atendimento médico imediato (qualquer médico disponível)
if (in_array($risco, ['Vermelho', 'Laranja'], true) && $funcionario_id) {
    $cargo = db_valor("SELECT cargo_funcionario FROM funcionario WHERE id_funcionario = ?", 'i', $funcionario_id);
    if ($cargo !== MED) {
        $funcionario_id = null;
    }
}

try {
    $conn->begin_transaction();
    $id = db_inserir("INSERT INTO triagem (agendamento_id, enfermeiro_id, pressao_arterial, glicemia, temperatura, peso, altura, queixa_triagem, classificacao_risco, dt_triagem)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 'iisissssss',
        $ag->id_agendamento, id_funcionario(), $pressao, $num('glicemia'), $num('temperatura'), $num('peso'), $num('altura'),
        trim($_POST['queixa']), $risco, agora());
    db("UPDATE agendamento SET funcionario_id = ? WHERE id_agendamento = ?", 'ii', $funcionario_id, $ag->id_agendamento);
    mudar_status($ag->id_agendamento, 'Em Atendimento', 'Triagem concluída e encaminhada ao atendimento');
    $conn->commit();
    auditoria('Criar', 'Triagem', $id, $ag->paciente_id, "Risco $risco");
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    voltar('Erro ao salvar a triagem.');
    return;
}

if (in_array($risco, ['Vermelho', 'Laranja'], true)) {
    mensagem("ATENÇÃO: paciente classificado como $risco. Ele foi colocado no topo da fila médica e a equipe está sendo alertada.", 'fila');
} else {
    mensagem('Triagem registrada. Paciente encaminhado para a fila do consultório.', 'fila');
}
?>
