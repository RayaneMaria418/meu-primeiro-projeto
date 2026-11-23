<?php
$familia = db_um("SELECT f.* FROM familia f JOIN microarea m ON m.id_microarea = f.microarea_id WHERE f.id_familia = ? AND m.acs_id = ?",
    'ii', $_POST['familia_id'] ?? 0, id_funcionario());
$dt_visita = str_replace('T', ' ', $_POST['dt_visita'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');
$situacao = in_array($_POST['situacao'] ?? '', ['Acompanhada', 'Em risco', 'Ausente'], true) ? $_POST['situacao'] : 'Acompanhada';
$offline = isset($_POST['offline']) ? 1 : 0;

if (!$familia || $motivo === '' || strtotime($dt_visita) === false || strtotime($dt_visita) > time() + 60) {
    voltar('Selecione a família, a data da visita (não pode ser futura) e o motivo.');
    return;
}
// RN-RF21: conta para as metas se registrada em até 48h após a visita.
// Visitas feitas offline contam pelo momento em que foram registradas no aparelho.
$registrado = $offline && !empty($_POST['coletado_em']) ? strtotime($_POST['coletado_em']) : time();
$dentro_prazo = ($registrado - strtotime($dt_visita)) <= 48 * 3600 ? 1 : 0;

$id = db_inserir("INSERT INTO visita_acs (familia_id, acs_id, dt_visita, motivo_visita, pessoas_acompanhadas, condicoes_observadas, situacao_familia, dt_registro, dentro_prazo, registro_offline)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 'iissssssii',
    $familia->id_familia, id_funcionario(), date('Y-m-d H:i:s', strtotime($dt_visita)), $motivo, trim($_POST['pessoas'] ?? ''),
    trim($_POST['condicoes'] ?? ''), $situacao, date('Y-m-d H:i:s', $registrado), $dentro_prazo, $offline);
auditoria('Criar', 'Visita ACS', $id, null, $familia->nome_familia . ($offline ? ' (sincronizada)' : ''));

if ($offline) {
    print "<!--VISITA-SINCRONIZADA-->";
    return;
}
mensagem($dentro_prazo
    ? 'Visita registrada e contabilizada para as metas.'
    : 'Visita registrada, mas fora do prazo de 48 horas: ela não conta para as metas do programa.', 'listar-visitas');
?>
