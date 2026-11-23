<h1>Triagem e acolhimento</h1>
<?php
$ag = db_um("SELECT a.*, f.nome_funcionario FROM agendamento a LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
             WHERE a.id_agendamento = ?", 'i', $_GET['ag_id'] ?? 0);
if (!$ag || $ag->status_agendamento !== 'Em Triagem') {
    print "<div class='alert alert-danger'>Agendamento não está aguardando triagem (é preciso fazer o check-in na recepção).</div>";
    return;
}
if (db_valor("SELECT COUNT(*) FROM triagem WHERE agendamento_id = ?", 'i', $ag->id_agendamento) > 0) {
    print "<div class='alert alert-warning'>A triagem deste paciente já foi registrada.</div>";
    return;
}
print cabecalho_paciente($ag->paciente_id);
print alerta_vacinas($ag->paciente_id);
?>
<p><b>Senha:</b> <?php print h($ag->senha_agendamento); ?> — <b>Tipo:</b> <?php print h($ag->tipo_atendimento); ?>
	<?php print $ag->observacao_agendamento ? ' — <b>Motivo informado:</b> ' . h($ag->observacao_agendamento) : ''; ?></p>
<form action="?page=salvar-triagem" method="POST">
	<input type="hidden" name="agendamento_id" value="<?php print $ag->id_agendamento; ?>">
	<div class="row">
		<div class="col-md-3 mb-3">
			<label>Pressão arterial (mmHg)</label>
			<input type="text" name="pressao" class="form-control" placeholder="120/80" pattern="\d{2,3}/\d{2,3}" required>
		</div>
		<div class="col-md-3 mb-3">
			<label>Glicemia (mg/dL)</label>
			<input type="number" name="glicemia" class="form-control" min="20" max="800">
		</div>
		<div class="col-md-2 mb-3">
			<label>Temperatura (°C)</label>
			<input type="number" name="temperatura" class="form-control" step="0.1" min="30" max="45" required>
		</div>
		<div class="col-md-2 mb-3">
			<label>Peso (kg)</label>
			<input type="number" name="peso" class="form-control" step="0.01" min="0.5" max="400">
		</div>
		<div class="col-md-2 mb-3">
			<label>Altura (m)</label>
			<input type="number" name="altura" class="form-control" step="0.01" min="0.2" max="2.5">
		</div>
	</div>
	<div class="mb-3">
		<label>Queixas do paciente</label>
		<textarea name="queixa" class="form-control" rows="3" required><?php print h($ag->observacao_agendamento); ?></textarea>
	</div>
	<div class="mb-3">
		<label>Classificação de risco (Protocolo de Manchester)</label>
		<select name="risco" class="form-control" required>
			<option value="">-- Selecione --</option>
			<option value="Vermelho">Vermelho — Emergência (atendimento imediato)</option>
			<option value="Laranja">Laranja — Muito urgente (até 10 min)</option>
			<option value="Amarelo">Amarelo — Urgente (até 60 min)</option>
			<option value="Verde">Verde — Pouco urgente (até 120 min)</option>
			<option value="Azul">Azul — Não urgente (até 240 min)</option>
		</select>
	</div>
	<div class="mb-3">
		<label>Encaminhar para</label>
		<?php print select_profissionais('funcionario_id', $ag->funcionario_id, CLINICOS, false); ?>
		<small class="text-muted">Deixe em branco para o primeiro médico disponível.</small>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Concluir triagem e encaminhar</button>
	</div>
</form>
