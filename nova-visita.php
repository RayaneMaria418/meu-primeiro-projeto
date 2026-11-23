<h1>Registrar visita domiciliar</h1>
<?php
$familias = db_lista("SELECT f.*, m.nome_microarea FROM familia f JOIN microarea m ON m.id_microarea = f.microarea_id
                      WHERE m.acs_id = ? ORDER BY f.nome_familia", 'i', id_funcionario());
if (!$familias) {
    print "<div class='alert alert-warning'>Nenhuma família está associada às suas microáreas. Peça ao administrador para vincular você a uma microárea.</div>";
    return;
}
?>
<div id="aviso-offline" class="alert alert-warning d-none"></div>
<form id="form-visita" action="?page=salvar-visita" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<input type="hidden" name="coletado_em" id="coletado_em">
	<div class="mb-3">
		<label>Família visitada</label>
		<select name="familia_id" class="form-control" required>
			<option value="">-- Selecione --</option>
			<?php foreach ($familias as $f) print "<option value='{$f->id_familia}' " . sel($f->id_familia, $_GET['familia_id'] ?? '') . ">" . h("{$f->nome_familia} - {$f->endereco_familia} ({$f->nome_microarea})") . "</option>"; ?>
		</select>
	</div>
	<div class="row">
		<div class="col-md-6 mb-3">
			<label>Data e hora da visita</label>
			<input type="datetime-local" name="dt_visita" value="<?php print date('Y-m-d\TH:i'); ?>" max="<?php print date('Y-m-d\TH:i'); ?>" class="form-control" required>
		</div>
		<div class="col-md-6 mb-3">
			<label>Situação da família</label>
			<select name="situacao" class="form-control"><option>Acompanhada</option><option>Em risco</option><option>Ausente</option></select>
		</div>
	</div>
	<div class="mb-3">
		<label>Motivo da visita</label>
		<input type="text" name="motivo" class="form-control" placeholder="Ex.: acompanhamento de hipertenso, puericultura, busca ativa de vacina" required>
	</div>
	<div class="mb-3">
		<label>Pessoas acompanhadas</label>
		<input type="text" name="pessoas" class="form-control">
	</div>
	<div class="mb-3">
		<label>Condições observadas</label>
		<textarea name="condicoes" class="form-control" rows="3"></textarea>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar visita</button>
		<small class="text-muted ms-2">Sem internet? A visita fica guardada no aparelho e é enviada sozinha quando a conexão voltar.</small>
	</div>
</form>
<script src="js/visitas-offline.js"></script>
