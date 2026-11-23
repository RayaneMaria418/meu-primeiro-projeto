<h1>Fila de atendimento</h1>
<p>
	<a href="?page=painel" target="_blank" class="btn btn-outline-primary">Abrir painel de chamada (TV)</a>
	<?php if (tem_perfil(ADM, REC)) { ?><a href="?page=demanda-espontanea" class="btn btn-outline-primary">Demanda espontânea</a><?php } ?>
</p>
<?php
// UC08 - urgência extrema: a equipe é avisada imediatamente
$emerg = db_lista("SELECT a.senha_agendamento, p.nome_paciente, t.classificacao_risco FROM agendamento a
                   JOIN paciente p ON p.id_paciente = a.paciente_id JOIN triagem t ON t.agendamento_id = a.id_agendamento
                   WHERE a.status_agendamento = 'Em Atendimento' AND t.classificacao_risco IN ('Vermelho','Laranja')
                     AND NOT EXISTS (SELECT 1 FROM atendimento x WHERE x.agendamento_id = a.id_agendamento AND x.finalizado = 1)");
foreach ($emerg as $e) {
    print "<div class='alert alert-danger'><b>Atendimento imediato:</b> " . h($e->nome_paciente) . " (senha " . h($e->senha_agendamento) . ") — risco "
        . risco_badge($e->classificacao_risco) . ". Este paciente passa na frente da fila regular.</div>";
}
?>
<div class="row">
	<div class="col-lg-6">
		<h4>Acolhimento / Triagem</h4>
		<p class="text-muted">Ordem de chamada: 2 senhas preferenciais para 1 normal (RN-RF09).</p>
		<?php if (tem_perfil(ENF)) { ?>
		<form action="?page=salvar-fila" method="POST" class="mb-2">
			<input type="hidden" name="acao" value="chamar-triagem">
			<button type="submit" class="btn btn-primary">Chamar próximo</button>
		</form>
		<?php }
		$triagem = fila_triagem();
		if ($triagem) {
			print "<table class='table table-bordered table-striped table-hover'><tr><th>Ordem</th><th>Senha</th><th>Paciente</th><th>Tipo</th><th>Chegada</th><th>Situação</th>" . (tem_perfil(ENF) ? "<th>Ações</th>" : '') . "</tr>";
			foreach ($triagem as $i => $a) {
				$situacao = $a->chamado_em ? badge('Em Chamada', 'info text-dark') : 'Aguardando';
				print "<tr><td>" . ($i + 1) . "º</td><td>" . h($a->senha_agendamento) . " " . ($a->prioridade_agendamento === 'Preferencial' ? badge('P', 'warning text-dark') : '') . "</td>"
					. "<td>" . h($a->nome_paciente) . "</td><td>" . h($a->tipo_atendimento) . "</td><td>" . date('H:i', strtotime($a->dt_checkin)) . "</td><td>$situacao</td>";
				if (tem_perfil(ENF)) {
					print "<td class='text-nowrap'><form action='?page=salvar-fila' method='POST' class='d-inline'><input type='hidden' name='acao' value='chamar-triagem'>
					       <input type='hidden' name='id' value='{$a->id_agendamento}'><button class='btn btn-outline-primary btn-sm'>Chamar</button></form>
					       <button onclick=\"location.href='?page=nova-triagem&ag_id={$a->id_agendamento}';\" class='btn btn-primary btn-sm'>Triagem</button></td>";
				}
				print "</tr>";
			}
			print "</table>";
		} else {
			print "<p class='alert alert-secondary'>Ninguém aguardando triagem.</p>";
		}
		?>
	</div>
	<div class="col-lg-6">
		<h4>Consultório<?php print tem_perfil(CLINICOS) ? ' — ' . h(usuario()['nome']) : ''; ?></h4>
		<p class="text-muted">Ordem: classificação de risco (Vermelho e Laranja primeiro), depois preferencial e horário da triagem (RN-RF08).</p>
		<?php if (tem_perfil(MED, ODO, ENF)) { ?>
		<form action="?page=salvar-fila" method="POST" class="mb-2">
			<input type="hidden" name="acao" value="chamar-consultorio">
			<button type="submit" class="btn btn-primary">Chamar próximo</button>
		</form>
		<?php }
		$consultorio = fila_consultorio(tem_perfil(CLINICOS) ? id_funcionario() : 0);
		if (tem_perfil(ADM, REC)) {
			$consultorio = db_lista("SELECT a.*, p.nome_paciente, t.classificacao_risco, f.nome_funcionario, NULL AS chamado_em, NULL AS atendimento_id
			                         FROM agendamento a JOIN paciente p ON p.id_paciente = a.paciente_id JOIN triagem t ON t.agendamento_id = a.id_agendamento
			                         LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
			                         WHERE a.status_agendamento = 'Em Atendimento' ORDER BY FIELD(t.classificacao_risco,'Vermelho','Laranja','Amarelo','Verde','Azul'), t.dt_triagem");
		}
		if ($consultorio) {
			print "<table class='table table-bordered table-striped table-hover'><tr><th>Senha</th><th>Paciente</th><th>Risco</th><th>Profissional</th><th>Situação</th>" . (tem_perfil(CLINICOS) ? "<th>Ações</th>" : '') . "</tr>";
			foreach ($consultorio as $a) {
				$situacao = $a->atendimento_id ? badge('Em atendimento', 'warning text-dark') : ($a->chamado_em ? badge('Em Chamada', 'info text-dark') : 'Aguardando');
				print "<tr><td>" . h($a->senha_agendamento) . "</td><td>" . h($a->nome_paciente) . "</td><td>" . risco_badge($a->classificacao_risco) . "</td>"
					. "<td>" . ($a->nome_funcionario ? h($a->nome_funcionario) : '<i>Qualquer médico</i>') . "</td><td>$situacao</td>";
				if (tem_perfil(CLINICOS)) {
					print "<td class='text-nowrap'><form action='?page=salvar-fila' method='POST' class='d-inline'><input type='hidden' name='acao' value='chamar-consultorio'>
					       <input type='hidden' name='id' value='{$a->id_agendamento}'><button class='btn btn-outline-primary btn-sm'>Chamar</button></form>
					       <button onclick=\"location.href='?page=novo-atendimento&ag_id={$a->id_agendamento}';\" class='btn btn-primary btn-sm'>Atender</button></td>";
				}
				print "</tr>";
			}
			print "</table>";
		} else {
			print "<p class='alert alert-secondary'>Ninguém aguardando atendimento.</p>";
		}
		?>
	</div>
</div>
