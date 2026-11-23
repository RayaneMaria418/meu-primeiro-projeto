<h1>Novo agendamento</h1>
<?php
// UC06: escolher profissional, ver os dias e horários livres e confirmar
$paciente_id = tem_perfil(PAC) ? id_paciente_logado() : (int)($_GET['paciente_id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';
$funcionario_id = (int)($_GET['funcionario_id'] ?? 0);
$data = $_GET['data'] ?? '';
?>
<form action="index.php" method="GET">
	<input type="hidden" name="page" value="novo-agendamento">
	<?php if (!tem_perfil(PAC)) { ?>
	<div class="mb-3">
		<label>Paciente</label>
		<?php print select_pacientes('paciente_id', $paciente_id); ?>
	</div>
	<?php } ?>
	<div class="row">
		<div class="col-md-5 mb-3">
			<label>Tipo de atendimento</label>
			<select name="tipo" class="form-control" required onchange="this.form.submit()">
				<option value="">-- Selecione --</option>
				<?php foreach (TIPOS_AGENDAMENTO as $t) print "<option " . sel($t, $tipo) . ">" . h($t) . "</option>"; ?>
			</select>
		</div>
		<div class="col-md-7 mb-3">
			<label>Profissional (especialidade)</label>
			<?php print select_profissionais('funcionario_id', $funcionario_id, $tipo ? cargos_do_tipo($tipo) : CLINICOS); ?>
		</div>
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-outline-primary">Ver dias e horários livres</button>
	</div>
</form>
<?php
if (!$paciente_id || !$tipo || !$funcionario_id) {
    return;
}
$prof = db_um("SELECT * FROM funcionario WHERE id_funcionario = ? AND ativo_funcionario = 1", 'i', $funcionario_id);
if (!$prof || !in_array($prof->cargo_funcionario, cargos_do_tipo($tipo), true)) {
    print "<div class='alert alert-warning'>Este profissional não realiza o tipo de atendimento escolhido.</div>";
    return;
}

// RN-RF06: consulta especializada exige encaminhamento aprovado pela regulação
$encaminhamentos = [];
if ($tipo === 'Consulta especializada') {
    $encaminhamentos = db_lista("SELECT * FROM encaminhamento WHERE paciente_id = ? AND status_encaminhamento = 'Aprovado' AND especialidade = ?",
        'is', $paciente_id, (string)$prof->especialidade_funcionario);
    if (!$encaminhamentos) {
        print "<div class='alert alert-danger'>Consulta especializada exige encaminhamento aprovado pela Regulação para "
            . h($prof->especialidade_funcionario ?: 'esta especialidade') . ". Este paciente não possui encaminhamento aprovado.</div>";
        return;
    }
}

// Calendário com os próximos 30 dias
print "<h5>Dias disponíveis com " . h($prof->nome_funcionario) . "</h5><div class='mb-3'>";
$base = "?page=novo-agendamento&paciente_id=$paciente_id&tipo=" . urlencode($tipo) . "&funcionario_id=$funcionario_id";
for ($i = 0; $i < 30; $i++) {
    $d = date('Y-m-d', strtotime("+$i day"));
    $livres = count(horarios_livres($funcionario_id, $d));
    $rotulo = DIAS_SEMANA[date('N', strtotime($d))] . ' ' . date('d/m', strtotime($d));
    if ($livres > 0) {
        print "<a href='$base&data=$d' class='btn btn-" . ($d === $data ? 'primary' : 'outline-primary') . " btn-sm m-1'>$rotulo<br><small>$livres horário(s)</small></a>";
    } else {
        $motivo = ausencia_na_data($funcionario_id, $d) ? 'ausente' : 'sem horários';
        print "<span class='btn btn-light btn-sm m-1 disabled'>$rotulo<br><small>$motivo</small></span>";
    }
}
print "</div>";

if (!$data) {
    return;
}
$horarios = horarios_livres($funcionario_id, $data);
if (!$horarios) {
    print "<div class='alert alert-warning'>Não há horários livres nesta data. Escolha outro dia.</div>";
    return;
}
?>
<form action="?page=salvar-agendamento" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<input type="hidden" name="paciente_id" value="<?php print $paciente_id; ?>">
	<input type="hidden" name="funcionario_id" value="<?php print $funcionario_id; ?>">
	<input type="hidden" name="tipo" value="<?php print h($tipo); ?>">
	<input type="hidden" name="data_agendamento" value="<?php print h($data); ?>">
	<div class="mb-3">
		<label>Hora do Agendamento (<?php print data_br($data); ?>)</label>
		<select name="hora_agendamento" class="form-control" required>
			<?php foreach ($horarios as $hr) print "<option>$hr</option>"; ?>
		</select>
	</div>
	<?php if ($encaminhamentos) { ?>
	<div class="mb-3">
		<label>Encaminhamento aprovado</label>
		<select name="encaminhamento_id" class="form-control" required>
			<?php foreach ($encaminhamentos as $e) print "<option value='{$e->id_encaminhamento}'>" . h($e->codigo_regulacao . ' - ' . $e->especialidade . ' (' . data_br($e->dt_solicitacao) . ')') . "</option>"; ?>
		</select>
	</div>
	<?php } ?>
	<div class="mb-3">
		<label>Observação</label>
		<input type="text" name="observacao" class="form-control" maxlength="255">
	</div>
	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Agendar</button>
	</div>
</form>
