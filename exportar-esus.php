<?php
// RF23 / RN-RF23: exporta fichas de atendimento finalizadas; só entram fichas com CPF/CNS 100% válidos
$de = $_GET['de'] ?? date('Y-m-01');
$ate = $_GET['ate'] ?? hoje();

$fichas = db_lista("SELECT at.*, p.nome_paciente, p.cpf_paciente, p.cns_paciente, p.dt_nasc_paciente, p.sexo_paciente,
                           f.nome_funcionario, f.cpf_funcionario, f.cns_funcionario, f.cargo_funcionario
                    FROM atendimento at
                    JOIN paciente p ON p.id_paciente = at.paciente_id
                    JOIN funcionario f ON f.id_funcionario = at.profissional_id
                    WHERE at.finalizado = 1 AND DATE(at.dt_atendimento) BETWEEN ? AND ? ORDER BY at.dt_atendimento", 'ss', $de, $ate);
$validas = [];
$pendentes = [];
foreach ($fichas as $f) {
    $erros = [];
    if (!cpf_valido($f->cpf_paciente) && !cns_valido($f->cns_paciente)) $erros[] = 'paciente sem CPF/CNS válido';
    if (!cpf_valido($f->cpf_funcionario) && !cns_valido($f->cns_funcionario)) $erros[] = 'profissional sem CPF/CNS válido';
    if ($erros) {
        $f->erros = $erros;
        $pendentes[] = $f;
    } else {
        $validas[] = $f;
    }
}

if (defined('MODO_DOWNLOAD')) {
    auditoria('Exportar', 'e-SUS', null, null, count($validas) . " fichas de $de a $ate");
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><loteFichasAtendimento/>');
    $xml->addAttribute('unidade', NOME_UNIDADE);
    $xml->addAttribute('periodoInicio', $de);
    $xml->addAttribute('periodoFim', $ate);
    $xml->addAttribute('geradoEm', date('c'));
    foreach ($validas as $f) {
        $ficha = $xml->addChild('fichaAtendimentoIndividual');
        $ficha->addAttribute('uuid', 'AT-' . str_pad($f->id_atendimento, 8, '0', STR_PAD_LEFT));
        $prof = $ficha->addChild('profissional');
        $prof->addChild('cns', $f->cns_funcionario ?: '');
        $prof->addChild('cpf', $f->cpf_funcionario);
        $prof->addChild('cbo', ['Medico' => '225142', 'Enfermeiro' => '223565', 'Odontologo' => '223293'][$f->cargo_funcionario] ?? '');
        $cid = $ficha->addChild('cidadao');
        $cid->addChild('cns', $f->cns_paciente ?: '');
        $cid->addChild('cpf', $f->cpf_paciente ?: '');
        $cid->addChild('dataNascimento', $f->dt_nasc_paciente);
        $cid->addChild('sexo', $f->sexo_paciente);
        $ficha->addChild('dataAtendimento', date('Y-m-d\TH:i:s', strtotime($f->dt_atendimento)));
        $ficha->addChild('tipoAtendimento', htmlspecialchars($f->tipo_registro));
        $ficha->addChild('cid10', $f->cid_atendimento ?: '');
    }
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="esus_' . $de . '_' . $ate . '.xml"');
    print $xml->asXML();
    return;
}
?>
<h1>Exportação para o e-SUS APS</h1>
<p class="text-muted">Gera o arquivo (XML) com as fichas de atendimento finalizadas do período. Fichas com documentos inválidos ficam de fora até serem corrigidas.</p>
<form action="index.php" method="GET" class="row g-2 mb-3">
	<input type="hidden" name="page" value="exportar-esus">
	<div class="col-md-3"><input type="date" name="de" value="<?php print h($de); ?>" class="form-control"></div>
	<div class="col-md-3"><input type="date" name="ate" value="<?php print h($ate); ?>" class="form-control"></div>
	<div class="col-md-3"><button type="submit" class="btn btn-outline-primary">Validar fichas</button></div>
</form>
<div class="row">
	<div class="col-md-6">
		<h4>Fichas válidas: <?php print count($validas); ?></h4>
		<?php if ($validas) { ?>
			<a href="?page=esus-xml&de=<?php print h($de); ?>&ate=<?php print h($ate); ?>" class="btn btn-success mb-3">Gerar arquivo de exportação</a>
			<table class="table table-bordered table-striped table-sm"><tr><th>Ficha</th><th>Data</th><th>Profissional</th></tr>
			<?php foreach ($validas as $f) print "<tr><td>AT-" . str_pad($f->id_atendimento, 8, '0', STR_PAD_LEFT) . "</td><td>" . datahora_br($f->dt_atendimento) . "</td><td>" . h($f->nome_funcionario) . "</td></tr>"; ?>
			</table>
		<?php } ?>
	</div>
	<div class="col-md-6">
		<h4>Relatório de pendências: <?php print count($pendentes); ?></h4>
		<?php if ($pendentes) { ?>
			<table class="table table-bordered table-striped table-sm"><tr><th>Ficha</th><th>Paciente</th><th>Profissional</th><th>Problema</th></tr>
			<?php foreach ($pendentes as $f) print "<tr><td>AT-" . str_pad($f->id_atendimento, 8, '0', STR_PAD_LEFT) . "</td><td>" . h($f->nome_paciente) . "</td><td>" . h($f->nome_funcionario) . "</td><td class='text-danger'>" . h(implode('; ', $f->erros)) . "</td></tr>"; ?>
			</table>
		<?php } else { ?>
			<p class="text-success">Nenhuma pendência no período.</p>
		<?php } ?>
	</div>
</div>
