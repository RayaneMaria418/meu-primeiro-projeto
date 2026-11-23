<?php
// RF22 / RN-RF22: relatórios de produção sem identificação nominal de pacientes
$de = $_GET['de'] ?? date('Y-m-01');
$ate = $_GET['ate'] ?? hoje();
$prof = (int)($_GET['funcionario_id'] ?? 0);

$filtro_prof = $prof ? ' AND f.id_funcionario = ?' : '';
$p = fn(...$x) => $prof ? array_merge($x, [$prof]) : $x;

$linhas = db_lista("SELECT f.id_funcionario, f.nome_funcionario, f.cargo_funcionario, f.especialidade_funcionario,
        (SELECT COUNT(*) FROM agendamento a WHERE a.funcionario_id = f.id_funcionario AND a.dt_agendamento BETWEEN ? AND ?) AS agendados,
        (SELECT COUNT(*) FROM agendamento a WHERE a.funcionario_id = f.id_funcionario AND a.dt_agendamento BETWEEN ? AND ? AND a.status_agendamento IN ('Finalizado','Aguardando Medicamento')) AS realizados,
        (SELECT COUNT(*) FROM agendamento a WHERE a.funcionario_id = f.id_funcionario AND a.dt_agendamento BETWEEN ? AND ? AND a.status_agendamento = 'Cancelado') AS cancelados,
        (SELECT COUNT(*) FROM agendamento a WHERE a.funcionario_id = f.id_funcionario AND a.dt_agendamento BETWEEN ? AND ? AND a.status_agendamento = 'Ausente') AS faltas,
        (SELECT COUNT(*) FROM atendimento x WHERE x.profissional_id = f.id_funcionario AND DATE(x.dt_atendimento) BETWEEN ? AND ?) AS atendimentos,
        (SELECT COUNT(*) FROM triagem t WHERE t.enfermeiro_id = f.id_funcionario AND DATE(t.dt_triagem) BETWEEN ? AND ?) AS triagens,
        (SELECT COUNT(*) FROM solicitacao_exame s WHERE s.profissional_id = f.id_funcionario AND DATE(s.dt_solicitacao) BETWEEN ? AND ?) AS exames,
        (SELECT COUNT(*) FROM vacinacao v WHERE v.aplicador_id = f.id_funcionario AND v.dt_aplicacao BETWEEN ? AND ?) AS vacinas,
        (SELECT COUNT(*) FROM prescricao r WHERE r.profissional_id = f.id_funcionario AND DATE(r.dt_emissao) BETWEEN ? AND ?) AS receitas,
        (SELECT COUNT(*) FROM dispensacao d WHERE d.farmaceutico_id = f.id_funcionario AND DATE(d.dt_dispensacao) BETWEEN ? AND ?) AS dispensadas,
        (SELECT COUNT(*) FROM visita_acs w WHERE w.acs_id = f.id_funcionario AND DATE(w.dt_visita) BETWEEN ? AND ? AND w.dentro_prazo = 1) AS visitas
    FROM funcionario f WHERE f.cargo_funcionario <> 'Administrador' AND f.cargo_funcionario <> 'Recepcao'$filtro_prof ORDER BY f.cargo_funcionario, f.nome_funcionario",
    str_repeat('s', 22) . ($prof ? 'i' : ''), ...$p(...array_merge(...array_fill(0, 11, [$de, $ate]))));

