<h1>Editar agendamento</h1>
<?php
$ag = db_um("SELECT a.*, p.nome_paciente, f.nome_funcionario FROM agendamento a
             JOIN paciente p ON p.id_paciente = a.paciente_id
             LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
             WHERE a.id_agendamento = ?", 'i', $_REQUEST['id'] ?? 0);
if (!$ag || (tem_perfil(PAC) && (int)$ag->paciente_id !== (int)id_paciente_logado())) {
    print "<div class='alert alert-danger'>Agendamento não encontrado.</div>";
    return;
}
$editavel = in_array($ag->status_agendamento, ['Solicitado', 'Confirmado'], true);
$data = $_GET['data'] ?? '';
?>
<table class="table table-bordered">
	<tr><th>Paciente</th><td><?php print h($ag->nome_paciente); ?></td><th>Profissional</th><td><?php print h($ag->nome_funcionario); ?></td></tr>
	<tr><th>Data / Hora</th><td><?php print data_br($ag->dt_agendamento) . ' ' . hora_br($ag->hr_agendamento); ?></td><th>Status</th><td><?php print status_badge($ag->status_agendamento); ?></td></tr>
	<tr><th>Tipo</th><td><?php print h($ag->tipo_atendimento); ?></td><th>Origem</th><td><?php print h($ag->origem_agendamento); ?></td></tr>
</table>
<?php if (!tem_perfil(PAC)) { ?>
<p class="text-muted">Regra RN-RF07: cancelamentos e remanejamentos feitos pelo posto exigem no mínimo 24 horas de antecedência; o paciente é notificado.</p>
<?php } ?>

<?php if ($editavel) { ?>
<div class="row">
	<div class="col-md-6">
		<h4>Remanejar (reagendar)</h4>
		<form action="index.php" method="GET" class="row g-2 mb-2">
			<input type="hidden" name="page" value="editar-agendamento">
			<input type="hidden" name="id" value="<?php print $ag->id_agendamento; ?>">
			<div class="col-8"><input type="date" name="data" value="<?php print h($data); ?>" min="<?php print hoje(); ?>" class="form-control" required></div>
			<div class="col-4"><button type="submit" class="btn btn-outline-primary">Ver horários</button></div>
		</form>
		<?php
		if ($data) {
			$horarios = horarios_livres($ag->funcionario_id, $data, $ag->id_agendamento);
			if ($horarios) { ?>
				<form action="?page=salvar-agendamento" method="POST">
					<input type="hidden" name="acao" value="reagendar">
					<input type="hidden" name="id" value="<?php print $ag->id_agendamento; ?>">
					<input type="hidden" name="data_agendamento" value="<?php print h($data); ?>">
					<div class="mb-3">
						<label>Novo horário em <?php print data_br($data); ?></label>
						<select name="hora_agendamento" class="form-control"><?php foreach ($horarios as $hr) print "<option>$hr</option>"; ?></select>
					</div>
					<div class="mb-3">
						<label>Justificativa</label>
						<input type="text" name="justificativa" class="form-control" required>
					</div>
					<button type="submit" class="btn btn-primary">Salvar Edição</button>
				</form>
			<?php } else {
				print "<div class='alert alert-warning'>Sem horários livres nesta data (ou profissional ausente).</div>";
			}
		}
		?>
	</div>
	<div class="col-md-6">
		<h4>Cancelar</h4>
		<form action="?page=salvar-agendamento" method="POST" onsubmit="return confirm('Confirmar o cancelamento?');">
			<input type="hidden" name="acao" value="cancelar">
			<input type="hidden" name="id" value="<?php print $ag->id_agendamento; ?>">
			<div class="mb-3">
				<label>Motivo do cancelamento</label>
				<input type="text" name="justificativa" class="form-control" required>
			</div>
			<button type="submit" class="btn btn-danger">Cancelar agendamento</button>
		</form>
	</div>
</div>
<?php } ?>

<h4 class="mt-4">Histórico de alterações</h4>
<?php
$hist = db_lista("SELECT h.*, COALESCE(f.nome_funcionario, p.nome_paciente) AS quem FROM agendamento_historico h
                  LEFT JOIN usuario u ON u.id_usuario = h.usuario_id
                  LEFT JOIN funcionario f ON f.id_funcionario = u.funcionario_id
                  LEFT JOIN paciente p ON p.id_paciente = u.paciente_id
                  WHERE h.agendamento_id = ? ORDER BY h.dt_registro, h.id_historico", 'i', $ag->id_agendamento);
print "<table class='table table-bordered table-striped table-sm'><tr><th>Quando</th><th>De</th><th>Para</th><th>Data/hora</th><th>Justificativa</th><th>Usuário</th></tr>";
foreach ($hist as $hh) {
    $mudou_data = $hh->data_hora_anterior ? datahora_br($hh->data_hora_anterior) . ' → ' . datahora_br($hh->data_hora_nova) : '';
    print "<tr><td>" . datahora_br($hh->dt_registro) . "</td><td>" . h($hh->status_anterior) . "</td><td>" . h($hh->status_novo) . "</td><td>$mudou_data</td><td>" . h($hh->justificativa) . "</td><td>" . h($hh->quem) . "</td></tr>";
}
print "</table>";
?>
