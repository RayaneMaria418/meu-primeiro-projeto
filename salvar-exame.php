<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 0 AND profissional_id = ?", 'ii', $_POST['atendimento_id'] ?? 0, id_funcionario());
$ids = array_map('intval', $_POST['exames'] ?? []);
$justificativa = trim($_POST['justificativa'] ?? '');
if (!$at) {
    mensagem('Atendimento não encontrado ou já finalizado.', 'listar-atendimentos');
    return;
}
if (!$ids) {
    voltar('Selecione pelo menos um exame.');
    return;
}
$exames = [];
foreach ($ids as $id) {
    $e = db_um("SELECT * FROM exame WHERE id_exame = ?", 'i', $id);
    if ($e) {
        $exames[] = $e;
    }
}
// RN-RF12: alto custo exige justificativa clínica
$alto = array_filter($exames, fn($e) => $e->alto_custo);
if ($alto && $justificativa === '') {
    voltar('Exames de alto custo (' . implode(', ', array_map(fn($e) => $e->nm_exame, $alto)) . ') exigem justificativa clínica.');
    return;
}
$dh = agora();
$primeiro = null;
foreach ($exames as $e) {
    $sid = db_inserir("INSERT INTO solicitacao_exame (atendimento_id, paciente_id, profissional_id, exame_id, justificativa_exame, dt_solicitacao) VALUES (?, ?, ?, ?, ?, ?)",
        'iiiiss', $at->id_atendimento, $at->paciente_id, id_funcionario(), $e->id_exame, $justificativa ?: null, $dh);
    $primeiro = $primeiro ?? $sid;
}
auditoria('Criar', 'Exame', $primeiro, $at->paciente_id, count($exames) . ' exame(s)');
mensagem('Guia de exames gerada. Imprima e entregue ao paciente.', 'documento&tipo=exame&id=' . $primeiro);
?>
