<?php
// Documentos oficiais para impressão: receita, guia de exames e atestado/declaração/laudo
$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($tipo === 'receita') {
    $doc = db_um("SELECT * FROM prescricao WHERE id_prescricao = ?", 'i', $id);
    $data = $doc->dt_emissao ?? null;
    $titulo = 'RECEITUÁRIO';
} elseif ($tipo === 'exame') {
    $doc = db_um("SELECT * FROM solicitacao_exame WHERE id_solicitacao = ?", 'i', $id);
    $data = $doc->dt_solicitacao ?? null;
    $titulo = 'GUIA DE SOLICITAÇÃO DE EXAMES';
} elseif ($tipo === 'atestado') {
    $doc = db_um("SELECT * FROM atestado WHERE id_atestado = ?", 'i', $id);
    $data = $doc->dt_emissao ?? null;
    $titulo = mb_strtoupper($doc->tipo_atestado ?? 'DOCUMENTO');
} else {
    $doc = null;
}

// Farmacêutico só vê receitas; paciente só vê os próprios documentos (RN-RNF01 / RN-RNF02)
if (!$doc || !pode_ver_paciente($doc->paciente_id) || (tem_perfil(FAR) && $tipo !== 'receita')) {
    print "<div class='alert alert-danger'>Documento não encontrado ou sem permissão de acesso.</div>";
    return;
}
auditoria('Visualizar', 'Documento', $id, $doc->paciente_id, $tipo);

$pac = db_um("SELECT * FROM paciente WHERE id_paciente = ?", 'i', $doc->paciente_id);
$prof = db_um("SELECT * FROM funcionario WHERE id_funcionario = ?", 'i', $doc->profissional_id);
?>
<p class="no-print">
	<button onclick="window.print()" class="btn btn-primary">Imprimir</button>
	<button onclick="history.back()" class="btn btn-secondary">Voltar</button>
</p>
<div class="border p-4 bg-white">
	<div class="text-center mb-3">
		<h4 class="mb-0"><?php print NOME_UNIDADE; ?></h4>
		<small><?php print NOME_SISTEMA; ?></small>
		<h3 class="mt-3"><?php print h($titulo); ?></h3>
	</div>
	<p><b>Paciente:</b> <?php print h($pac->nome_paciente); ?> — <b>Nascimento:</b> <?php print data_br($pac->dt_nasc_paciente); ?>
		<?php print $pac->cpf_paciente ? ' — <b>CPF:</b> ' . cpf_br($pac->cpf_paciente) : ''; ?>
		<?php print $pac->cns_paciente ? ' — <b>Cartão SUS:</b> ' . h($pac->cns_paciente) : ''; ?></p>
	<hr>
	<?php
	if ($tipo === 'receita') {
		$itens = db_lista("SELECT i.*, m.nm_medicamento, m.concentracao_medicamento, m.unidade_medida, m.controlado FROM prescricao_item i
		                   JOIN medicamento m ON m.id_medicamento = i.medicamento_id WHERE i.prescricao_id = ?", 'i', $id);
		print "<ol>";
		foreach ($itens as $i) {
			print "<li class='mb-2'><b>" . h("{$i->nm_medicamento} {$i->concentracao_medicamento}") . "</b> — " . (int)$i->quantidade . ' ' . h(mb_strtolower($i->unidade_medida)) . "(s)"
				. ($i->controlado ? ' ' . badge('controlado', 'danger') : '') . "<br>" . h($i->posologia) . " — por " . h($i->duracao) . "</li>";
		}
		print "</ol>";
		if ($doc->observacao_prescricao) print "<p><b>Orientações:</b> " . nl2br(h($doc->observacao_prescricao)) . "</p>";
		print "<p>Situação na farmácia: " . status_badge($doc->status_prescricao) . "</p>";
		if (array_filter($itens, fn($i) => $i->controlado)) {
			print "<p class='text-danger'><small>Contém medicamento controlado: a dispensação exige a apresentação da receita física em até 30 dias da emissão.</small></p>";
		}
	} elseif ($tipo === 'exame') {
		$exames = db_lista("SELECT s.*, e.nm_exame, e.tipo_exame FROM solicitacao_exame s JOIN exame e ON e.id_exame = s.exame_id
		                    WHERE s.paciente_id = ? AND s.profissional_id = ? AND s.dt_solicitacao = ? ORDER BY e.tipo_exame, e.nm_exame",
			'iis', $doc->paciente_id, $doc->profissional_id, $doc->dt_solicitacao);
		print "<p>Solicito os seguintes exames:</p><ul>";
		foreach ($exames as $e) print "<li>" . h($e->nm_exame) . " <small>(" . h($e->tipo_exame) . ")</small></li>";
		print "</ul>";
		if ($doc->justificativa_exame) print "<p><b>Justificativa clínica:</b> " . nl2br(h($doc->justificativa_exame)) . "</p>";
	} else {
		if ($doc->tipo_atestado === 'Atestado médico') {
			print "<p>Atesto, para os devidos fins, que o(a) paciente acima foi atendido(a) nesta unidade em " . data_br($doc->dt_emissao)
				. " e necessita de <b>" . (int)$doc->dias_afastamento . " dia(s)</b> de afastamento de suas atividades a partir desta data.</p>";
		} elseif ($doc->tipo_atestado === 'Declaração de comparecimento') {
			print "<p>Declaro, para os devidos fins, que o(a) paciente acima compareceu a esta unidade de saúde em " . datahora_br($doc->dt_emissao) . " para atendimento.</p>";
		}
		if ($doc->texto_atestado) print "<p>" . nl2br(h($doc->texto_atestado)) . "</p>";
		if ($doc->cid_atestado && $doc->consentimento_cid) print "<p><b>CID-10:</b> " . h($doc->cid_atestado) . " <small>(incluído com autorização expressa do paciente)</small></p>";
	}
	?>
	<div class="mt-5 text-center">
		<p class="mb-0">______________________________________</p>
		<p class="mb-0"><b><?php print h($prof->nome_funcionario); ?></b> — <?php print h($prof->registro_conselho); ?></p>
		<small>Documento assinado eletronicamente em <?php print datahora_br($data); ?> — nº <?php print strtoupper(substr($tipo, 0, 3)) . '-' . str_pad($id, 6, '0', STR_PAD_LEFT); ?></small>
	</div>
</div>
