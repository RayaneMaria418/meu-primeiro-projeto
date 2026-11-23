<h1>Movimentação de estoque</h1>
<?php
$id = (int)($_REQUEST['id'] ?? 0);
$row = db_um("SELECT e.*, m.nm_medicamento, m.concentracao_medicamento FROM estoque e JOIN medicamento m ON m.id_medicamento = e.medicamento_id WHERE e.id_estoque = ?", 'i', $id);
if (!$row) {
    print "<div class='alert alert-danger'>Lote não encontrado.</div>";
    return;
}

if (($_POST['acao'] ?? '') === 'movimentar') {
    $tipo = $_POST['tipo'] ?? '';
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $doc = trim($_POST['documento'] ?? '') ?: null;
    $obs = trim($_POST['observacao'] ?? '') ?: null;
    $vencido = $row->dt_vali_estoque < hoje();
    if (!in_array($tipo, ['Entrada', 'Transferência', 'Baixa por avaria'], true) || $qtd < 1) {
        voltar('Escolha o tipo e uma quantidade válida.');
        return;
    }
    if ($tipo !== 'Entrada' && $qtd > $row->qt_estoque) {
        voltar('Quantidade maior que o saldo do lote.');
        return;
    }
    // RN-RF20: lote vencido só pode sair como baixa (descarte)
    if ($vencido && $tipo !== 'Baixa por avaria') {
        voltar('Lote vencido: só é permitido registrar a baixa por avaria/descarte.');
        return;
    }
    $sinal = $tipo === 'Entrada' ? 1 : -1;
    $conn->begin_transaction();
    db("UPDATE estoque SET qt_estoque = qt_estoque + ? WHERE id_estoque = ?", 'ii', $sinal * $qtd, $id);
    $mov = registrar_movimentacao($id, $tipo, $qtd, $doc, $obs);
    $conn->commit();
    auditoria('Movimentar', 'Estoque', $id, null, "$tipo: $qtd");
    mensagem('Movimentação registrada. Comprovante assinado eletronicamente.', 'comprovante&id=' . $mov);
    return;
}
?>
<p><b><?php print h("{$row->nm_medicamento} {$row->concentracao_medicamento}"); ?></b> — lote <?php print h($row->lote_estoque); ?>
	— validade <?php print data_br($row->dt_vali_estoque) . ' ' . validade_badge($row->dt_vali_estoque); ?> — saldo <b><?php print (int)$row->qt_estoque; ?></b></p>
<form action="?page=movimentar-estoque&id=<?php print $id; ?>" method="POST">
	<input type="hidden" name="acao" value="movimentar">
	<div class="row">
		<div class="col-md-4 mb-3">
			<label>Tipo de movimentação</label>
			<select name="tipo" class="form-control">
				<option>Entrada</option>
				<option>Transferência</option>
				<option>Baixa por avaria</option>
			</select>
		</div>
		<div class="col-md-4 mb-3">
			<label>Quantidade</label>
			<input type="number" name="quantidade" class="form-control" min="1" required>
		</div>
		<div class="col-md-4 mb-3">
			<label>Documento (nota fiscal, guia de transferência...)</label>
			<input type="text" name="documento" class="form-control">
		</div>
	</div>
	<div class="mb-3">
		<label>Observação</label>
		<input type="text" name="observacao" class="form-control" maxlength="255">
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Registrar e assinar</button>
	</div>
</form>
