<h1>Novo paciente</h1>
<?php
// UC01 - passo 2: verificar se o paciente já tem cadastro (CPF ou Cartão SUS)
$doc = so_digitos($_GET['verificar'] ?? '');
$pac = null;
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="novo">
	<div class="col-md-6">
		<input type="text" name="verificar" value="<?php print h($doc); ?>" class="form-control" placeholder="Digite o CPF ou o Cartão SUS para verificar o cadastro" required>
	</div>
	<div class="col-md-3">
		<button type="submit" class="btn btn-outline-primary">Verificar cadastro</button>
	</div>
</form>
<?php
if ($doc !== '') {
    $existe = db_um("SELECT id_paciente, nome_paciente FROM paciente WHERE cpf_paciente = ? OR cns_paciente = ?", 'ss', $doc, $doc);
    if ($existe) {
        print "<div class='alert alert-info'>Já existe um cadastro para este documento: <b>" . h($existe->nome_paciente) . "</b>.
               <a href='?page=editar&id={$existe->id_paciente}' class='alert-link'>Abrir o cadastro para atualizar</a>.</div>";
        return;
    }
    print "<div class='alert alert-success'>Nenhum cadastro encontrado. Preencha os dados abaixo.</div>";
    $pac = (object)[strlen($doc) === 15 ? 'cns_paciente' : 'cpf_paciente' => $doc];
}
?>
<form action="?page=salvar" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<?php include("form-paciente.php"); ?>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Enviar</button>
	</div>
</form>
