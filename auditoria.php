<h1>Auditoria de acesso e alterações</h1>
<p class="text-muted">Registros inalteráveis: o banco de dados recusa qualquer alteração ou exclusão nesta tabela.</p>
<?php
$de = $_GET['de'] ?? date('Y-m-d', strtotime('-7 days'));
$ate = $_GET['ate'] ?? hoje();
$usuario_filtro = trim($_GET['usuario'] ?? '');
$paciente_filtro = (int)($_GET['paciente_id'] ?? 0);
$acao = $_GET['acao'] ?? '';
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="auditoria">
	<div class="col-md-2"><input type="date" name="de" value="<?php print h($de); ?>" class="form-control"></div>
	<div class="col-md-2"><input type="date" name="ate" value="<?php print h($ate); ?>" class="form-control"></div>
	<div class="col-md-2"><input type="text" name="usuario" value="<?php print h($usuario_filtro); ?>" class="form-control" placeholder="Usuário"></div>
	<div class="col-md-3"><?php print select_pacientes('paciente_id', $paciente_filtro, false); ?></div>
	<div class="col-md-2">
		<select name="acao" class="form-control">
			<option value="">Todas as ações</option>
			<?php foreach (db_lista("SELECT DISTINCT acao FROM auditoria ORDER BY acao") as $a) print "<option " . sel($a->acao, $acao) . ">" . h($a->acao) . "</option>"; ?>
		</select>
	</div>
	<div class="col-md-1"><button type="submit" class="btn btn-outline-primary">Filtrar</button></div>
</form>
<?php
$where = ['DATE(a.dt_registro) BETWEEN ? AND ?'];
$tipos = 'ss';
$params = [$de, $ate];
if ($usuario_filtro !== '') { $where[] = 'a.nome_usuario LIKE ?'; $tipos .= 's'; $params[] = "%$usuario_filtro%"; }
if ($paciente_filtro) { $where[] = 'a.paciente_id = ?'; $tipos .= 'i'; $params[] = $paciente_filtro; }
if ($acao !== '') { $where[] = 'a.acao = ?'; $tipos .= 's'; $params[] = $acao; }
$lista = db_lista("SELECT a.*, p.nome_paciente FROM auditoria a LEFT JOIN paciente p ON p.id_paciente = a.paciente_id
                   WHERE " . implode(' AND ', $where) . " ORDER BY a.id_auditoria DESC LIMIT 500", $tipos, ...$params);
if ($lista) {
    print "<p>Encontrou <b>" . count($lista) . "</b> registro(s) (máximo 500)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover table-sm'>";
    print "<tr><th>Data/hora</th><th>Usuário</th><th>Perfil</th><th>IP</th><th>Ação</th><th>Registro</th><th>Paciente</th><th>Detalhes</th></tr>";
    foreach ($lista as $a) {
        print "<tr><td>" . datahora_br($a->dt_registro) . "</td><td>" . h($a->nome_usuario ?? '(não autenticado)') . "</td><td>" . h($a->perfil_usuario) . "</td>"
            . "<td>" . h($a->ip) . "</td><td>" . h($a->acao) . "</td><td>" . h($a->entidade) . ($a->registro_id ? " #{$a->registro_id}" : '') . "</td>"
            . "<td>" . h($a->nome_paciente) . "</td><td>" . h($a->detalhes) . "</td></tr>";
    }
    print "</table></div>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
