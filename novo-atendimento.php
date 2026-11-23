<h1>Registrar Atendimento</h1>
<?php
// Origem: agendamento (presencial ou teleconsulta) ou teleconsulta/contato sem agendamento (paciente_id + tipo)
$agendamento_id = (int)($_REQUEST['ag_id'] ?? 0);
$ag = null;
if ($agendamento_id) {
    $ag = db_um("SELECT a.*, p.nome_paciente, f.nome_funcionario FROM agendamento AS a
                 JOIN paciente AS p ON a.paciente_id = p.id_paciente
                 LEFT JOIN funcionario AS f ON a.funcionario_id = f.id_funcionario
                 WHERE a.id_agendamento = ?", 'i', $agendamento_id);
    if (!$ag) {
        print "<div class='alert alert-danger'>Agendamento não encontrado.</div>";
        return;
    }
    $existente = db_valor("SELECT id_atendimento FROM atendimento WHERE agendamento_id = ?", 'i', $agendamento_id);
    if ($existente) {
        print "<script>location.href='?page=editar-atendimento&id=$existente';</script>";
        return;
    }
    $tele = $ag->tipo_atendimento === 'Teleconsulta';
    $pode = $tele ? $ag->status_agendamento === 'Confirmado' : $ag->status_agendamento === 'Em Atendimento';
    if (!$pode) {
        print "<div class='alert alert-warning'>Este agendamento está com status \"" . h($ag->status_agendamento) . "\". "
            . ($tele ? 'A teleconsulta precisa estar confirmada.' : 'O paciente precisa passar pela triagem antes do atendimento.') . "</div>";
        return;
    }
    $paciente_id = $ag->paciente_id;
    $tipo_registro = $tele ? 'Teleconsulta' : 'Presencial';
} else {
    $paciente_id = (int)($_GET['paciente_id'] ?? 0);
    $tipo_registro = ($_GET['tipo'] ?? '') === 'Contato telefônico' ? 'Contato telefônico' : 'Teleconsulta';
    if (!$paciente_id || !tem_perfil(MED, ENF)) {
        print "<div class='alert alert-danger'>Selecione o paciente pela tela de Teleconsulta.</div>";
        return;
    }
}
$triagem = $ag ? db_um("SELECT * FROM triagem WHERE agendamento_id = ?", 'i', $agendamento_id) : null;

auditoria('Visualizar', 'Prontuário', $paciente_id, $paciente_id, 'Abertura de atendimento');
print cabecalho_paciente($paciente_id);
print alerta_vacinas($paciente_id);

if ($triagem) {
    print "<div class='alert alert-info'><b>Triagem</b> " . risco_badge($triagem->classificacao_risco) . " — PA " . h($triagem->pressao_arterial)
        . " | Glicemia " . h($triagem->glicemia) . " | Temp. " . h($triagem->temperatura) . " °C | Peso " . h($triagem->peso) . " kg | Altura " . h($triagem->altura) . " m"
        . "<br>Queixa: " . h($triagem->queixa_triagem) . "</div>";
}
$anteriores = db_lista("SELECT at.dt_atendimento, at.diagnostico_atendimento, at.cid_atendimento, f.nome_funcionario FROM atendimento at
                        JOIN funcionario f ON f.id_funcionario = at.profissional_id WHERE at.paciente_id = ? ORDER BY at.dt_atendimento DESC LIMIT 3", 'i', $paciente_id);
if ($anteriores) {
    print "<p><b>Últimos atendimentos:</b> ";
    foreach ($anteriores as $a) {
        print data_br($a->dt_atendimento) . " - " . h($a->diagnostico_atendimento) . ($a->cid_atendimento ? " ({$a->cid_atendimento})" : '') . " - " . h($a->nome_funcionario) . "; ";
    }
    print "<a href='?page=prontuario&id=$paciente_id' target='_blank'>abrir prontuário completo</a></p>";
}
?>

<form action="?page=salvar-atendimento" method="POST">
	<input type="hidden" name="acao" value="cadastrar">
	<input type="hidden" name="agendamento_id" value="<?php print $agendamento_id; ?>">
	<input type="hidden" name="paciente_id" value="<?php print $paciente_id; ?>">
	<input type="hidden" name="tipo_registro" value="<?php print h($tipo_registro); ?>">

	<?php if ($tipo_registro !== 'Presencial') { ?>
	<div class="alert alert-light border">
		<b><?php print h($tipo_registro); ?></b> — RN-RF15: confirme a identidade do paciente e o aceite do termo antes de iniciar.
		<?php if ($ag && $ag->link_teleconsulta) print "<br>Sala de vídeo: <a href='" . h($ag->link_teleconsulta) . "' target='_blank'>" . h($ag->link_teleconsulta) . "</a>"; ?>
		<div class="form-check mt-2">
			<input class="form-check-input" type="checkbox" name="identidade" value="1" id="ident" required>
			<label class="form-check-label" for="ident">Identidade do paciente confirmada (nome completo, data de nascimento e CPF/Cartão SUS)</label>
		</div>
		<div class="form-check">
			<input class="form-check-input" type="checkbox" name="consentimento" value="1" id="cons" required>
			<label class="form-check-label" for="cons">Paciente leu e aceitou o termo de consentimento para atendimento remoto</label>
		</div>
	</div>
	<?php } ?>

    <div class="mb-3">
		<label>Queixa Principal</label>
		<textarea name="queixa" class="form-control" rows="3" required><?php print h($triagem->queixa_triagem ?? ''); ?></textarea>
	</div>
    <div class="mb-3">
		<label>Diagnóstico</label>
		<textarea name="diagnostico" class="form-control" rows="3"></textarea>
	</div>
    <div class="mb-3">
		<label>CID-10</label>
		<input type="text" name="cid" class="form-control" maxlength="10" placeholder="Ex.: I10, E11, J11">
		<small class="text-muted">CIDs de hipertensão (I10–I15), diabetes (E10–E14) e gestação (Z32–Z36, O) incluem o paciente automaticamente no programa correspondente.</small>
	</div>
    <div class="mb-3">
		<label>Conduta / Tratamento</label>
		<textarea name="conduta" class="form-control" rows="3"></textarea>
	</div>

	<div class="mb-3">
		<button type="submit" class="btn btn-primary">Salvar Atendimento</button>
	</div>
</form>
