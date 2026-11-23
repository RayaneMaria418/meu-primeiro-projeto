<h1>Nova família</h1>
<?php
$cep = so_digitos($_GET['cep'] ?? '');
// Sugere a microárea de famílias já cadastradas no mesmo CEP (ou na mesma região do CEP)
$sugerida = $cep ? db_valor("SELECT microarea_id FROM familia WHERE cep_familia = ? OR LEFT(cep_familia, 5) = LEFT(?, 5)
                             ORDER BY (cep_familia = ?) DESC LIMIT 1", 'sss', $cep, $cep, $cep) : null;
?>
<form action="?page=salvar-familia" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<input type="hidden" name="paciente_id" value="<?php print (int)($_GET['paciente_id'] ?? 0); ?>">
	<div class="mb-3">
		<label>Nome da família</label>
		<input type="text" name="nome" class="form-control" placeholder="Ex.: Família Silva" required>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>CEP</label>
			<input type="text" name="cep" value="<?php print h($cep); ?>" class="form-control" required>
		</div>
		<div class="col-md-8 mb-3">
			<label>Endereço da residência</label>
			<input type="text" name="endereco" value="<?php print h($_GET['endereco'] ?? ''); ?>" class="form-control" required>
		</div>
	</div>
	<div class="mb-3">
		<label>Microárea / Equipe ESF</label>
		<select name="microarea_id" class="form-control" required>
			<option value="">-- Selecione --</option>
			<?php
				foreach (db_lista("SELECT * FROM microarea ORDER BY nome_microarea") as $m) {
					print "<option value='{$m->id_microarea}' " . sel($m->id_microarea, $sugerida) . ">" . h("{$m->nome_microarea} - {$m->equipe_esf}")
						. ($m->id_microarea == $sugerida ? ' (sugerida pelo CEP)' : '') . "</option>";
				}
			?>
		</select>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar</button>
	</div>
</form>
