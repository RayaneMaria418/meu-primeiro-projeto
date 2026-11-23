<?php
switch ($_REQUEST["acao"] ?? '') {
    case 'cadastrar':
        if (!exigir(MED)) break;
        $at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 0 AND profissional_id = ?", 'ii', $_POST['atendimento_id'] ?? 0, id_funcionario());
        $esp = trim($_POST['especialidade'] ?? '');
        $resumo = trim($_POST['resumo'] ?? '');
        $prioridade = in_array($_POST['prioridade'] ?? '', ['Eletiva', 'Prioritária', 'Urgente'], true) ? $_POST['prioridade'] : 'Eletiva';
        $complexidade = in_array($_POST['complexidade'] ?? '', ['Básica', 'Média', 'Alta'], true) ? $_POST['complexidade'] : 'Média';
        if (!$at || $esp === '' || $resumo === '') {
            voltar('Informe a especialidade e o resumo clínico.');
            break;
        }
        $id = db_inserir("INSERT INTO encaminhamento (paciente_id, profissional_id, atendimento_id, especialidade, resumo_clinico, prioridade, complexidade, dt_solicitacao)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 'iiisssss',
            $at->paciente_id, id_funcionario(), $at->id_atendimento, $esp, $resumo, $prioridade, $complexidade, agora());
        // RN-RF14: média/alta complexidade gera código de rastreio
        $codigo = null;
        if ($complexidade !== 'Básica') {
            $codigo = 'REG-' . date('Y') . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
            db("UPDATE encaminhamento SET codigo_regulacao = ? WHERE id_encaminhamento = ?", 'si', $codigo, $id);
        }
        auditoria('Criar', 'Encaminhamento', $id, $at->paciente_id, $esp);
        mensagem('Encaminhamento enviado para a regulação.' . ($codigo ? " Código de rastreio: $codigo." : ''), 'editar-atendimento&id=' . $at->id_atendimento);
        break;

    case 'aprovar':
    case 'negar':
        // O administrador faz o papel do setor de Regulação
        if (!exigir(ADM)) break;
        $e = db_um("SELECT * FROM encaminhamento WHERE id_encaminhamento = ? AND status_encaminhamento = 'Aguardando regulação'", 'i', $_GET['id'] ?? 0);
        if (!$e) {
            mensagem('Encaminhamento não encontrado ou já avaliado.', 'listar-encaminhamentos');
            break;
        }
        $novo = $_REQUEST['acao'] === 'aprovar' ? 'Aprovado' : 'Negado';
        db("UPDATE encaminhamento SET status_encaminhamento = ? WHERE id_encaminhamento = ?", 'si', $novo, $e->id_encaminhamento);
        notificar($e->paciente_id, "Seu encaminhamento para {$e->especialidade} foi " . strtolower($novo) . " pela regulação."
            . ($novo === 'Aprovado' ? ' Procure a recepção ou agende pela internet a consulta especializada.' : ''));
        auditoria($novo === 'Aprovado' ? 'Aprovar' : 'Negar', 'Encaminhamento', $e->id_encaminhamento, $e->paciente_id);
        mensagem("Encaminhamento $novo.", 'listar-encaminhamentos');
        break;
}
?>
