<h1>Encaminhamento para especialista (Regulação)</h1>
<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 0 AND profissional_id = ?", 'ii', $_GET['at_id'] ?? 0, id_funcionario());
if (!$at) {
    print "<div class='alert alert-danger'>O encaminhamento é feito dentro de um atendimento em aberto seu.</div>";
    return;
}
print cabecalho_paciente($at->paciente_id);
$especialidades = db_lista("SELECT DISTINCT especialidade_funcionario AS e FROM funcionario WHERE especialidade_funcionario IS NOT NULL ORDER BY 1");
?>
<form action="?page=salvar-encaminhamento" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<input type="hidden" name="atendimento_id" value="<?php print $at->id_atendimento; ?>">
	<div class="mb-3">
		<label>Especialidade ou serviço</label>
		<input type="text" name="especialidade" class="form-control" list="lista-esp" required>
		<datalist id="lista-esp">
			<?php foreach ($especialidades as $e) print "<option value='" . h($e->e) . "'>"; ?>
			<option value="Ortopedia"><option value="Neurologia"><option value="Oftalmologia"><option value="Endocrinologia"><option value="Psiquiatria">
		</datalist>
	</div>
	<div class="mb-3">
		<label>Resumo do quadro clínico</label>
		<textarea name="resumo" class="form-control" rows="4" required><?php print h(trim($at->diagnostico_atendimento . ($at->cid_atendimento ? " (CID {$at->cid_atendimento})" : ''))); ?></textarea>
	</div>
	<div class="row">
		<div class="col-md-6 mb-3">
			<label>Prioridade regulatória</label>
			<select name="prioridade" class="form-control"><option>Eletiva</option><option>Prioritária</option><option>Urgente</option></select>
		</div>
		<div class="col-md-6 mb-3">
			<label>Complexidade</label>
			<select name="complexidade" class="form-control"><option>Básica</option><option selected>Média</option><option>Alta</option></select>
			<small class="text-muted">Média e alta complexidade geram código de rastreio na regulação.</small>
		</div>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Enviar para a regulação</button>
		<a href="?page=editar-atendimento&id=<?php print $at->id_atendimento; ?>" class="btn btn-secondary">Voltar ao atendimento</a>
	</div>
</form>
