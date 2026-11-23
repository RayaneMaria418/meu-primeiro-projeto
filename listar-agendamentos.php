<h1><?php print tem_perfil(PAC) ? 'Meus agendamentos' : 'Listar agendamentos'; ?></h1>
<?php
$data = $_GET['data'] ?? (tem_perfil(PAC) ? '' : hoje());
$status = $_GET['status'] ?? '';
$prof = (int)($_GET['funcionario_id'] ?? (tem_perfil(CLINICOS) ? id_funcionario() : 0));

if (!tem_perfil(PAC)) { ?>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="listar-agendamentos">
	<div class="col-md-3"><input type="date" name="data" value="<?php print h($data); ?>" class="form-control"></div>
	<div class="col-md-3">
		<select name="status" class="form-control">
			<option value="">Todos os status</option>
			<?php foreach (['Solicitado', 'Confirmado', 'Em Triagem', 'Em Atendimento', 'Aguardando Medicamento', 'Finalizado', 'Cancelado', 'Ausente'] as $s) print "<option " . sel($s, $status) . ">$s</option>"; ?>
		</select>
	</div>
	<div class="col-md-4"><?php print select_profissionais('funcionario_id', $prof, CLINICOS, false); ?></div>
	<div class="col-md-2"><button type="submit" class="btn btn-outline-primary">Filtrar</button></div>
</form>
<p class="text-muted">Deixe a data em branco para ver todas as datas.</p>
<?php }

$where = [];
$tipos = '';
$params = [];
if (tem_perfil(PAC)) {
    $where[] = 'a.paciente_id = ?'; $tipos .= 'i'; $params[] = id_paciente_logado();
}
if ($data !== '') {
    $where[] = 'a.dt_agendamento = ?'; $tipos .= 's'; $params[] = $data;
}
if ($status !== '') {
    $where[] = 'a.status_agendamento = ?'; $tipos .= 's'; $params[] = $status;
}
if ($prof && !tem_perfil(PAC)) {
    $where[] = '(a.funcionario_id = ? OR a.funcionario_id IS NULL)'; $tipos .= 'i'; $params[] = $prof;
}
$sql = "SELECT a.*, p.nome_paciente, f.nome_funcionario,
               (SELECT x.id_atendimento FROM atendimento x WHERE x.agendamento_id = a.id_agendamento) AS atendimento_id,
               (SELECT 1 FROM triagem t WHERE t.agendamento_id = a.id_agendamento) AS triado
        FROM agendamento AS a
        JOIN paciente AS p ON a.paciente_id = p.id_paciente
        LEFT JOIN funcionario AS f ON a.funcionario_id = f.id_funcionario"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . " ORDER BY a.dt_agendamento" . (tem_perfil(PAC) ? ' DESC' : '') . ", a.hr_agendamento";

$lista = db_lista($sql, $tipos, ...$params);
$qtd = count($lista);

if($qtd > 0){
    print "<p>Encontrou <b>$qtd</b> resultado(s)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr>";
    print "<th>#</th>";
    if (!tem_perfil(PAC)) print "<th>Paciente</th>";
    print "<th>Médico/Funcionário</th>";
    print "<th>Data</th>";
    print "<th>Hora</th>";
    print "<th>Tipo</th>";
    print "<th>Origem</th>";
    print "<th>Status</th>";
    print "<th>Senha</th>";
    print "<th>Ações</th>";
    print "</tr>";

    foreach ($lista as $row) {
        $futuro = strtotime("{$row->dt_agendamento} {$row->hr_agendamento}") > time();
        $meu = (int)$row->funcionario_id === (int)id_funcionario() || $row->funcionario_id === null;
        print "<tr>";
        print "<td>" . $row->id_agendamento . "</td>";
        if (!tem_perfil(PAC)) print "<td>" . h($row->nome_paciente) . "</td>";
        print "<td>" . ($row->nome_funcionario ? h($row->nome_funcionario) : '<i>A definir na triagem</i>') . "</td>";
        print "<td>" . data_br($row->dt_agendamento) . "</td>";
        print "<td>" . hora_br($row->hr_agendamento) . "</td>";
        print "<td>" . h($row->tipo_atendimento) . "</td>";
        print "<td>" . h($row->origem_agendamento) . "</td>";
        print "<td>" . status_badge($row->status_agendamento) . "</td>";
        print "<td>" . h($row->senha_agendamento) . "</td>";
        print "<td class='text-nowrap'>";
        $ativo = in_array($row->status_agendamento, ['Solicitado', 'Confirmado'], true);

        if (tem_perfil(ADM, REC)) {
            if ($row->status_agendamento === 'Confirmado' && $row->dt_agendamento === hoje() && $row->tipo_atendimento !== 'Teleconsulta') {
                print "<button onclick=\"location.href='?page=salvar-agendamento&acao=checkin&id={$row->id_agendamento}';\" class='btn btn-primary'>Check-in</button> ";
            }
            if ($row->status_agendamento === 'Confirmado' && !$futuro) {
                print "<button onclick=\"if(confirm('Registrar que o paciente não compareceu?')){ location.href='?page=salvar-agendamento&acao=ausente&id={$row->id_agendamento}'; }\" class='btn btn-secondary'>Faltou</button> ";
            }
        }
        if (tem_perfil(ADM, REC, PAC) && $ativo && $futuro) {
            print "<button onclick=\"location.href='?page=editar-agendamento&id={$row->id_agendamento}';\" class='btn btn-success'>Editar</button> ";
        }
        if ($row->tipo_atendimento === 'Teleconsulta' && $row->link_teleconsulta && $row->status_agendamento === 'Confirmado' && tem_perfil(PAC)) {
            print "<a href='" . h($row->link_teleconsulta) . "' target='_blank' class='btn btn-primary'>Entrar na teleconsulta</a> ";
        }
        if (tem_perfil(ENF) && $row->status_agendamento === 'Em Triagem' && !$row->triado) {
            print "<button onclick=\"location.href='?page=nova-triagem&ag_id={$row->id_agendamento}';\" class='btn btn-primary'>Triagem</button> ";
        }
        if (tem_perfil(CLINICOS) && $meu) {
            if ($row->status_agendamento === 'Em Atendimento' || ($row->tipo_atendimento === 'Teleconsulta' && $row->status_agendamento === 'Confirmado' && tem_perfil(MED, ENF))) {
                print "<button onclick=\"location.href='?page=novo-atendimento&ag_id={$row->id_agendamento}';\" class='btn btn-primary'>Atender</button> ";
            }
        }
        if (tem_perfil(CLINICOS) && $row->atendimento_id) {
            print "<button onclick=\"location.href='?page=editar-atendimento&id={$row->atendimento_id}';\" class='btn btn-outline-primary'>Atendimento</button> ";
        }
        print "</td>";
        print "</tr>";
    }
    print "</table></div>";
}else{
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
