<h1>Editar Item do Estoque</h1>
<?php
$row = db_um("SELECT e.*, m.* FROM estoque e JOIN medicamento m ON m.id_medicamento = e.medicamento_id WHERE e.id_estoque = ?", 'i', $_REQUEST['id'] ?? 0);
if (!$row) {
    print "<div class='alert alert-danger'>Item não encontrado.</div>";
    return;
}
?>
<form action="?page=salvar-estoque" method="POST">
	<input type="hidden" name="acao" value="editar">
    <input type="hidden" name="id" value="<?php print $row->id_estoque; ?>">

    <div class="mb-3">
		<label>Nome do Medicamento</label>
		<input type="text" name="nome_medicamento" class="form-control" value="<?php print h($row->nm_medicamento); ?>" required>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>Tipo</label>
			<select name="tipo_item" class="form-control">
				<?php foreach (['Medicamento', 'Vacina', 'Insumo'] as $t) print "<option " . sel($t, $row->tipo_item) . ">$t</option>"; ?>
			</select>
		</div>
	    <div class="col-md-4 mb-3">
			<label>Concentração</label>
			<input type="text" name="concentracao" class="form-control" value="<?php print h($row->concentracao_medicamento); ?>" required>
		</div>
	    <div class="col-md-4 mb-3">
			<label>Unidade de Medida</label>
			<input type="text" name="unidade_medida" class="form-control" value="<?php print h($row->unidade_medida); ?>" required>
		</div>
	</div>
	<div class="row">
	    <div class="col-md-3 mb-3">
			<label>Quantidade em Estoque</label>
			<input type="number" name="quantidade" class="form-control" min="0" value="<?php print (int)$row->qt_estoque; ?>" required>
			<small class="text-muted">Alterar gera um ajuste de inventário.</small>
		</div>
	    <div class="col-md-3 mb-3">
			<label>Lote</label>
			<input type="text" name="lote" class="form-control" value="<?php print h($row->lote_estoque); ?>" required>
		</div>
	    <div class="col-md-3 mb-3">
			<label>Fabricante</label>
			<input type="text" name="fabricante" class="form-control" value="<?php print h($row->fabricante_estoque); ?>" required>
		</div>
	    <div class="col-md-3 mb-3">
			<label>Data de Validade</label>
			<input type="date" name="data_validade" class="form-control" value="<?php print h($row->dt_vali_estoque); ?>" required>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6 mb-3">
			<label>Dose máxima recomendada</label>
			<input type="text" name="dose_maxima" class="form-control" value="<?php print h($row->dose_maxima); ?>">
		</div>
		<div class="col-md-6 mb-3 pt-4">
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="controlado" value="1" id="ctrl" <?php print marcado($row->controlado); ?>>
				<label class="form-check-label" for="ctrl">Medicamento controlado</label>
			</div>
		</div>
	</div>

	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar Edição</button>
		<a href="?page=movimentar-estoque&id=<?php print $row->id_estoque; ?>" class="btn btn-outline-primary">Registrar movimentação</a>
	</div>
</form>
