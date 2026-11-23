<h1>Novo funcionário</h1>
<form action="?page=salvar-funcionario" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<?php $row = null; include("form-funcionario.php"); ?>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Enviar</button>
	</div>
</form>
