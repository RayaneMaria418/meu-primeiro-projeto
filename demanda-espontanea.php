<h1>Demanda espontânea</h1>
<p>Paciente que chegou sem agendamento: o sistema gera a senha e o encaminha direto para o acolhimento (triagem) da enfermagem no mesmo dia.</p>
<form action="?page=salvar-agendamento" method="POST">
	<input type="hidden" name="acao" value="demanda">
	<div class="mb-3">
		<label>Paciente</label>
		<?php print select_pacientes('paciente_id', $_GET['paciente_id'] ?? null); ?>
		<small class="text-muted">Se o paciente não tiver cadastro, <a href="?page=novo">cadastre-o primeiro</a>.</small>
	</div>
	<div class="mb-3">
		<label>Tipo de senha</label>
		<select name="prioridade" class="form-control">
			<option value="Automática">Automática (preferencial para idosos, gestantes e PCD)</option>
			<option value="Preferencial">Preferencial</option>
			<option value="Normal">Normal</option>
		</select>
	</div>
	<div class="mb-3">
		<label>Motivo da procura</label>
		<input type="text" name="observacao" class="form-control" maxlength="255">
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Gerar senha e enviar para a triagem</button>
	</div>
</form>
