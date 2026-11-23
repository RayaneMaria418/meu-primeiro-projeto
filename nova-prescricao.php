<h1>Prescrição de medicamentos</h1>
<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 0 AND profissional_id = ?", 'ii', $_GET['at_id'] ?? 0, id_funcionario());
if (!$at) {
    print "<div class='alert alert-danger'>A prescrição só pode ser feita dentro de um atendimento em aberto seu.</div>";
    return;
}
print cabecalho_paciente($at->paciente_id);
$meds = db_lista("SELECT m.*, (SELECT COALESCE(SUM(e.qt_estoque),0) FROM estoque e WHERE e.medicamento_id = m.id_medicamento AND e.dt_vali_estoque >= ?) AS saldo
                  FROM medicamento m WHERE m.tipo_item = 'Medicamento' ORDER BY m.nm_medicamento", 's', hoje());
$controlados = array_filter($meds, fn($m) => $m->controlado);
?>
<?php if ($controlados) { ?>
<div class="alert alert-warning">
	<b>Medicamentos controlados — limite de dosagem recomendado:</b>
	<ul class="mb-0">
		<?php foreach ($controlados as $m) print "<li>" . h("{$m->nm_medicamento} {$m->concentracao_medicamento}: {$m->dose_maxima}") . " (saldo: {$m->saldo})</li>"; ?>
	</ul>
</div>
<?php } ?>
<form action="?page=salvar-prescricao" method="POST">
	<input type="hidden" name="atendimento_id" value="<?php print $at->id_atendimento; ?>">
	<table class="table table-bordered">
		<tr><th>Medicamento (catálogo da farmácia)</th><th>Posologia / dosagem</th><th style="width:110px">Quantidade</th><th style="width:150px">Duração</th></tr>
		<?php for ($i = 0; $i < 5; $i++) { ?>
		<tr>
			<td>
				<select name="medicamento_id[]" class="form-control" <?php print $i === 0 ? 'required' : ''; ?>>
					<option value="">-- Selecione --</option>
					<?php foreach ($meds as $m) {
						$info = $m->controlado ? ' [CONTROLADO]' : '';
						$info .= $m->saldo > 0 ? " (estoque: {$m->saldo})" : ' (SEM ESTOQUE)';
						print "<option value='{$m->id_medicamento}'>" . h("{$m->nm_medicamento} {$m->concentracao_medicamento} - {$m->unidade_medida}$info") . "</option>";
					} ?>
				</select>
			</td>
			<td><input type="text" name="posologia[]" class="form-control" placeholder="Ex.: 1 comprimido de 8 em 8 horas"></td>
			<td><input type="number" name="quantidade[]" class="form-control" min="1"></td>
			<td><input type="text" name="duracao[]" class="form-control" placeholder="Ex.: 7 dias"></td>
		</tr>
		<?php } ?>
	</table>
	<div class="mb-3">
		<label>Orientações gerais</label>
		<textarea name="observacao" class="form-control" rows="2"></textarea>
	</div>
	<div class="form-check mb-3">
		<input class="form-check-input" type="checkbox" name="confirma_dose" value="1" id="dose">
		<label class="form-check-label" for="dose">Confirmo que as doses prescritas não ultrapassam o limite recomendado (obrigatório para controlados).</label>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Assinar e emitir receita</button>
		<a href="?page=editar-atendimento&id=<?php print $at->id_atendimento; ?>" class="btn btn-secondary">Voltar ao atendimento</a>
	</div>
</form>
