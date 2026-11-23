<?php
$acao = $_REQUEST["acao"] ?? '';

/** Carrega o agendamento e confere se o usuário pode mexer nele. */
function agendamento_permitido($id)
{
    $ag = db_um("SELECT a.*, f.nome_funcionario FROM agendamento a LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
                 WHERE a.id_agendamento = ?", 'i', $id);
    if (!$ag || (tem_perfil(PAC) && (int)$ag->paciente_id !== (int)id_paciente_logado())) {
        return null;
    }
    return $ag;
}

/** RN-RF07: o posto só cancela/remaneja com pelo menos 24 horas de antecedência. */
function antecedencia_ok($ag)
{
    return tem_perfil(PAC) || strtotime("{$ag->dt_agendamento} {$ag->hr_agendamento}") - time() >= 24 * 3600;
}

switch ($acao) {
    case 'cadastrar':
        if (!exigir(ADM, REC, PAC)) break;
        $paciente_id = tem_perfil(PAC) ? id_paciente_logado() : (int)($_POST["paciente_id"] ?? 0);
        $funcionario_id = (int)($_POST["funcionario_id"] ?? 0);
        $tipo = $_POST["tipo"] ?? '';
        $data = $_POST["data_agendamento"] ?? '';
        $hora = $_POST["hora_agendamento"] ?? '';
        $obs = trim($_POST["observacao"] ?? '');
        $origem = tem_perfil(PAC) ? 'Remoto' : 'Presencial';

        $prof = db_um("SELECT * FROM funcionario WHERE id_funcionario = ? AND ativo_funcionario = 1", 'i', $funcionario_id);
        if (!$paciente_id || !$prof || !in_array($tipo, TIPOS_AGENDAMENTO, true) || !in_array($prof->cargo_funcionario, cargos_do_tipo($tipo), true)) {
            voltar('Dados do agendamento inválidos.');
            break;
        }
        // RN-RF24 e disponibilidade da agenda
        $erro = validar_horario($funcionario_id, $data, $hora);
        if ($erro) {
            voltar($erro);
            break;
        }
        // RN-RF06: consulta especializada exige encaminhamento aprovado
        $enc_id = null;
        if ($tipo === 'Consulta especializada') {
            $enc = db_um("SELECT * FROM encaminhamento WHERE id_encaminhamento = ? AND paciente_id = ? AND status_encaminhamento = 'Aprovado' AND especialidade = ?",
                'iis', $_POST['encaminhamento_id'] ?? 0, $paciente_id, (string)$prof->especialidade_funcionario);
            if (!$enc) {
                voltar('Consulta especializada exige encaminhamento aprovado pela Regulação.');
                break;
            }
            $enc_id = $enc->id_encaminhamento;
        }
        $link = $tipo === 'Teleconsulta' ? 'https://meet.jit.si/posto-saude-' . bin2hex(random_bytes(6)) : null;

        try {
            $conn->begin_transaction();
            $id = db_inserir("INSERT INTO agendamento (paciente_id, funcionario_id, dt_agendamento, hr_agendamento, tipo_atendimento, origem_agendamento,
                                  status_agendamento, encaminhamento_id, link_teleconsulta, observacao_agendamento)
                              VALUES (?, ?, ?, ?, ?, ?, 'Solicitado', ?, ?, ?)", 'iissssiss',
                $paciente_id, $funcionario_id, $data, $hora, $tipo, $origem, $enc_id, $link, $obs);
            registrar_historico($id, null, 'Solicitado', 'Agendamento ' . strtolower($origem));
            // A agenda já foi validada acima: confirma automaticamente (Solicitado -> Confirmado)
            mudar_status($id, 'Confirmado', 'Disponibilidade da agenda validada');
            if ($enc_id) {
                db("UPDATE encaminhamento SET status_encaminhamento = 'Utilizado' WHERE id_encaminhamento = ?", 'i', $enc_id);
            }
            $orientacao = $tipo === 'Teleconsulta'
                ? "No horário, acesse o link da teleconsulta: $link"
                : 'Chegue 15 minutos antes, com documento com foto e Cartão SUS.';
            notificar($paciente_id, "Consulta confirmada: $tipo com {$prof->nome_funcionario} em " . data_br($data) . " às $hora. $orientacao");
            $conn->commit();
            auditoria('Criar', 'Agendamento', $id, $paciente_id);
            mensagem("Agendamento confirmado para " . data_br($data) . " às $hora. $orientacao", 'listar-agendamentos');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            voltar('Erro ao cadastrar o agendamento.');
        }
        break;

    case 'checkin':
        // UC09: recepção faz o check-in e emite a senha (preferencial ou normal)
        if (!exigir(ADM, REC)) break;
        $ag = agendamento_permitido((int)($_REQUEST['id'] ?? 0));
        if (!$ag || $ag->status_agendamento !== 'Confirmado' || $ag->dt_agendamento !== hoje() || $ag->tipo_atendimento === 'Teleconsulta') {
            mensagem('Check-in disponível apenas para agendamentos confirmados de hoje (presenciais).', 'listar-agendamentos');
            break;
        }
        $prioridade = eh_preferencial($ag->paciente_id) ? 'Preferencial' : 'Normal';
        $senha = gerar_senha($prioridade);
        db("UPDATE agendamento SET prioridade_agendamento = ?, senha_agendamento = ?, dt_checkin = ? WHERE id_agendamento = ?",
            'sssi', $prioridade, $senha, agora(), $ag->id_agendamento);
        mudar_status($ag->id_agendamento, 'Em Triagem', 'Check-in na recepção');
        auditoria('Check-in', 'Agendamento', $ag->id_agendamento, $ag->paciente_id, "Senha $senha");
        mensagem("Check-in realizado. Senha $senha ($prioridade). Encaminhe o paciente para a sala de espera da triagem.", 'listar-agendamentos&data=' . hoje());
        break;

    case 'demanda':
        // RF10 / UC10: paciente sem agendamento entra direto na fila de acolhimento do dia
        if (!exigir(ADM, REC)) break;
        $paciente_id = (int)($_POST['paciente_id'] ?? 0);
        if (!db_um("SELECT id_paciente FROM paciente WHERE id_paciente = ?", 'i', $paciente_id)) {
            voltar('Selecione o paciente.');
            break;
        }
        $prioridade = ($_POST['prioridade'] ?? 'Automática') === 'Automática'
            ? (eh_preferencial($paciente_id) ? 'Preferencial' : 'Normal')
            : ($_POST['prioridade'] === 'Preferencial' ? 'Preferencial' : 'Normal');
        $senha = gerar_senha($prioridade);
        $id = db_inserir("INSERT INTO agendamento (paciente_id, funcionario_id, dt_agendamento, hr_agendamento, tipo_atendimento, origem_agendamento,
                              status_agendamento, prioridade_agendamento, senha_agendamento, dt_checkin, observacao_agendamento)
                          VALUES (?, NULL, ?, ?, 'Demanda espontânea', 'Demanda espontânea', 'Em Triagem', ?, ?, ?, ?)", 'issssss',
            $paciente_id, hoje(), date('H:i:s'), $prioridade, $senha, agora(), trim($_POST['observacao'] ?? ''));
        registrar_historico($id, null, 'Em Triagem', 'Demanda espontânea encaminhada ao acolhimento');
        auditoria('Criar', 'Agendamento', $id, $paciente_id, "Demanda espontânea - senha $senha");
        mensagem("Paciente inserido na fila de acolhimento. Senha $senha ($prioridade).", 'fila');
        break;

    case 'cancelar':
        if (!exigir(ADM, REC, PAC)) break;
        $ag = agendamento_permitido((int)($_POST['id'] ?? 0));
        $justificativa = trim($_POST['justificativa'] ?? '');
        if (!$ag || !in_array($ag->status_agendamento, ['Solicitado', 'Confirmado'], true)) {
            mensagem('Só é possível cancelar agendamentos solicitados ou confirmados.', 'listar-agendamentos');
            break;
        }
        if ($justificativa === '') {
            voltar('Informe o motivo do cancelamento.');
            break;
        }
        if (!antecedencia_ok($ag)) {
            voltar('Pela regra RN-RF07, o posto só pode cancelar com no mínimo 24 horas de antecedência.');
            break;
        }
        db("UPDATE agendamento SET status_agendamento = 'Cancelado' WHERE id_agendamento = ?", 'i', $ag->id_agendamento);
        registrar_historico($ag->id_agendamento, $ag->status_agendamento, 'Cancelado', $justificativa);
        if (!tem_perfil(PAC)) {
            notificar($ag->paciente_id, "Sua consulta de " . data_br($ag->dt_agendamento) . " às " . hora_br($ag->hr_agendamento)
                . " foi cancelada pelo posto. Motivo: $justificativa. Entre em contato para remarcar.");
        }
        auditoria('Cancelar', 'Agendamento', $ag->id_agendamento, $ag->paciente_id, $justificativa);
        mensagem('Agendamento cancelado e vaga liberada na agenda.', 'listar-agendamentos');
        break;

    case 'reagendar':
        if (!exigir(ADM, REC, PAC)) break;
        $ag = agendamento_permitido((int)($_POST['id'] ?? 0));
        $justificativa = trim($_POST['justificativa'] ?? '');
        $data = $_POST['data_agendamento'] ?? '';
        $hora = $_POST['hora_agendamento'] ?? '';
        if (!$ag || !in_array($ag->status_agendamento, ['Solicitado', 'Confirmado'], true)) {
            mensagem('Só é possível remanejar agendamentos solicitados ou confirmados.', 'listar-agendamentos');
            break;
        }
        if ($justificativa === '') {
            voltar('Informe a justificativa do remanejamento.');
            break;
        }
        if (!antecedencia_ok($ag)) {
            voltar('Pela regra RN-RF07, o posto só pode remanejar com no mínimo 24 horas de antecedência.');
            break;
        }
        $erro = validar_horario($ag->funcionario_id, $data, $hora, $ag->id_agendamento);
        if ($erro) {
            voltar($erro);
            break;
        }
        db("UPDATE agendamento SET dt_agendamento = ?, hr_agendamento = ? WHERE id_agendamento = ?", 'ssi', $data, $hora, $ag->id_agendamento);
        registrar_historico($ag->id_agendamento, $ag->status_agendamento, $ag->status_agendamento, $justificativa,
            "{$ag->dt_agendamento} {$ag->hr_agendamento}", "$data $hora:00");
        notificar($ag->paciente_id, "Sua consulta foi remarcada de " . data_br($ag->dt_agendamento) . " " . hora_br($ag->hr_agendamento)
            . " para " . data_br($data) . " às $hora. Motivo: $justificativa");
        auditoria('Remanejar', 'Agendamento', $ag->id_agendamento, $ag->paciente_id, $justificativa);
        mensagem('Agendamento remarcado para ' . data_br($data) . " às $hora.", 'listar-agendamentos');
        break;

    case 'ausente':
        if (!exigir(ADM, REC)) break;
        $ag = agendamento_permitido((int)($_REQUEST['id'] ?? 0));
        if (!$ag || $ag->status_agendamento !== 'Confirmado' || strtotime("{$ag->dt_agendamento} {$ag->hr_agendamento}") > time()) {
            mensagem('Só é possível marcar falta em agendamento confirmado cujo horário já passou.', 'listar-agendamentos');
            break;
        }
        mudar_status($ag->id_agendamento, 'Ausente', 'Paciente não compareceu no horário agendado');
        auditoria('Editar', 'Agendamento', $ag->id_agendamento, $ag->paciente_id, 'Ausente');
        mensagem('Falta registrada.', 'listar-agendamentos');
        break;

    default:
        mensagem('Ação inválida.', 'listar-agendamentos');
}
?>
