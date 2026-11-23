<h1>Registrar vacina aplicada</h1>
<?php
$paciente_id = (int)($_GET['paciente_id'] ?? 0);
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="nova-vacinacao">
	<div class="col-md-8"><?php print select_pacientes('paciente_id', $paciente_id); ?></div>
	<div class="col-md-4"><button type="submit" class="btn btn-outline-primary">Abrir ficha de vacinação</button></div>
</form>
<?php
if (!$paciente_id) {
    return;
}
print cabecalho_paciente($paciente_id);
print alerta_vacinas($paciente_id);
$pendentes = array_map(fn($v) => $v->id_calendario, vacinas_pendentes($paciente_id));
$aplicadas = array_map(fn($v) => $v->calendario_id, db_lista("SELECT calendario_id FROM vacinacao WHERE paciente_id = ?", 'i', $paciente_id));
// RN-RF16: só aparecem lotes de vacina dentro da validade e com saldo
$lotes = db_lista("SELECT e.*, m.nm_medicamento FROM estoque e JOIN medicamento m ON m.id_medicamento = e.medicamento_id
                   WHERE m.tipo_item = 'Vacina' AND e.qt_estoque > 0 AND e.dt_vali_estoque >= ? ORDER BY m.nm_medicamento, e.dt_vali_estoque", 's', hoje());
?>
<form action="?page=salvar-vacinacao" method="POST">
	<input type="hidden" name="paciente_id" value="<?php print $paciente_id; ?>">
	<div class="mb-3">
		<label>Vacina e dose (Calendário Nacional)</label>
		<select name="calendario_id" class="form-control" required>
			<option value="">-- Selecione --</option>
			<?php foreach (db_lista("SELECT * FROM vacina_calendario ORDER BY idade_meses, vacina") as $c) {
				if (in_array($c->id_calendario, $aplicadas)) continue;
				$marca = in_array($c->id_calendario, $pendentes) ? ' — PENDENTE' : '';
				print "<option value='{$c->id_calendario}'>" . h("{$c->vacina} - {$c->dose} (" . ($c->idade_meses ? $c->idade_meses . ' meses' : 'ao nascer') . ")$marca") . "</option>";
			} ?>
		</select>
	</div>
	<div class="mb-3">
		<label>Lote do imunobiológico (fabricante e validade)</label>
		<select name="estoque_id" class="form-control" required>
			<option value="">-- Selecione --</option>
			<?php foreach ($lotes as $l) print "<option value='{$l->id_estoque}'>" . h("{$l->nm_medicamento} - lote {$l->lote_estoque} - {$l->fabricante_estoque} - validade " . data_br($l->dt_vali_estoque) . " ({$l->qt_estoque} doses)") . "</option>"; ?>
		</select>
		<small class="text-muted">Lotes vencidos ou sem saldo não aparecem.</small>
	</div>
	<div class="row">
		<div class="col-md-6 mb-3">
			<label>Via de administração</label>
			<select name="via" class="form-control" required>
				<?php foreach (['Intramuscular', 'Subcutânea', 'Intradérmica', 'Oral'] as $v) print "<option>$v</option>"; ?>
			</select>
		</div>
		<div class="col-md-6 mb-3">
			<label>Data da aplicação</label>
			<input type="date" name="data" value="<?php print hoje(); ?>" max="<?php print hoje(); ?>" class="form-control" required>
		</div>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Registrar aplicação</button>
		<a href="?page=cartao-vacina&id=<?php print $paciente_id; ?>" class="btn btn-secondary">Ver cartão de vacina</a>
	</div>
</form>
