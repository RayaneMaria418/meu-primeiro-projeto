<?php
$mv = db_um("SELECT mv.*, e.lote_estoque, e.fabricante_estoque, e.dt_vali_estoque, m.nm_medicamento, m.concentracao_medicamento, m.unidade_medida,
                    f.nome_funcionario, f.registro_conselho
             FROM movimentacao_estoque mv
             JOIN estoque e ON e.id_estoque = mv.estoque_id
             JOIN medicamento m ON m.id_medicamento = e.medicamento_id
             LEFT JOIN usuario u ON u.id_usuario = mv.usuario_id
             LEFT JOIN funcionario f ON f.id_funcionario = u.funcionario_id
             WHERE mv.id_movimentacao = ?", 'i', $_GET['id'] ?? 0);
if (!$mv) {
    print "<div class='alert alert-danger'>Movimentação não encontrada.</div>";
    return;
}
// Confere a assinatura recalculando o HMAC com os dados gravados
$valida = $mv->assinatura && hash_equals($mv->assinatura, assinar("{$mv->estoque_id}|{$mv->tipo_movimentacao}|{$mv->quantidade}|{$mv->usuario_id}|{$mv->dt_movimentacao}"));
?>
<p class="no-print">
	<button onclick="window.print()" class="btn btn-primary">Imprimir</button>
	<a href="?page=listar-movimentacoes" class="btn btn-secondary">Voltar</a>
</p>
<div class="border p-4 bg-white">
	<div class="text-center mb-3">
		<h4 class="mb-0"><?php print NOME_UNIDADE; ?> — Farmácia</h4>
		<h3 class="mt-2">COMPROVANTE DE MOVIMENTAÇÃO DE ESTOQUE Nº <?php print str_pad($mv->id_movimentacao, 6, '0', STR_PAD_LEFT); ?></h3>
	</div>
	<table class="table table-bordered">
		<tr><th>Data / hora</th><td><?php print datahora_br($mv->dt_movimentacao); ?></td><th>Tipo</th><td><?php print h($mv->tipo_movimentacao); ?></td></tr>
		<tr><th>Item</th><td><?php print h("{$mv->nm_medicamento} {$mv->concentracao_medicamento} ({$mv->unidade_medida})"); ?></td><th>Quantidade</th><td><?php print (int)$mv->quantidade; ?></td></tr>
		<tr><th>Lote / fabricante</th><td><?php print h("{$mv->lote_estoque} / {$mv->fabricante_estoque}"); ?></td><th>Validade</th><td><?php print data_br($mv->dt_vali_estoque); ?></td></tr>
		<tr><th>Documento</th><td><?php print h($mv->documento); ?></td><th>Observação</th><td><?php print h($mv->observacao_movimentacao); ?></td></tr>
		<tr><th>Responsável técnico</th><td colspan="3"><?php print h(trim(($mv->nome_funcionario ?? '') . ' ' . ($mv->registro_conselho ?? ''))); ?></td></tr>
	</table>
	<?php if ($valida) { ?>
		<p class="text-success mb-0"><b>Assinatura eletrônica válida</b> (HMAC-SHA256): <code><?php print h($mv->assinatura); ?></code></p>
	<?php } elseif ($mv->assinatura) { ?>
		<p class="text-danger mb-0"><b>Atenção:</b> a assinatura não confere com os dados gravados (registro alterado após a assinatura).</p>
	<?php } else { ?>
		<p class="text-muted mb-0">Movimentação da carga inicial do sistema (sem assinatura eletrônica).</p>
	<?php } ?>
</div>
