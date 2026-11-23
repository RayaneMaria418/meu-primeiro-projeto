<h1>Ficha de pré-natal</h1>
<?php
$g = db_um("SELECT * FROM gestacao WHERE id_gestacao = ?", 'i', $_GET['id'] ?? 0);
if (!$g) {
    print "<div class='alert alert-danger'>Ficha não encontrada.</div>";
    return;
}
auditoria('Visualizar', 'Pré-natal', $g->id_gestacao, $g->paciente_id);
print cabecalho_paciente($g->paciente_id);
$sem = semanas_gestacao($g->dum);
$consultas = db_lista("SELECT a.*, f.nome_funcionario FROM agendamento a LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
                       WHERE a.paciente_id = ? AND a.tipo_atendimento = 'Pré-natal' AND a.dt_agendamento >= ? ORDER BY a.dt_agendamento",
    'is', $g->paciente_id, $g->dum);
?>
<div class="row">
	<div class="col-md-6">
		<p><b>DUM:</b> <?php print data_br($g->dum); ?> — <b>Idade gestacional:</b> <?php print $sem; ?> semanas — <b>DPP:</b> <?php print data_br($g->dpp); ?>
			<?php print $g->status_gestacao === 'Encerrada' ? badge('Encerrada', 'secondary') : ''; ?></p>
		<?php if ($g->dpp_justificativa) print "<div class='alert alert-light border'><b>DPP alterada — fundamentação:</b> " . h($g->dpp_justificativa) . "</div>"; ?>

		<form action="?page=salvar-gestacao" method="POST">
			<input type="hidden" name="acao" value="editar">
			<input type="hidden" name="id" value="<?php print $g->id_gestacao; ?>">
			<div class="mb-3">
				<label>Data provável do parto (DPP)</label>
				<input type="date" name="dpp" value="<?php print h($g->dpp); ?>" class="form-control" required>
			</div>
			<div class="mb-3">
				<label>Fundamentação clínica / laudo de ultrassonografia (obrigatório se alterar a DPP)</label>
				<textarea name="justificativa" class="form-control" rows="2"></textarea>
			</div>
			<div class="mb-3">
				<label>Exames realizados</label>
				<textarea name="exames" class="form-control" rows="3"><?php print h($g->exames_gestacao); ?></textarea>
			</div>
			<div class="mb-3">
				<label>Observações</label>
				<textarea name="observacoes" class="form-control" rows="2"><?php print h($g->observacoes_gestacao); ?></textarea>
			</div>
			<div class="form-check mb-3">
				<input class="form-check-input" type="checkbox" name="encerrar" value="1" id="enc" <?php print marcado($g->status_gestacao === 'Encerrada'); ?>>
				<label class="form-check-label" for="enc">Encerrar acompanhamento (parto ou outro desfecho)</label>
			</div>
			<div class="mb-3">
				<button type="submit" class="btn btn-primary">Salvar Edição</button>
			</div>
		</form>
	</div>
	<div class="col-md-6">
		<h5>Agenda recomendada de consultas</h5>
		<table class="table table-bordered table-sm">
			<tr><th>Semana</th><th>Data prevista</th><th>Situação</th></tr>
			<?php
				foreach (agenda_pre_natal($g->dum) as $c) {
					$situacao = $c['data'] < hoje() ? 'Passada' : 'A agendar';
					foreach ($consultas as $a) {
						if (abs(strtotime($a->dt_agendamento) - strtotime($c['data'])) <= 7 * 86400) {
							$situacao = status_badge($a->status_agendamento) . ' ' . data_br($a->dt_agendamento);
						}
					}
					print "<tr" . ($c['semana'] == $sem ? " class='table-info'" : '') . "><td>{$c['semana']}ª</td><td>" . data_br($c['data']) . "</td><td>$situacao</td></tr>";
				}
			?>
		</table>
		<h5>Exames recomendados</h5>
		<ul>
			<?php foreach (EXAMES_PRE_NATAL as $tri => $ex) print "<li><b>$tri:</b> " . h($ex) . "</li>"; ?>
		</ul>
		<?php if (tem_perfil(MED, ENF)) { ?>
			<p class="text-muted">Para marcar a próxima consulta, peça à recepção um agendamento do tipo "Pré-natal".</p>
		<?php } ?>
	</div>
</div>
