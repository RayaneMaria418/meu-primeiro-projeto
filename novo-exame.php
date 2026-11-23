<h1>Solicitação de exames</h1>
<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 0 AND profissional_id = ?", 'ii', $_GET['at_id'] ?? 0, id_funcionario());
if (!$at) {
    print "<div class='alert alert-danger'>A solicitação de exames é feita dentro de um atendimento em aberto seu.</div>";
    return;
}
print cabecalho_paciente($at->paciente_id);
?>
<form action="?page=salvar-exame" method="POST">
	<input type="hidden" name="atendimento_id" value="<?php print $at->id_atendimento; ?>">
	<div class="row">
		<?php foreach (['Laboratorial', 'Imagem'] as $tipo) { ?>
		<div class="col-md-6 mb-3">
			<h5><?php print $tipo === 'Imagem' ? 'Exames de imagem' : 'Exames laboratoriais'; ?></h5>
			<?php foreach (db_lista("SELECT * FROM exame WHERE tipo_exame = ? ORDER BY nm_exame", 's', $tipo) as $e) { ?>
			<div class="form-check">
				<input class="form-check-input" type="checkbox" name="exames[]" value="<?php print $e->id_exame; ?>" id="ex<?php print $e->id_exame; ?>">
				<label class="form-check-label" for="ex<?php print $e->id_exame; ?>">
					<?php print h($e->nm_exame) . ($e->alto_custo ? ' ' . badge('alto custo', 'danger') : ''); ?>
				</label>
			</div>
			<?php } ?>
		</div>
		<?php } ?>
	</div>
	<div class="mb-3">
		<label>Justificativa clínica (obrigatória para exames de alto custo ou complexidade)</label>
		<textarea name="justificativa" class="form-control" rows="3"></textarea>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Gerar guia de solicitação</button>
		<a href="?page=editar-atendimento&id=<?php print $at->id_atendimento; ?>" class="btn btn-secondary">Voltar ao atendimento</a>
	</div>
</form>
