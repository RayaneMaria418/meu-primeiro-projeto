<h1>Atendimento</h1>
<?php
$row = db_um("SELECT at.*, p.nome_paciente, f.nome_funcionario, f.registro_conselho
              FROM atendimento AS at
              JOIN paciente AS p ON at.paciente_id = p.id_paciente
              JOIN funcionario AS f ON at.profissional_id = f.id_funcionario
              WHERE at.id_atendimento = ?", 'i', $_REQUEST['id'] ?? 0);
if (!$row) {
    print "<div class='alert alert-danger'>Atendimento não encontrado.</div>";
    return;
}
auditoria('Visualizar', 'Atendimento', $row->id_atendimento, $row->paciente_id);
$editavel = !$row->finalizado && (int)$row->profissional_id === (int)id_funcionario();
$triagem = $row->agendamento_id ? db_um("SELECT * FROM triagem WHERE agendamento_id = ?", 'i', $row->agendamento_id) : null;

print cabecalho_paciente($row->paciente_id);
print alerta_vacinas($row->paciente_id);
print "<p><b>" . h($row->tipo_registro) . "</b> em " . datahora_br($row->dt_atendimento) . " com " . h($row->nome_funcionario) . " "
    . ($row->finalizado ? badge('Finalizado e assinado em ' . datahora_br($row->dt_finalizacao), 'success') : badge('Em aberto', 'warning text-dark'))
    . " — <a href='?page=prontuario&id={$row->paciente_id}'>prontuário completo</a></p>";
if ($triagem) {
    print "<div class='alert alert-info'><b>Triagem</b> " . risco_badge($triagem->classificacao_risco) . " — PA " . h($triagem->pressao_arterial)
        . " | Glicemia " . h($triagem->glicemia) . " | Temp. " . h($triagem->temperatura) . " °C | Peso " . h($triagem->peso) . " kg</div>";
}
?>
<div class="row">
	<div class="col-lg-7">
	<?php if ($editavel) { ?>
		<form action="?page=salvar-atendimento" method="POST">
			<input type="hidden" name="acao" value="editar">
		    <input type="hidden" name="id" value="<?php print $row->id_atendimento; ?>">
		    <div class="mb-3">
				<label>Queixa Principal</label>
				<textarea name="queixa" class="form-control" rows="3" required><?php print h($row->queixa_principal); ?></textarea>
			</div>
		    <div class="mb-3">
				<label>Diagnóstico</label>
				<textarea name="diagnostico" class="form-control" rows="3"><?php print h($row->diagnostico_atendimento); ?></textarea>
			</div>
		    <div class="mb-3">
				<label>CID-10</label>
				<input type="text" name="cid" class="form-control" maxlength="10" value="<?php print h($row->cid_atendimento); ?>">
			</div>
		    <div class="mb-3">
				<label>Conduta / Tratamento</label>
				<textarea name="conduta" class="form-control" rows="3"><?php print h($row->conduta_atendimento); ?></textarea>
			</div>
			<div class="mb-3">
				<button type="submit" class="btn btn-primary">Salvar Edição</button>
			</div>
		</form>
	<?php } else { ?>
		<table class="table table-bordered">
			<tr><th>Queixa</th><td><?php print h($row->queixa_principal); ?></td></tr>
			<tr><th>Diagnóstico</th><td><?php print h($row->diagnostico_atendimento) . ($row->cid_atendimento ? ' (CID ' . h($row->cid_atendimento) . ')' : ''); ?></td></tr>
			<tr><th>Conduta</th><td><?php print h($row->conduta_atendimento); ?></td></tr>
		</table>
		<?php
		foreach (db_lista("SELECT a.*, f.nome_funcionario FROM atendimento_adendo a JOIN funcionario f ON f.id_funcionario = a.profissional_id
		                   WHERE a.atendimento_id = ? ORDER BY a.dt_adendo", 'i', $row->id_atendimento) as $ad) {
			print "<div class='alert alert-light border'><b>Adendo</b> " . datahora_br($ad->dt_adendo) . " — " . h($ad->nome_funcionario) . ": " . h($ad->texto_adendo) . "</div>";
		}
		if ($row->finalizado) { ?>
			<form action="?page=salvar-atendimento" method="POST">
				<input type="hidden" name="acao" value="adendo">
				<input type="hidden" name="id" value="<?php print $row->id_atendimento; ?>">
				<div class="mb-3">
					<label>Incluir adendo (o registro finalizado não pode ser alterado)</label>
					<textarea name="texto" class="form-control" rows="3" required></textarea>
				</div>
				<button type="submit" class="btn btn-primary">Assinar adendo</button>
			</form>
		<?php } else {
			print "<div class='alert alert-secondary'>Atendimento em aberto de outro profissional.</div>";
		}
	} ?>
	</div>
	<div class="col-lg-5">
		<h4>Documentos deste atendimento</h4>
		<?php
		$prescricoes = db_lista("SELECT * FROM prescricao WHERE atendimento_id = ?", 'i', $row->id_atendimento);
		foreach ($prescricoes as $pr) {
			print "<p>Receita #{$pr->id_prescricao} " . status_badge($pr->status_prescricao) . " <a href='?page=documento&tipo=receita&id={$pr->id_prescricao}'>ver</a></p>";
		}
		foreach (db_lista("SELECT s.id_solicitacao, e.nm_exame FROM solicitacao_exame s JOIN exame e ON e.id_exame = s.exame_id WHERE s.atendimento_id = ?", 'i', $row->id_atendimento) as $ex) {
			print "<p>Exame: " . h($ex->nm_exame) . " <a href='?page=documento&tipo=exame&id={$ex->id_solicitacao}'>guia</a></p>";
		}
		foreach (db_lista("SELECT * FROM atestado WHERE atendimento_id = ?", 'i', $row->id_atendimento) as $as) {
			print "<p>" . h($as->tipo_atestado) . " <a href='?page=documento&tipo=atestado&id={$as->id_atestado}'>ver</a></p>";
		}
		foreach (db_lista("SELECT * FROM encaminhamento WHERE atendimento_id = ?", 'i', $row->id_atendimento) as $en) {
			print "<p>Encaminhamento: " . h($en->especialidade) . " " . status_badge($en->status_encaminhamento) . ($en->codigo_regulacao ? ' — ' . h($en->codigo_regulacao) : '') . "</p>";
		}
		if (!$prescricoes) print "<p class='text-muted'>Nenhuma receita emitida.</p>";

		if ($editavel) {
			$at = $row->id_atendimento;
			print "<div class='d-grid gap-2 mb-3'>";
			if (tem_perfil(MED, ODO)) print "<a href='?page=nova-prescricao&at_id=$at' class='btn btn-outline-primary'>Prescrever medicamentos</a>";
			if (tem_perfil(MED, ENF)) print "<a href='?page=novo-exame&at_id=$at' class='btn btn-outline-primary'>Solicitar exames</a>";
			if (tem_perfil(MED, ODO)) print "<a href='?page=novo-atestado&at_id=$at' class='btn btn-outline-primary'>Emitir atestado / declaração</a>";
			if (tem_perfil(MED)) print "<a href='?page=novo-encaminhamento&at_id=$at' class='btn btn-outline-primary'>Encaminhar para especialista</a>";
			print "</div>";
			?>
			<form action="?page=salvar-atendimento" method="POST" onsubmit="return confirm('Depois de finalizado, o atendimento não poderá ser alterado (apenas adendos). Confirmar?');">
				<input type="hidden" name="acao" value="finalizar">
				<input type="hidden" name="id" value="<?php print $row->id_atendimento; ?>">
				<button type="submit" class="btn btn-success w-100">Finalizar e assinar atendimento</button>
				<small class="text-muted">Assinatura: <?php print h(usuario()['nome'] . ' - ' . (usuario()['registro'] ?? 'sem registro')); ?></small>
			</form>
		<?php } elseif ($row->finalizado && tem_perfil(MED, ODO) && (int)$row->profissional_id === (int)id_funcionario()) { ?>
			<a href="?page=novo-atestado&at_id=<?php print $row->id_atendimento; ?>" class="btn btn-outline-primary">Emitir atestado / declaração</a>
		<?php } ?>
	</div>
</div>
