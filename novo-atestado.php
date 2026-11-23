<h1>Atestados e declarações</h1>
<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND profissional_id = ?", 'ii', $_GET['at_id'] ?? 0, id_funcionario());
if (!$at) {
    print "<div class='alert alert-danger'>O documento é emitido a partir de um atendimento seu.</div>";
    return;
}
print cabecalho_paciente($at->paciente_id);
?>
<form action="?page=salvar-atestado" method="POST">
	<input type="hidden" name="atendimento_id" value="<?php print $at->id_atendimento; ?>">
	<div class="mb-3">
		<label>Tipo de documento</label>
		<select name="tipo" class="form-control" required>
			<option>Atestado médico</option>
			<option>Declaração de comparecimento</option>
			<option>Laudo</option>
		</select>
	</div>
	<div class="mb-3">
		<label>Dias de afastamento (atestado)</label>
		<input type="number" name="dias" class="form-control" min="0" max="365">
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>CID-10</label>
			<input type="text" name="cid" class="form-control" maxlength="10" value="<?php print h($at->cid_atendimento); ?>">
		</div>
		<div class="col-md-8 mb-3 pt-4">
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="consentimento_cid" value="1" id="ccid">
				<label class="form-check-label" for="ccid">O paciente autorizou expressamente a inclusão do CID no documento</label>
			</div>
		</div>
	</div>
	<div class="mb-3">
		<label>Texto complementar / conteúdo do laudo</label>
		<textarea name="texto" class="form-control" rows="4"></textarea>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Assinar e emitir</button>
		<a href="?page=editar-atendimento&id=<?php print $at->id_atendimento; ?>" class="btn btn-secondary">Voltar ao atendimento</a>
	</div>
</form>
