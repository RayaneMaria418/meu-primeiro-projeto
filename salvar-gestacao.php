<?php
switch ($_REQUEST["acao"] ?? '') {
    case 'cadastrar':
        $paciente_id = (int)$_POST['paciente_id'];
        $dum = $_POST['dum'] ?? '';
        if ($dum === '' || $dum > hoje()) {
            voltar('Informe uma DUM válida.');
            break;
        }
        if (db_valor("SELECT COUNT(*) FROM gestacao WHERE paciente_id = ? AND status_gestacao = 'Em acompanhamento'", 'i', $paciente_id) > 0) {
            voltar('Esta paciente já tem uma gestação em acompanhamento.');
            break;
        }
        $id = db_inserir("INSERT INTO gestacao (paciente_id, dum, dpp, exames_gestacao, observacoes_gestacao) VALUES (?, ?, ?, ?, ?)", 'issss',
            $paciente_id, $dum, calcular_dpp($dum), trim($_POST['exames'] ?? ''), trim($_POST['observacoes'] ?? ''));
        incluir_programa($paciente_id, 'Pré-natal', 'Manual');
        auditoria('Criar', 'Pré-natal', $id, $paciente_id);
        mensagem('Acompanhamento iniciado. DPP calculada: ' . data_br(calcular_dpp($dum)), 'editar-gestacao&id=' . $id);
        break;

    case 'editar':
        $g = db_um("SELECT * FROM gestacao WHERE id_gestacao = ?", 'i', $_POST['id'] ?? 0);
        if (!$g) {
            voltar('Ficha não encontrada.');
            break;
        }
        $dpp = $_POST['dpp'];
        $justificativa = trim($_POST['justificativa'] ?? '');
        // RN-RF05: alterar a DPP exige fundamentação clínica ou laudo
        if ($dpp !== $g->dpp && $justificativa === '') {
            voltar('Para alterar a DPP é obrigatório registrar a fundamentação clínica ou o laudo de ultrassonografia.');
            break;
        }
        $nova_just = $dpp !== $g->dpp
            ? trim(($g->dpp_justificativa ? $g->dpp_justificativa . "\n" : '') . date('d/m/Y H:i') . ' - DPP de ' . data_br($g->dpp) . ' para ' . data_br($dpp) . ': ' . $justificativa)
            : $g->dpp_justificativa;
        $status = isset($_POST['encerrar']) ? 'Encerrada' : 'Em acompanhamento';
        db("UPDATE gestacao SET dpp = ?, dpp_justificativa = ?, exames_gestacao = ?, observacoes_gestacao = ?, status_gestacao = ? WHERE id_gestacao = ?",
            'sssssi', $dpp, $nova_just, trim($_POST['exames'] ?? ''), trim($_POST['observacoes'] ?? ''), $status, $g->id_gestacao);
        auditoria('Editar', 'Pré-natal', $g->id_gestacao, $g->paciente_id, $dpp !== $g->dpp ? 'DPP alterada' : null);
        mensagem('Ficha de pré-natal atualizada', 'editar-gestacao&id=' . $g->id_gestacao);
        break;
}
?>
