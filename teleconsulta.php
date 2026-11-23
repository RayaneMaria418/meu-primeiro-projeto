<h1>Teleconsulta / atendimento remoto</h1>
<?php
$lista = db_lista("SELECT a.*, p.nome_paciente,
                          (SELECT x.id_atendimento FROM atendimento x WHERE x.agendamento_id = a.id_agendamento) AS atendimento_id
                   FROM agendamento a JOIN paciente p ON p.id_paciente = a.paciente_id
                   WHERE a.tipo_atendimento = 'Teleconsulta' AND a.funcionario_id = ? AND a.dt_agendamento >= ?
                     AND a.status_agendamento IN ('Confirmado','Em Atendimento')
                   ORDER BY a.dt_agendamento, a.hr_agendamento", 'is', id_funcionario(), hoje());
print "<h4>Minhas teleconsultas agendadas</h4>";
if ($lista) {
    print "<table class='table table-bordered table-striped table-hover'><tr><th>Data</th><th>Hora</th><th>Paciente</th><th>Sala de vídeo</th><th>Status</th><th>Ações</th></tr>";
    foreach ($lista as $a) {
        $botao = $a->atendimento_id
            ? "<button onclick=\"location.href='?page=editar-atendimento&id={$a->atendimento_id}';\" class='btn btn-success'>Continuar</button>"
            : "<button onclick=\"location.href='?page=novo-atendimento&ag_id={$a->id_agendamento}';\" class='btn btn-primary'>Iniciar</button>";
        print "<tr><td>" . data_br($a->dt_agendamento) . "</td><td>" . hora_br($a->hr_agendamento) . "</td><td>" . h($a->nome_paciente) . "</td>"
            . "<td><a href='" . h($a->link_teleconsulta) . "' target='_blank'>abrir sala</a></td><td>" . status_badge($a->status_agendamento) . "</td><td>$botao</td></tr>";
    }
    print "</table>";
} else {
    print "<p class='alert alert-secondary'>Nenhuma teleconsulta agendada.</p>";
}
?>
<h4 class="mt-4">Registrar atendimento remoto sem agendamento</h4>
<p>Use para orientações passadas por telefone ou para uma teleconsulta de retorno.</p>
<form action="index.php" method="GET" class="row g-2">
	<input type="hidden" name="page" value="novo-atendimento">
	<div class="col-md-6"><?php print select_pacientes('paciente_id', $_GET['paciente_id'] ?? null); ?></div>
	<div class="col-md-3">
		<select name="tipo" class="form-control">
			<option>Contato telefônico</option>
			<option>Teleconsulta</option>
		</select>
	</div>
	<div class="col-md-3"><button type="submit" class="btn btn-primary">Iniciar</button></div>
</form>
