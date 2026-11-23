<h1>Vincular paciente a família e microárea</h1>
<?php
// UC03: operador informa o CEP, o sistema sugere a microárea/equipe e confirma a inclusão no grupo familiar
$paciente_id = (int)($_GET['paciente_id'] ?? 0);
$cep = so_digitos($_GET['cep'] ?? '');
if ($paciente_id && !$cep) {
    $cep = (string)db_valor("SELECT cep_paciente FROM paciente WHERE id_paciente = ?", 'i', $paciente_id);
}
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="vincular-familia">
	<div class="col-md-6"><?php print select_pacientes('paciente_id', $paciente_id); ?></div>
	<div class="col-md-3"><input type="text" name="cep" value="<?php print h($cep); ?>" class="form-control" placeholder="CEP da residência" required></div>
	<div class="col-md-3"><button type="submit" class="btn btn-outline-primary">Buscar</button></div>
</form>
<?php
if ($paciente_id && $cep) {
    $familias = db_lista("SELECT f.*, m.nome_microarea, m.equipe_esf,
                                 (SELECT GROUP_CONCAT(p.nome_paciente SEPARATOR ', ') FROM paciente p WHERE p.familia_id = f.id_familia) AS membros
                          FROM familia f JOIN microarea m ON m.id_microarea = f.microarea_id
                          WHERE f.cep_familia = ? ORDER BY f.nome_familia", 's', $cep);
    if ($familias) {
        print "<div class='alert alert-info'>Encontramos família(s) cadastrada(s) neste CEP. Microárea sugerida: <b>"
            . h($familias[0]->nome_microarea . ' - ' . $familias[0]->equipe_esf) . "</b>.</div>";
        print "<table class='table table-bordered table-striped table-hover'><tr><th>Família</th><th>Endereço</th><th>Microárea</th><th>Membros</th><th>Ação</th></tr>";
        foreach ($familias as $f) {
            print "<tr><td>" . h($f->nome_familia) . "</td><td>" . h($f->endereco_familia) . "</td><td>" . h($f->nome_microarea) . "</td><td>" . h($f->membros) . "</td>
                   <td><form action='?page=salvar-familia' method='POST'>
                         <input type='hidden' name='acao' value='vincular'>
                         <input type='hidden' name='paciente_id' value='$paciente_id'>
                         <input type='hidden' name='familia_id' value='{$f->id_familia}'>
                         <button type='submit' class='btn btn-success'>Incluir neste grupo familiar</button>
                       </form></td></tr>";
        }
        print "</table>";
    } else {
        $end = db_valor("SELECT endereco_paciente FROM paciente WHERE id_paciente = ?", 'i', $paciente_id);
        print "<div class='alert alert-warning'>Nenhuma família cadastrada neste CEP.
               <a class='alert-link' href='?page=nova-familia&paciente_id=$paciente_id&cep=" . urlencode($cep) . "&endereco=" . urlencode((string)$end) . "'>Cadastrar a família deste endereço</a>
               (o sistema sugere a microárea pela região do CEP).</div>";
    }
}
?>
