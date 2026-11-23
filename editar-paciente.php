<h1>Editar paciente</h1>
<?php
    $pac = db_um("SELECT * FROM paciente WHERE id_paciente = ?", 'i', $_REQUEST["id"] ?? 0);
    if (!$pac) {
        print "<div class='alert alert-danger'>Paciente não encontrado.</div>";
        return;
    }
?>
<form action="?page=salvar" method="POST">
    <input type="hidden" name="acao" value="editar">
    <input type="hidden" name="id" value="<?php print $pac->id_paciente; ?>">
    <?php include("form-paciente.php"); ?>
    <div class="mb-3">
        <button type="submit" class="btn btn-primary">Salvar Edição</button>
    </div>
</form>
