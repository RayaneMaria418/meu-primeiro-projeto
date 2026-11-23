<h1>Encaminhamentos (Regulação)</h1>
<?php
$sql = "SELECT e.*, p.nome_paciente, f.nome_funcionario FROM encaminhamento e
        JOIN paciente p ON p.id_paciente = e.paciente_id JOIN funcionario f ON f.id_funcionario = e.profissional_id";
if (tem_perfil(MED)) {
    $lista = db_lista($sql . " WHERE e.profissional_id = ? ORDER BY e.dt_solicitacao DESC", 'i', id_funcionario());
} elseif (tem_perfil(REC)) {
    $lista = db_lista($sql . " WHERE e.status_encaminhamento = 'Aprovado' ORDER BY e.dt_solicitacao DESC");
    print "<p class='text-muted'>Encaminhamentos aprovados aguardando agendamento da consulta especializada.</p>";
} else {
    $lista = db_lista($sql . " ORDER BY FIELD(e.status_encaminhamento,'Aguardando regulação','Aprovado','Negado','Utilizado'),
                               FIELD(e.prioridade,'Urgente','Prioritária','Eletiva'), e.dt_solicitacao");
}
if ($lista) {
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>Código</th><th>Data</th><th>Paciente</th><th>Especialidade</th><th>Prioridade</th><th>Complexidade</th><th>Solicitante</th><th>Resumo</th><th>Status</th><th>Ações</th></tr>";
    foreach ($lista as $e) {
        print "<tr><td>" . h($e->codigo_regulacao) . "</td><td>" . data_br($e->dt_solicitacao) . "</td><td>" . h($e->nome_paciente) . "</td><td>" . h($e->especialidade) . "</td>"
            . "<td>" . h($e->prioridade) . "</td><td>" . h($e->complexidade) . "</td><td>" . h($e->nome_funcionario) . "</td><td>" . h($e->resumo_clinico) . "</td>"
            . "<td>" . status_badge($e->status_encaminhamento) . "</td><td class='text-nowrap'>";
        if (tem_perfil(ADM) && $e->status_encaminhamento === 'Aguardando regulação') {
            print "<button onclick=\"location.href='?page=salvar-encaminhamento&acao=aprovar&id={$e->id_encaminhamento}';\" class='btn btn-success'>Aprovar</button> ";
            print "<button onclick=\"if(confirm('Negar o encaminhamento?')){ location.href='?page=salvar-encaminhamento&acao=negar&id={$e->id_encaminhamento}'; }\" class='btn btn-danger'>Negar</button>";
        }
        if (tem_perfil(REC, ADM) && $e->status_encaminhamento === 'Aprovado') {
            print "<button onclick=\"location.href='?page=novo-agendamento&paciente_id={$e->paciente_id}&tipo=Consulta+especializada';\" class='btn btn-primary'>Agendar</button>";
        }
        print "</td></tr>";
    }
    print "</table></div>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
