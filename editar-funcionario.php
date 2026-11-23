<h1>Editar funcionário</h1>
<?php
$row = db_um("SELECT * FROM funcionario WHERE id_funcionario = ?", 'i', $_REQUEST['id'] ?? 0);
if (!$row) {
    print "<div class='alert alert-danger'>Funcionário não encontrado.</div>";
    return;
}
?>
<form action="?page=salvar-funcionario" method="POST">
	<input type="hidden" name="acao" value="editar">
	<input type="hidden" name="id" value="<?php print $row->id_funcionario; ?>">
	<?php include("form-funcionario.php"); ?>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar Edição</button>
		<a href="?page=escala&id=<?php print $row->id_funcionario; ?>" class="btn btn-outline-primary">Escala e ausências</a>
	</div>
</form>
