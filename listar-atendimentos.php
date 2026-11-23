<h1>Histórico de Atendimentos</h1>
<?php
$meus = ($_GET['meus'] ?? '1') === '1';
$de = $_GET['de'] ?? date('Y-m-d', strtotime('-30 days'));
$ate = $_GET['ate'] ?? hoje();
?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="listar-atendimentos">
	<div class="col-md-3"><input type="date" name="de" value="<?php print h($de); ?>" class="form-control"></div>
	<div class="col-md-3"><input type="date" name="ate" value="<?php print h($ate); ?>" class="form-control"></div>
	<div class="col-md-3">
		<select name="meus" class="form-control">
			<option value="1" <?php print sel('1', $meus ? '1' : '0'); ?>>Somente meus atendimentos</option>
			<option value="0" <?php print sel('0', $meus ? '1' : '0'); ?>>Todos os profissionais</option>
		</select>
	</div>
	<div class="col-md-3"><button type="submit" class="btn btn-outline-primary">Filtrar</button></div>
</form>
<?php
$sql = "SELECT at.id_atendimento, at.dt_atendimento, at.tipo_registro, at.queixa_principal, at.diagnostico_atendimento, at.cid_atendimento, at.finalizado,
               at.paciente_id, p.nome_paciente, f.nome_funcionario
        FROM atendimento AS at
        JOIN paciente AS p ON at.paciente_id = p.id_paciente
        JOIN funcionario AS f ON at.profissional_id = f.id_funcionario
        WHERE DATE(at.dt_atendimento) BETWEEN ? AND ?" . ($meus ? " AND at.profissional_id = ?" : '') . "
        ORDER BY at.dt_atendimento DESC";
$lista = $meus ? db_lista($sql, 'ssi', $de, $ate, id_funcionario()) : db_lista($sql, 'ss', $de, $ate);
$qtd = count($lista);

if($qtd > 0){
    print "<p>Encontrou <b>$qtd</b> resultado(s)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr>";
    print "<th>#</th>";
    print "<th>Data</th>";
    print "<th>Paciente</th>";
    print "<th>Médico</th>";
    print "<th>Tipo</th>";
    print "<th>Queixa</th>";
    print "<th>Diagnóstico</th>";
    print "<th>Situação</th>";
    print "<th>Ações</th>";
    print "</tr>";

    foreach ($lista as $row) {
        print "<tr>";
        print "<td>" . $row->id_atendimento . "</td>";
        print "<td>" . datahora_br($row->dt_atendimento) . "</td>";
        print "<td>" . h($row->nome_paciente) . "</td>";
        print "<td>" . h($row->nome_funcionario) . "</td>";
        print "<td>" . h($row->tipo_registro) . "</td>";
        print "<td>" . h($row->queixa_principal) . "</td>";
        print "<td>" . h($row->diagnostico_atendimento) . ($row->cid_atendimento ? ' (' . h($row->cid_atendimento) . ')' : '') . "</td>";
        print "<td>" . ($row->finalizado ? badge('Finalizado', 'success') : badge('Em aberto', 'warning text-dark')) . "</td>";
        print "<td class='text-nowrap'>
                 <button onclick=\"location.href='?page=editar-atendimento&id=" . $row->id_atendimento . "';\" class='btn btn-success'>Abrir</button>
                 <button onclick=\"location.href='?page=prontuario&id=" . $row->paciente_id . "';\" class='btn btn-primary'>Prontuário</button>
               </td>";
        print "</tr>";
    }
    print "</table></div>";
}else{
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
