<h1>Estoque de Medicamentos</h1>
<?php
$filtro = $_GET['filtro'] ?? '';
$tipo = $_GET['tipo'] ?? '';
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="listar-estoque">
	<div class="col-md-4">
		<select name="filtro" class="form-control">
			<option value="">Todos os lotes com saldo</option>
			<option value="vencendo" <?php print sel('vencendo', $filtro); ?>>Vencendo em até 30 dias</option>
			<option value="vencidos" <?php print sel('vencidos', $filtro); ?>>Vencidos (bloqueados)</option>
			<option value="zerados" <?php print sel('zerados', $filtro); ?>>Lotes zerados</option>
		</select>
	</div>
	<div class="col-md-3">
		<select name="tipo" class="form-control">
			<option value="">Todos os tipos</option>
			<?php foreach (['Medicamento', 'Vacina', 'Insumo'] as $t) print "<option " . sel($t, $tipo) . ">$t</option>"; ?>
		</select>
	</div>
	<div class="col-md-2"><button type="submit" class="btn btn-outline-primary">Filtrar</button></div>
</form>
<?php
$where = ["e.qt_estoque > 0"];
$tipos = '';
$params = [];
if ($filtro === 'vencendo') {
    $where[] = "e.dt_vali_estoque BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)"; $tipos .= 'ss'; array_push($params, hoje(), hoje());
} elseif ($filtro === 'vencidos') {
    $where[] = "e.dt_vali_estoque < ?"; $tipos .= 's'; $params[] = hoje();
} elseif ($filtro === 'zerados') {
    $where = ["e.qt_estoque = 0"];
}
if ($tipo !== '') {
    $where[] = "m.tipo_item = ?"; $tipos .= 's'; $params[] = $tipo;
}
$lista = db_lista("SELECT e.*, m.* FROM estoque e JOIN medicamento m ON m.id_medicamento = e.medicamento_id
                   WHERE " . implode(' AND ', $where) . " ORDER BY m.nm_medicamento, e.dt_vali_estoque", $tipos, ...$params);
$qtd = count($lista);

if($qtd > 0){
    print "<p>Encontrou <b>$qtd</b> resultado(s)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr>";
    print "<th>#</th>";
    print "<th>Medicamento</th>";
    print "<th>Tipo</th>";
    print "<th>Concentração</th>";
    print "<th>Unidade</th>";
    print "<th>Quantidade</th>";
    print "<th>Lote</th>";
    print "<th>Fabricante</th>";
    print "<th>Validade</th>";
    print "<th>Ações</th>";
    print "</tr>";

    foreach ($lista as $row) {
        print "<tr>";
        print "<td>" . $row->id_estoque . "</td>";
        print "<td>" . h($row->nm_medicamento) . ($row->controlado ? ' ' . badge('controlado', 'danger') : '') . "</td>";
        print "<td>" . h($row->tipo_item) . "</td>";
        print "<td>" . h($row->concentracao_medicamento) . "</td>";
        print "<td>" . h($row->unidade_medida) . "</td>";
        print "<td>" . (int)$row->qt_estoque . "</td>";
        print "<td>" . h($row->lote_estoque) . "</td>";
        print "<td>" . h($row->fabricante_estoque) . "</td>";
        print "<td>" . data_br($row->dt_vali_estoque) . " " . validade_badge($row->dt_vali_estoque) . "</td>";
        print "<td class='text-nowrap'>
                 <button onclick=\"location.href='?page=editar-estoque&id=" . $row->id_estoque . "';\" class='btn btn-success'>Editar</button>
                 <button onclick=\"location.href='?page=movimentar-estoque&id=" . $row->id_estoque . "';\" class='btn btn-primary'>Movimentar</button>
                 <button onclick=\"if(confirm('Tem certeza?')){ location.href='?page=salvar-estoque&acao=excluir&id=" . $row->id_estoque . "'; }\" class='btn btn-danger'>Excluir</button>
               </td>";
        print "</tr>";
    }
    print "</table></div>";
}else{
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
