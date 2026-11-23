<h1>Movimentações de estoque</h1>
<?php
$de = $_GET['de'] ?? date('Y-m-d', strtotime('-30 days'));
$ate = $_GET['ate'] ?? hoje();
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="listar-movimentacoes">
	<div class="col-md-3"><input type="date" name="de" value="<?php print h($de); ?>" class="form-control"></div>
	<div class="col-md-3"><input type="date" name="ate" value="<?php print h($ate); ?>" class="form-control"></div>
	<div class="col-md-2"><button type="submit" class="btn btn-outline-primary">Filtrar</button></div>
</form>
<?php
$lista = db_lista("SELECT mv.*, e.lote_estoque, m.nm_medicamento, m.concentracao_medicamento, COALESCE(f.nome_funcionario, '-') AS responsavel
                   FROM movimentacao_estoque mv
                   JOIN estoque e ON e.id_estoque = mv.estoque_id
                   JOIN medicamento m ON m.id_medicamento = e.medicamento_id
                   LEFT JOIN usuario u ON u.id_usuario = mv.usuario_id
                   LEFT JOIN funcionario f ON f.id_funcionario = u.funcionario_id
                   WHERE DATE(mv.dt_movimentacao) BETWEEN ? AND ? ORDER BY mv.dt_movimentacao DESC, mv.id_movimentacao DESC", 'ss', $de, $ate);
if ($lista) {
    print "<p>Encontrou <b>" . count($lista) . "</b> resultado(s)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>#</th><th>Data</th><th>Tipo</th><th>Item</th><th>Lote</th><th>Qtd.</th><th>Documento</th><th>Responsável</th><th>Ações</th></tr>";
    foreach ($lista as $mv) {
        print "<tr><td>{$mv->id_movimentacao}</td><td>" . datahora_br($mv->dt_movimentacao) . "</td><td>" . h($mv->tipo_movimentacao) . "</td>"
            . "<td>" . h("{$mv->nm_medicamento} {$mv->concentracao_medicamento}") . "</td><td>" . h($mv->lote_estoque) . "</td><td>" . (int)$mv->quantidade . "</td>"
            . "<td>" . h($mv->documento) . "</td><td>" . h($mv->responsavel) . "</td>"
            . "<td><button onclick=\"location.href='?page=comprovante&id={$mv->id_movimentacao}';\" class='btn btn-success btn-sm'>Comprovante</button></td></tr>";
    }
    print "</table></div>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
