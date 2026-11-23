<h1>Programas prioritários</h1>
<?php
$programa = $_GET['programa'] ?? '';
$opcoes = ['Hipertensão', 'Diabetes', 'Pré-natal', 'Idoso', 'Puericultura'];
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="programas">
	<div class="col-md-4">
		<select name="programa" class="form-control">
			<option value="">Todos os programas</option>
			<?php foreach ($opcoes as $o) print "<option " . sel($o, $programa) . ">" . h($o) . "</option>"; ?>
		</select>
	</div>
	<div class="col-md-2"><button type="submit" class="btn btn-outline-primary">Filtrar</button></div>
</form>

<h5>Incluir paciente em programa</h5>
<form action="?page=salvar-programa" method="POST" class="row g-2 mb-4">
	<input type="hidden" name="acao" value="incluir">
	<input type="hidden" name="voltar" value="programas">
	<div class="col-md-5"><?php print select_pacientes(); ?></div>
	<div class="col-md-4">
		<select name="programa" class="form-control" required>
			<option value="">-- Programa --</option>
			<?php foreach ($opcoes as $o) print "<option>" . h($o) . "</option>"; ?>
		</select>
	</div>
	<div class="col-md-3"><button type="submit" class="btn btn-primary">Incluir</button></div>
</form>
<?php
$sql = "SELECT pp.*, p.nome_paciente, p.dt_nasc_paciente, p.telefone_paciente FROM programa_paciente pp
        JOIN paciente p ON p.id_paciente = pp.paciente_id";
$lista = $programa !== ''
    ? db_lista($sql . " WHERE pp.programa = ? ORDER BY p.nome_paciente", 's', $programa)
    : db_lista($sql . " ORDER BY pp.programa, p.nome_paciente");

if ($lista) {
    print "<p>Encontrou <b>" . count($lista) . "</b> resultado(s)</p>";
    print "<table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>Programa</th><th>Paciente</th><th>Idade</th><th>Telefone</th><th>Inclusão</th><th>Origem</th><th>Ações</th></tr>";
    foreach ($lista as $r) {
        print "<tr><td>" . h($r->programa) . "</td><td>" . h($r->nome_paciente) . "</td><td>" . idade_texto($r->dt_nasc_paciente) . "</td><td>" . h($r->telefone_paciente) . "</td>"
            . "<td>" . data_br($r->dt_inclusao) . "</td><td>" . h($r->origem) . "</td><td class='text-nowrap'>"
            . "<button onclick=\"location.href='?page=prontuario&id={$r->paciente_id}';\" class='btn btn-primary'>Prontuário</button> "
            . "<button onclick=\"if(confirm('Remover o paciente do programa?')){ location.href='?page=salvar-programa&acao=remover&id={$r->id_programa}'; }\" class='btn btn-danger'>Remover</button>"
            . "</td></tr>";
    }
    print "</table>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
