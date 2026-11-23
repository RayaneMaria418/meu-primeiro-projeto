<h1>Dispensação de medicamentos</h1>
<?php $documento = so_digitos($_GET['documento'] ?? ''); ?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="dispensacao">
	<div class="col-md-6"><input type="text" name="documento" value="<?php print h($documento); ?>" class="form-control" placeholder="CPF ou Cartão SUS do paciente" required autofocus></div>
	<div class="col-md-3"><button type="submit" class="btn btn-primary">Buscar receitas</button></div>
</form>
<?php
if ($documento === '') {
    $pendentes = db_lista("SELECT pr.id_prescricao, pr.dt_emissao, p.nome_paciente, p.cpf_paciente, p.cns_paciente FROM prescricao pr
                           JOIN paciente p ON p.id_paciente = pr.paciente_id WHERE pr.status_prescricao = 'Pendente' ORDER BY pr.dt_emissao");
    print "<h5>Receitas pendentes na farmácia</h5>";
    if ($pendentes) {
        print "<table class='table table-bordered table-striped table-hover'><tr><th>Receita</th><th>Emissão</th><th>Paciente</th><th>Ações</th></tr>";
        foreach ($pendentes as $r) {
            $doc = $r->cpf_paciente ?: $r->cns_paciente;
            print "<tr><td>#{$r->id_prescricao}</td><td>" . datahora_br($r->dt_emissao) . "</td><td>" . h($r->nome_paciente) . "</td>"
                . "<td><button onclick=\"location.href='?page=dispensacao&documento=" . urlencode((string)$doc) . "';\" class='btn btn-primary'>Atender</button></td></tr>";
        }
        print "</table>";
    } else {
        print "<p class='alert alert-secondary'>Nenhuma receita pendente.</p>";
    }
    return;
}

$pac = db_um("SELECT * FROM paciente WHERE cpf_paciente = ? OR cns_paciente = ?", 'ss', $documento, $documento);
if (!$pac) {
    print "<div class='alert alert-danger'>Nenhum paciente com este CPF ou Cartão SUS.</div>";
    return;
}
auditoria('Visualizar', 'Prescrições', $pac->id_paciente, $pac->id_paciente, 'Farmácia');
print "<div class='alert alert-light border'><b>" . h($pac->nome_paciente) . "</b> — " . idade_texto($pac->dt_nasc_paciente) . "</div>";

$receitas = db_lista("SELECT pr.*, f.nome_funcionario, f.registro_conselho FROM prescricao pr JOIN funcionario f ON f.id_funcionario = pr.profissional_id
                      WHERE pr.paciente_id = ? AND pr.status_prescricao = 'Pendente' ORDER BY pr.dt_emissao", 'i', $pac->id_paciente);
if (!$receitas) {
    print "<p class='alert alert-secondary'>Este paciente não tem receitas pendentes.</p>";
    return;
}
foreach ($receitas as $r) {
    $itens = db_lista("SELECT i.*, m.nm_medicamento, m.concentracao_medicamento, m.unidade_medida, m.controlado FROM prescricao_item i
                       JOIN medicamento m ON m.id_medicamento = i.medicamento_id WHERE i.prescricao_id = ?", 'i', $r->id_prescricao);
    $tem_controlado = (bool)array_filter($itens, fn($i) => $i->controlado);
    $dias = (int)floor((time() - strtotime($r->dt_emissao)) / 86400);
    ?>
	<div class="card mb-3"><div class="card-body">
		<h5>Receita #<?php print $r->id_prescricao; ?> — <?php print datahora_br($r->dt_emissao) . ' — ' . h($r->nome_funcionario . ' (' . $r->registro_conselho . ')'); ?>
			<a href="?page=documento&tipo=receita&id=<?php print $r->id_prescricao; ?>" class="btn btn-sm btn-outline-secondary">ver receita</a></h5>
		<?php if ($tem_controlado && $dias > 30) { ?>
			<div class="alert alert-danger">Receita com medicamento controlado emitida há <?php print $dias; ?> dias: fora da validade legal de 30 dias (RN-RF18). Dispensação bloqueada.</div>
		<?php continue; } ?>
		<form action="?page=salvar-dispensacao" method="POST">
			<input type="hidden" name="prescricao_id" value="<?php print $r->id_prescricao; ?>">
			<input type="hidden" name="documento" value="<?php print h($documento); ?>">
			<table class="table table-bordered">
				<tr><th>Medicamento</th><th>Posologia</th><th>Prescrito</th><th>Em estoque (válido)</th><th>Entregar</th></tr>
				<?php foreach ($itens as $i) {
					$saldo = saldo_valido($i->medicamento_id); ?>
				<tr>
					<td><?php print h("{$i->nm_medicamento} {$i->concentracao_medicamento}") . ($i->controlado ? ' ' . badge('controlado', 'danger') : ''); ?></td>
					<td><?php print h($i->posologia . ' - ' . $i->duracao); ?></td>
					<td><?php print (int)$i->quantidade; ?></td>
					<td><?php print $saldo < $i->quantidade ? "<span class='text-danger'><b>$saldo</b> (falta)</span>" : $saldo; ?></td>
					<td style="width:130px"><input type="number" name="entregar[<?php print $i->id_item; ?>]" value="<?php print min((int)$i->quantidade, $saldo); ?>"
						min="0" max="<?php print min((int)$i->quantidade, $saldo); ?>" class="form-control"></td>
				</tr>
				<?php } ?>
			</table>
			<?php if ($tem_controlado) { ?>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="receita_fisica" value="1" id="rf<?php print $r->id_prescricao; ?>" required>
				<label class="form-check-label" for="rf<?php print $r->id_prescricao; ?>">Receita física de controle especial apresentada, conferida e retida (emitida há <?php print $dias; ?> dia(s)).</label>
			</div>
			<?php } ?>
			<button type="submit" class="btn btn-success">Confirmar entrega e baixar estoque</button>
		</form>
	</div></div>
<?php } ?>
<p class="text-muted">A baixa usa primeiro os lotes que vencem antes; lotes vencidos ficam bloqueados (RN-RF20).</p>
