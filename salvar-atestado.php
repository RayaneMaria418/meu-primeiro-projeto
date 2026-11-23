<?php
$at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND profissional_id = ?", 'ii', $_POST['atendimento_id'] ?? 0, id_funcionario());
$tipo = $_POST['tipo'] ?? '';
$dias = ($_POST['dias'] ?? '') === '' ? null : (int)$_POST['dias'];
$cid = strtoupper(trim($_POST['cid'] ?? ''));
$consentimento = isset($_POST['consentimento_cid']) ? 1 : 0;
$texto = trim($_POST['texto'] ?? '');

if (!$at || !in_array($tipo, ['Atestado médico', 'Declaração de comparecimento', 'Laudo'], true)) {
    voltar('Dados inválidos.');
    return;
}
if (empty(usuario()['registro'])) {
    voltar('Apenas profissionais com registro ativo no conselho podem emitir documentos.');
    return;
}
if ($tipo === 'Atestado médico' && !$dias) {
    voltar('Informe os dias de afastamento.');
    return;
}
// RN-RF13: o CID só entra no documento com consentimento expresso do paciente
if ($cid !== '' && !$consentimento) {
    voltar('O CID só pode constar no documento com o consentimento expresso do paciente. Marque a autorização ou apague o CID.');
    return;
}
if (!cid_valido($cid)) {
    voltar('CID-10 inválido.');
    return;
}
$id = db_inserir("INSERT INTO atestado (atendimento_id, paciente_id, profissional_id, tipo_atestado, dias_afastamento, cid_atestado, consentimento_cid, texto_atestado, dt_emissao)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 'iiisisiss',
    $at->id_atendimento, $at->paciente_id, id_funcionario(), $tipo, $dias, $cid ?: null, $consentimento, $texto, agora());
auditoria('Criar', 'Atestado', $id, $at->paciente_id, $tipo);
mensagem("$tipo emitido.", 'documento&tipo=atestado&id=' . $id);
?>
