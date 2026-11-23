<?php
switch ($_REQUEST["acao"] ?? '') {
    case 'incluir-escala':
        $fid = (int)$_POST['funcionario_id'];
        if ($_POST['inicio'] >= $_POST['fim']) {
            voltar('O horário de início deve ser antes do horário de fim.');
            break;
        }
        db("INSERT INTO escala (funcionario_id, dia_semana, hr_inicio, hr_fim) VALUES (?, ?, ?, ?)", 'iiss', $fid, (int)$_POST['dia'], $_POST['inicio'], $_POST['fim']);
        auditoria('Criar', 'Escala', $fid);
        mensagem('Horário incluído', 'escala&id=' . $fid);
        break;

    case 'excluir-escala':
        $e = db_um("SELECT * FROM escala WHERE id_escala = ?", 'i', $_GET['id'] ?? 0);
        if ($e) {
            db("DELETE FROM escala WHERE id_escala = ?", 'i', $e->id_escala);
            auditoria('Excluir', 'Escala', $e->funcionario_id);
        }
        mensagem('Horário removido', 'escala&id=' . ($e->funcionario_id ?? 0));
        break;

    case 'incluir-ausencia':
        $fid = (int)$_POST['funcionario_id'];
        if ($_POST['inicio'] > $_POST['fim']) {
            voltar('A data inicial deve ser anterior à data final.');
            break;
        }
        db("INSERT INTO ausencia (funcionario_id, dt_inicio, dt_fim, motivo, observacao_ausencia) VALUES (?, ?, ?, ?, ?)", 'issss',
            $fid, $_POST['inicio'], $_POST['fim'], $_POST['motivo'], trim($_POST['observacao'] ?? ''));
        auditoria('Criar', 'Ausência', $fid, null, $_POST['motivo']);
        $afetados = db_valor("SELECT COUNT(*) FROM agendamento WHERE funcionario_id = ? AND dt_agendamento BETWEEN ? AND ? AND status_agendamento IN ('Solicitado','Confirmado')",
            'iss', $fid, $_POST['inicio'], $_POST['fim']);
        $aviso = $afetados > 0 ? " Atenção: existem $afetados agendamento(s) neste período que precisam ser remanejados." : '';
        mensagem('Ausência registrada.' . $aviso, 'escala&id=' . $fid);
        break;

    case 'excluir-ausencia':
        $a = db_um("SELECT * FROM ausencia WHERE id_ausencia = ?", 'i', $_GET['id'] ?? 0);
        if ($a) {
            db("DELETE FROM ausencia WHERE id_ausencia = ?", 'i', $a->id_ausencia);
            auditoria('Excluir', 'Ausência', $a->funcionario_id);
        }
        mensagem('Ausência removida', 'escala&id=' . ($a->funcionario_id ?? 0));
        break;
}
?>
