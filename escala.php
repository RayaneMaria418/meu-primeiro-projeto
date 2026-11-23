<?php
$f = db_um("SELECT * FROM funcionario WHERE id_funcionario = ?", 'i', $_GET['id'] ?? 0);
if (!$f) {
    print "<div class='alert alert-danger'>Funcionário não encontrado.</div>";
    return;
}
?>
<h1>Escala de <?php print h($f->nome_funcionario); ?></h1>
<div class="row">
	<div class="col-md-6">
		<h4>Horários de trabalho</h4>
		<form action="?page=salvar-escala" method="POST" class="row g-2 mb-3">
			<input type="hidden" name="acao" value="incluir-escala">
			<input type="hidden" name="funcionario_id" value="<?php print $f->id_funcionario; ?>">
			<div class="col-4">
				<select name="dia" class="form-control" required>
					<?php foreach (DIAS_SEMANA as $n => $d) print "<option value='$n'>$d</option>"; ?>
				</select>
			</div>
			<div class="col-3"><input type="time" name="inicio" class="form-control" required></div>
			<div class="col-3"><input type="time" name="fim" class="form-control" required></div>
			<div class="col-2"><button type="submit" class="btn btn-primary">Incluir</button></div>
		</form>
		<?php
		$escalas = db_lista("SELECT * FROM escala WHERE funcionario_id = ? ORDER BY dia_semana, hr_inicio", 'i', $f->id_funcionario);
		if ($escalas) {
			print "<table class='table table-bordered table-striped table-hover'><tr><th>Dia</th><th>Início</th><th>Fim</th><th>Ações</th></tr>";
			foreach ($escalas as $e) {
				print "<tr><td>" . DIAS_SEMANA[$e->dia_semana] . "</td><td>" . hora_br($e->hr_inicio) . "</td><td>" . hora_br($e->hr_fim) . "</td>
				       <td><button onclick=\"if(confirm('Remover este horário?')){ location.href='?page=salvar-escala&acao=excluir-escala&id={$e->id_escala}'; }\" class='btn btn-danger btn-sm'>Excluir</button></td></tr>";
			}
			print "</table>";
		} else {
			print "<p class='alert alert-warning'>Sem horários cadastrados: o profissional não aparecerá com horários livres na agenda.</p>";
		}
		?>
	</div>
	<div class="col-md-6">
		<h4>Folgas, licenças e férias</h4>
		<form action="?page=salvar-escala" method="POST" class="mb-3">
			<input type="hidden" name="acao" value="incluir-ausencia">
			<input type="hidden" name="funcionario_id" value="<?php print $f->id_funcionario; ?>">
			<div class="row g-2">
				<div class="col-4"><input type="date" name="inicio" class="form-control" required></div>
				<div class="col-4"><input type="date" name="fim" class="form-control" required></div>
				<div class="col-4">
					<select name="motivo" class="form-control">
						<?php foreach (['Férias', 'Licença', 'Folga', 'Plantão em outra unidade', 'Outro'] as $m) print "<option>$m</option>"; ?>
					</select>
				</div>
				<div class="col-9"><input type="text" name="observacao" class="form-control" placeholder="Observação"></div>
				<div class="col-3"><button type="submit" class="btn btn-primary">Registrar</button></div>
			</div>
		</form>
		<?php
		$ausencias = db_lista("SELECT * FROM ausencia WHERE funcionario_id = ? AND dt_fim >= DATE_SUB(?, INTERVAL 90 DAY) ORDER BY dt_inicio", 'is', $f->id_funcionario, hoje());
		if ($ausencias) {
			print "<table class='table table-bordered table-striped table-hover'><tr><th>Período</th><th>Motivo</th><th>Observação</th><th>Ações</th></tr>";
			foreach ($ausencias as $a) {
				print "<tr><td>" . data_br($a->dt_inicio) . " a " . data_br($a->dt_fim) . "</td><td>" . h($a->motivo) . "</td><td>" . h($a->observacao_ausencia) . "</td>
				       <td><button onclick=\"if(confirm('Remover esta ausência?')){ location.href='?page=salvar-escala&acao=excluir-ausencia&id={$a->id_ausencia}'; }\" class='btn btn-danger btn-sm'>Excluir</button></td></tr>";
			}
			print "</table>";
		} else {
			print "<p class='text-muted'>Nenhuma ausência registrada.</p>";
		}
		?>
		<p class="text-muted">Durante as ausências, a agenda bloqueia automaticamente novos agendamentos com este profissional.</p>
	</div>
</div>
