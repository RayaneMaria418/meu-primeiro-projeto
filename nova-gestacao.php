<h1>Iniciar acompanhamento de pré-natal</h1>
<?php
$paciente_id = (int)($_GET['paciente_id'] ?? 0);
$pac = db_um("SELECT * FROM paciente WHERE id_paciente = ? AND sexo_paciente = 'F'", 'i', $paciente_id);
if (!$pac) {
    print "<div class='alert alert-danger'>Paciente não encontrada (o pré-natal é para pacientes do sexo feminino).</div>";
    return;
}
print cabecalho_paciente($paciente_id);
?>
<form action="?page=salvar-gestacao" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<input type="hidden" name="paciente_id" value="<?php print $paciente_id; ?>">
	<div class="mb-3">
		<label>Data da última menstruação (DUM)</label>
		<input type="date" name="dum" class="form-control" max="<?php print hoje(); ?>" required>
		<small class="text-muted">O sistema calcula a idade gestacional e a data provável do parto (DUM + 280 dias).</small>
	</div>
	<div class="mb-3">
		<label>Exames já realizados</label>
		<textarea name="exames" class="form-control" rows="2"></textarea>
	</div>
	<div class="mb-3">
		<label>Observações</label>
		<textarea name="observacoes" class="form-control" rows="2"></textarea>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar</button>
	</div>
</form>
