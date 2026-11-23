<?php
switch ($_REQUEST["acao"] ?? '') {
    case 'incluir':
        $paciente_id = (int)$_POST['paciente_id'];
        $programa = preg_replace('/ \(sugerido.*$/', '', $_POST['programa']);
        if (!in_array($programa, ['Hipertensão', 'Diabetes', 'Pré-natal', 'Idoso', 'Puericultura'], true)) {
            voltar('Programa inválido.');
            break;
        }
        incluir_programa($paciente_id, $programa, 'Manual');
        auditoria('Criar', 'Programa', null, $paciente_id, $programa);
        mensagem('Paciente incluído no programa ' . $programa, $_POST['voltar'] ?? 'programas');
        break;

    case 'remover':
        $p = db_um("SELECT * FROM programa_paciente WHERE id_programa = ?", 'i', $_REQUEST['id'] ?? 0);
        if ($p) {
            db("DELETE FROM programa_paciente WHERE id_programa = ?", 'i', $p->id_programa);
            auditoria('Excluir', 'Programa', $p->id_programa, $p->paciente_id, $p->programa);
        }
        mensagem('Paciente removido do programa', 'programas');
        break;
}
?>
