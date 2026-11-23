<?php
$pid = id_paciente_logado();
$pac = db_um("SELECT * FROM paciente WHERE id_paciente = ?", 'i', $pid);
?>
<h1>Olá, <?php print h(explode(' ', $pac->nome_paciente)[0]); ?>!</h1>
<p>
	<a href="?page=novo-agendamento" class="btn btn-primary">Agendar consulta</a>
	<a href="?page=listar-agendamentos" class="btn btn-outline-primary">Meus agendamentos</a>
	<a href="?page=meu-historico" class="btn btn-outline-primary">Meu histórico</a>
	<a href="?page=cartao-vacina&id=<?php print $pid; ?>" class="btn btn-outline-primary">Cartão de vacina</a>
</p>
<?php print alerta_vacinas($pid); ?>
<div class="row">
	<div class="col-md-6">
		<h4>Próximas consultas</h4>
		<?php
		$prox = db_lista("SELECT a.*, f.nome_funcionario FROM agendamento a LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
		                  WHERE a.paciente_id = ? AND a.dt_agendamento >= ? AND a.status_agendamento IN ('Solicitado','Confirmado')
		                  ORDER BY a.dt_agendamento, a.hr_agendamento", 'is', $pid, hoje());
		if ($prox) {
			print "<table class='table table-bordered table-striped'><tr><th>Data</th><th>Hora</th><th>Profissional</th><th>Tipo</th><th></th></tr>";
			foreach ($prox as $a) {
				$extra = $a->link_teleconsulta ? "<a href='" . h($a->link_teleconsulta) . "' target='_blank'>sala de vídeo</a> " : '';
				print "<tr><td>" . data_br($a->dt_agendamento) . "</td><td>" . hora_br($a->hr_agendamento) . "</td><td>" . h($a->nome_funcionario) . "</td><td>" . h($a->tipo_atendimento) . "</td>"
					. "<td class='text-nowrap'>$extra<a href='?page=editar-agendamento&id={$a->id_agendamento}' class='btn btn-success btn-sm'>Remarcar/Cancelar</a></td></tr>";
			}
			print "</table>";
		} else {
			print "<p class='text-muted'>Nenhuma consulta agendada.</p>";
		}
		$receitas = db_valor("SELECT COUNT(*) FROM prescricao WHERE paciente_id = ? AND status_prescricao = 'Pendente'", 'i', $pid);
		if ($receitas) print "<div class='alert alert-info'>Você tem $receitas receita(s) para retirar na farmácia da UBS. Leve um documento com foto.</div>";
		?>
	</div>
	<div class="col-md-6">
		<h4>Avisos</h4>
		<?php
		$avisos = db_lista("SELECT * FROM notificacao WHERE paciente_id = ? ORDER BY dt_notificacao DESC LIMIT 10", 'i', $pid);
		if ($avisos) {
			foreach ($avisos as $n) {
				print "<div class='alert " . ($n->lida ? 'alert-light border' : 'alert-primary') . " py-2'><small>" . datahora_br($n->dt_notificacao) . "</small><br>" . h($n->mensagem) . "</div>";
			}
			// depois de exibidos, os avisos novos passam a constar como lidos
			db("UPDATE notificacao SET lida = 1 WHERE paciente_id = ? AND lida = 0", 'i', $pid);
		} else {
			print "<p class='text-muted'>Nenhum aviso.</p>";
		}
		?>
	</div>
</div>