$consumo = db_lista("SELECT m.nm_medicamento, m.concentracao_medicamento, m.tipo_item, SUM(mv.quantidade) AS total
                     FROM movimentacao_estoque mv JOIN estoque e ON e.id_estoque = mv.estoque_id JOIN medicamento m ON m.id_medicamento = e.medicamento_id
                     WHERE mv.tipo_movimentacao IN ('Saída por dispensação','Saída por vacinação') AND DATE(mv.dt_movimentacao) BETWEEN ? AND ?
                     GROUP BY m.id_medicamento ORDER BY total DESC", 'ss', $de, $ate);

$colunas = ['nome_funcionario' => 'Profissional', 'cargo_funcionario' => 'Cargo', 'agendados' => 'Consultas agendadas', 'realizados' => 'Realizadas',
            'cancelados' => 'Canceladas', 'faltas' => 'Faltas', 'atendimentos' => 'Atendimentos', 'triagens' => 'Triagens', 'exames' => 'Exames solicitados',
            'vacinas' => 'Vacinas aplicadas', 'receitas' => 'Receitas emitidas', 'dispensadas' => 'Receitas dispensadas', 'visitas' => 'Visitas ACS (no prazo)'];

// Exportação em planilha (CSV, abre no Excel)
if (defined('MODO_DOWNLOAD')) {
    auditoria('Exportar', 'Relatório de produção', null, null, "$de a $ate");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="producao_' . $de . '_' . $ate . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ["Relatório de produção - $de a $ate"], ';');
    fputcsv($out, array_values($colunas), ';');
    foreach ($linhas as $l) {
        fputcsv($out, array_map(fn($c) => $c === 'cargo_funcionario' ? NOMES_PERFIL[$l->$c] : $l->$c, array_keys($colunas)), ';');
    }
    fputcsv($out, [], ';');
    fputcsv($out, ['Consumo de estoque', 'Tipo', 'Quantidade'], ';');
    foreach ($consumo as $c) {
        fputcsv($out, ["{$c->nm_medicamento} {$c->concentracao_medicamento}", $c->tipo_item, $c->total], ';');
    }
    fclose($out);
    return;
}
?>
<h1>Relatórios de produção</h1>
<p class="text-muted">Dados consolidados por profissional e período, sem identificação nominal dos pacientes (sigilo médico).</p>
<form action="index.php" method="GET" class="row g-2 mb-3 no-print">
	<input type="hidden" name="page" value="relatorios">
	<div class="col-md-3"><input type="date" name="de" value="<?php print h($de); ?>" class="form-control"></div>
	<div class="col-md-3"><input type="date" name="ate" value="<?php print h($ate); ?>" class="form-control"></div>
	<div class="col-md-3"><?php print select_profissionais('funcionario_id', $prof, [MED, ENF, ODO, FAR, ACS], false); ?></div>
	<div class="col-md-3"><button type="submit" class="btn btn-outline-primary">Gerar</button></div>
</form>
<p class="no-print">
	<a href="?page=relatorio-csv&de=<?php print h($de); ?>&ate=<?php print h($ate); ?>&funcionario_id=<?php print $prof; ?>" class="btn btn-success">Exportar planilha (CSV)</a>
	<button onclick="window.print()" class="btn btn-primary">Imprimir / salvar em PDF</button>
</p>
<h5>Período: <?php print data_br($de) . ' a ' . data_br($ate); ?></h5>
<div class="table-responsive">
<table class="table table-bordered table-striped table-hover table-sm">
	<tr><?php foreach ($colunas as $titulo) print "<th>$titulo</th>"; ?></tr>
	<?php
	$totais = [];
	foreach ($linhas as $l) {
		print "<tr>";
		foreach (array_keys($colunas) as $c) {
			$valor = $c === 'cargo_funcionario' ? NOMES_PERFIL[$l->$c] : $l->$c;
			print "<td>" . h($valor) . "</td>";
			if (!in_array($c, ['nome_funcionario', 'cargo_funcionario'], true)) $totais[$c] = ($totais[$c] ?? 0) + $l->$c;
		}
		print "</tr>";
	}
	print "<tr class='fw-bold'><td colspan='2'>Total</td>";
	foreach (array_slice(array_keys($colunas), 2) as $c) print "<td>" . ($totais[$c] ?? 0) . "</td>";
	print "</tr>";
	?>
</table>
</div>
<h5>Consumo de estoque no período</h5>
<?php
if ($consumo) {
    print "<table class='table table-bordered table-striped table-sm'><tr><th>Item</th><th>Tipo</th><th>Quantidade consumida</th></tr>";
    foreach ($consumo as $c) print "<tr><td>" . h("{$c->nm_medicamento} {$c->concentracao_medicamento}") . "</td><td>" . h($c->tipo_item) . "</td><td>" . (int)$c->total . "</td></tr>";
    print "</table>";
} else {
    print "<p class='text-muted'>Sem saídas de estoque no período.</p>";
}
?>
