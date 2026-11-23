<h1>Novo Medicamento (Estoque)</h1>
<p class="text-muted">Registra a entrada de um lote. Se o item ainda não existir no catálogo, ele é criado automaticamente.</p>
<form action="?page=salvar-estoque" method="POST">
	<input type="hidden" name="acao" value="cadastrar">

    <div class="mb-3">
		<label>Nome do Medicamento</label>
		<input type="text" name="nome_medicamento" class="form-control" list="catalogo" required>
		<datalist id="catalogo">
			<?php foreach (db_lista("SELECT DISTINCT nm_medicamento FROM medicamento ORDER BY nm_medicamento") as $m) print "<option value='" . h($m->nm_medicamento) . "'>"; ?>
		</datalist>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>Tipo</label>
			<select name="tipo_item" class="form-control">
				<option>Medicamento</option><option>Vacina</option><option>Insumo</option>
			</select>
		</div>
		<div class="col-md-4 mb-3">
			<label>Concentração (ex: 100mg, 50ml)</label>
			<input type="text" name="concentracao" class="form-control" required>
		</div>
		<div class="col-md-4 mb-3">
			<label>Unidade de Medida (ex: Caixa, Comprimido, Frasco)</label>
			<input type="text" name="unidade_medida" class="form-control" required>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3 mb-3">
			<label>Quantidade em Estoque</label>
			<input type="number" name="quantidade" class="form-control" min="1" required>
		</div>
		<div class="col-md-3 mb-3">
			<label>Lote</label>
			<input type="text" name="lote" class="form-control" required>
		</div>
		<div class="col-md-3 mb-3">
			<label>Fabricante</label>
			<input type="text" name="fabricante" class="form-control" required>
		</div>
		<div class="col-md-3 mb-3">
			<label>Data de Validade</label>
			<input type="date" name="data_validade" class="form-control" min="<?php print hoje(); ?>" required>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>Nota fiscal / documento de entrada</label>
			<input type="text" name="documento" class="form-control">
		</div>
		<div class="col-md-5 mb-3">
			<label>Dose máxima recomendada (medicamentos)</label>
			<input type="text" name="dose_maxima" class="form-control" placeholder="Ex.: Até 4 g por dia (adulto)">
		</div>
		<div class="col-md-3 mb-3 pt-4">
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="controlado" value="1" id="ctrl">
				<label class="form-check-label" for="ctrl">Medicamento controlado</label>
			</div>
		</div>
	</div>

	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar</button>
	</div>
</form>
