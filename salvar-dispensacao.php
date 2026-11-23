<?php
$pr = db_um("SELECT * FROM prescricao WHERE id_prescricao = ? AND status_prescricao = 'Pendente'", 'i', $_POST['prescricao_id'] ?? 0);
$volta = 'dispensacao&documento=' . urlencode(so_digitos($_POST['documento'] ?? ''));
if (!$pr) {
    mensagem('Receita não encontrada ou já atendida.', 'dispensacao');
    return;
}
$itens = db_lista("SELECT i.*, m.nm_medicamento, m.controlado FROM prescricao_item i JOIN medicamento m ON m.id_medicamento = i.medicamento_id
                   WHERE i.prescricao_id = ?", 'i', $pr->id_prescricao);
$controlado = (bool)array_filter($itens, fn($i) => $i->controlado);
$dias = (time() - strtotime($pr->dt_emissao)) / 86400;

// RN-RF18: controlado exige receita física válida (até 30 dias)
if ($controlado && (!isset($_POST['receita_fisica']) || $dias > 30)) {
    voltar('Medicamento controlado: confirme a apresentação da receita física dentro do prazo de 30 dias.');
    return;
}

$entregues = [];
$faltas = [];
try {
    $conn->begin_transaction();
    foreach ($itens as $i) {
        $qtd = max(0, min((int)($_POST['entregar'][$i->id_item] ?? 0), (int)$i->quantidade));
        if ($qtd > 0) {
            baixar_estoque($i->medicamento_id, $qtd, 'Saída por dispensação', 'Receita #' . $pr->id_prescricao);
            $entregues[] = "{$i->nm_medicamento}: $qtd";
        }
        if ($qtd < $i->quantidade) {
            $faltas[] = "{$i->nm_medicamento} (" . ($i->quantidade - $qtd) . " em falta)";
        }
    }
    if (!$entregues) {
        throw new RuntimeException('Nenhum item disponível para entrega.');
    }
    db("INSERT INTO dispensacao (prescricao_id, farmaceutico_id, dt_dispensacao, receita_fisica_validada) VALUES (?, ?, ?, ?)", 'iisi',
        $pr->id_prescricao, id_funcionario(), agora(), $controlado ? 1 : 0);
    db("UPDATE prescricao SET status_prescricao = 'Atendida' WHERE id_prescricao = ?", 'i', $pr->id_prescricao);

    // Fim do fluxo: o agendamento passa de "Aguardando Medicamento" para "Finalizado"
    $ag_id = db_valor("SELECT agendamento_id FROM atendimento WHERE id_atendimento = ?", 'i', $pr->atendimento_id);
    if ($ag_id && !db_valor("SELECT COUNT(*) FROM prescricao WHERE atendimento_id = ? AND status_prescricao = 'Pendente'", 'i', $pr->atendimento_id)) {
        if (db_valor("SELECT status_agendamento FROM agendamento WHERE id_agendamento = ?", 'i', $ag_id) === 'Aguardando Medicamento') {
            mudar_status($ag_id, 'Finalizado', 'Medicamentos dispensados pela farmácia');
        }
    }
    if ($faltas) {
        notificar($pr->paciente_id, 'Alguns medicamentos da sua receita estão em falta na farmácia da UBS: ' . implode(', ', $faltas) . '.');
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    voltar('Não foi possível dispensar: ' . $e->getMessage());
    return;
}
auditoria('Dispensar', 'Prescrição', $pr->id_prescricao, $pr->paciente_id, implode('; ', $entregues));
mensagem('Entrega registrada (' . implode('; ', $entregues) . ').' . ($faltas ? ' Itens em falta: ' . implode(', ', $faltas) . '. O paciente foi notificado.' : ''), $volta);
?>
