<h1>Pré-natal — gestantes em acompanhamento</h1>
<?php
$lista = db_lista("SELECT g.*, p.nome_paciente, p.telefone_paciente,
                          (SELECT MIN(a.dt_agendamento) FROM agendamento a WHERE a.paciente_id = g.paciente_id AND a.tipo_atendimento = 'Pré-natal'
                             AND a.dt_agendamento >= ? AND a.status_agendamento IN ('Solicitado','Confirmado')) AS proxima_consulta
                   FROM gestacao g JOIN paciente p ON p.id_paciente = g.paciente_id
                   WHERE g.status_gestacao = 'Em acompanhamento' ORDER BY g.dpp", 's', hoje());
if ($lista) {
    print "<p>Encontrou <b>" . count($lista) . "</b> resultado(s)</p>";
    print "<table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>Gestante</th><th>Telefone</th><th>DUM</th><th>Idade gestacional</th><th>DPP</th><th>Próxima consulta</th><th>Ações</th></tr>";
    foreach ($lista as $g) {
        print "<tr><td>" . h($g->nome_paciente) . "</td><td>" . h($g->telefone_paciente) . "</td><td>" . data_br($g->dum) . "</td>"
            . "<td>" . semanas_gestacao($g->dum) . " semanas</td><td>" . data_br($g->dpp) . "</td>"
            . "<td>" . ($g->proxima_consulta ? data_br($g->proxima_consulta) : badge('Sem consulta agendada', 'warning text-dark')) . "</td>"
            . "<td class='text-nowrap'><button onclick=\"location.href='?page=editar-gestacao&id={$g->id_gestacao}';\" class='btn btn-success'>Ficha</button> "
            . "<button onclick=\"location.href='?page=prontuario&id={$g->paciente_id}';\" class='btn btn-primary'>Prontuário</button></td></tr>";
    }
    print "</table>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
<p class="text-muted">Para iniciar um acompanhamento, abra o prontuário da paciente e clique em "Iniciar acompanhamento de pré-natal".</p>
