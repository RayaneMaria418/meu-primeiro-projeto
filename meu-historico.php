<h1>Meu histórico de atendimentos</h1>
<?php
$pid = id_paciente_logado();
auditoria('Visualizar', 'Histórico (paciente)', $pid, $pid);
$lista = db_lista("SELECT at.*, f.nome_funcionario, f.especialidade_funcionario FROM atendimento at
                   JOIN funcionario f ON f.id_funcionario = at.profissional_id
                   WHERE at.paciente_id = ? AND at.finalizado = 1 ORDER BY at.dt_atendimento DESC", 'i', $pid);
if (!$lista) {
    print "<p class='alert alert-secondary'>Você ainda não tem atendimentos finalizados.</p>";
    return;
}
print "<table class='table table-bordered table-striped table-hover'><tr><th>Data</th><th>Profissional</th><th>Tipo</th><th>Orientações</th><th>Documentos</th></tr>";
foreach ($lista as $at) {
    $docs = [];
    foreach (db_lista("SELECT id_prescricao, status_prescricao FROM prescricao WHERE atendimento_id = ?", 'i', $at->id_atendimento) as $p) {
        $docs[] = "<a href='?page=documento&tipo=receita&id={$p->id_prescricao}'>Receita</a> " . status_badge($p->status_prescricao);
    }
    foreach (db_lista("SELECT MIN(id_solicitacao) AS id FROM solicitacao_exame WHERE atendimento_id = ? GROUP BY dt_solicitacao", 'i', $at->id_atendimento) as $e) {
        $docs[] = "<a href='?page=documento&tipo=exame&id={$e->id}'>Guia de exames</a>";
    }
    foreach (db_lista("SELECT id_atestado, tipo_atestado FROM atestado WHERE atendimento_id = ?", 'i', $at->id_atendimento) as $a) {
        $docs[] = "<a href='?page=documento&tipo=atestado&id={$a->id_atestado}'>" . h($a->tipo_atestado) . "</a>";
    }
    print "<tr><td>" . datahora_br($at->dt_atendimento) . "</td><td>" . h($at->nome_funcionario . ($at->especialidade_funcionario ? " ({$at->especialidade_funcionario})" : '')) . "</td>"
        . "<td>" . h($at->tipo_registro) . "</td><td>" . h($at->conduta_atendimento) . "</td><td>" . implode('<br>', $docs) . "</td></tr>";
}
print "</table>";
?>
