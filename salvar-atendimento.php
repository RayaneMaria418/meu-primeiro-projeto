<?php
/** Inclusão automática em programas pelo CID (RN-RF04). Retorna os programas incluídos. */
function programas_automaticos($paciente_id, $cid)
{
    $incluidos = [];
    foreach (programas_pelo_cid($cid) as $prog) {
        $ja = db_valor("SELECT COUNT(*) FROM programa_paciente WHERE paciente_id = ? AND programa = ?", 'is', $paciente_id, $prog);
        if (!$ja) {
            incluir_programa($paciente_id, $prog, 'Automático (CID)');
            $incluidos[] = $prog;
        }
    }
    return $incluidos;
}

/** Atendimento em aberto do profissional logado (ou null). */
function meu_atendimento_aberto($id)
{
    $at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ?", 'i', $id);
    if (!$at || $at->finalizado || (int)$at->profissional_id !== (int)id_funcionario()) {
        return null;
    }
    return $at;
}

$acao = $_POST["acao"] ?? '';
$queixa = trim($_POST["queixa"] ?? '');
$diag = trim($_POST["diagnostico"] ?? '');
$cid = strtoupper(trim($_POST["cid"] ?? ''));
$conduta = trim($_POST["conduta"] ?? '');

if (in_array($acao, ['cadastrar', 'editar'], true) && ($queixa === '' || !cid_valido($cid))) {
    voltar('Informe a queixa principal e um CID-10 válido (ex.: I10) ou deixe o CID em branco.');
    return;
}

switch ($acao) {
    case 'cadastrar':
        $paciente_id = (int)$_POST["paciente_id"];
        $ag_id = (int)($_POST["agendamento_id"] ?? 0);
        $tipo = in_array($_POST['tipo_registro'] ?? '', ['Teleconsulta', 'Contato telefônico'], true) ? $_POST['tipo_registro'] : 'Presencial';
        $identidade = isset($_POST['identidade']) ? 1 : 0;
        $consentimento = isset($_POST['consentimento']) ? 1 : 0;

        if ($ag_id) {
            $ag = db_um("SELECT * FROM agendamento WHERE id_agendamento = ? AND paciente_id = ?", 'ii', $ag_id, $paciente_id);
            $ok = $ag && ($ag->tipo_atendimento === 'Teleconsulta' ? $ag->status_agendamento === 'Confirmado' : $ag->status_agendamento === 'Em Atendimento');
            if (!$ok || db_valor("SELECT COUNT(*) FROM atendimento WHERE agendamento_id = ?", 'i', $ag_id) > 0) {
                mensagem('Este agendamento não pode receber um novo atendimento.', 'fila');
                break;
            }
            $tipo = $ag->tipo_atendimento === 'Teleconsulta' ? 'Teleconsulta' : 'Presencial';
        }
        // RN-RF15: atendimento remoto só com identidade confirmada e consentimento
        if ($tipo !== 'Presencial' && (!$identidade || !$consentimento)) {
            voltar('Para atendimento remoto é obrigatório confirmar a identidade e o aceite do termo de consentimento.');
            break;
        }
        try {
            $conn->begin_transaction();
            $id = db_inserir("INSERT INTO atendimento (agendamento_id, paciente_id, profissional_id, tipo_registro, dt_atendimento, queixa_principal,
                                  diagnostico_atendimento, cid_atendimento, conduta_atendimento, identidade_confirmada, consentimento_remoto)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 'iiissssssii',
                $ag_id ?: null, $paciente_id, id_funcionario(), $tipo, agora(), $queixa, $diag, $cid ?: null, $conduta, $identidade, $consentimento);
            if ($ag_id) {
                db("UPDATE agendamento SET funcionario_id = ? WHERE id_agendamento = ?", 'ii', id_funcionario(), $ag_id);
                if ($tipo === 'Teleconsulta') {
                    mudar_status($ag_id, 'Em Atendimento', 'Teleconsulta iniciada');
                }
            }
            $progs = $cid ? programas_automaticos($paciente_id, $cid) : [];
            $conn->commit();
            auditoria('Criar', 'Atendimento', $id, $paciente_id, $tipo);
            $extra = $progs ? ' Paciente incluído automaticamente em: ' . implode(', ', $progs) . '.' : '';
            mensagem('Atendimento registrado.' . $extra . ' Agora você pode prescrever, solicitar exames, emitir atestado ou finalizar.', 'editar-atendimento&id=' . $id);
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            voltar('Erro ao registrar o atendimento.');
        }
        break;

    case 'editar':
        $at = meu_atendimento_aberto((int)$_POST['id']);
        if (!$at) {
            mensagem('Este atendimento já foi finalizado ou pertence a outro profissional. Use um adendo.', 'listar-atendimentos');
            break;
        }
        db("UPDATE atendimento SET queixa_principal = ?, diagnostico_atendimento = ?, cid_atendimento = ?, conduta_atendimento = ? WHERE id_atendimento = ?",
            'ssssi', $queixa, $diag, $cid ?: null, $conduta, $at->id_atendimento);
        $progs = $cid ? programas_automaticos($at->paciente_id, $cid) : [];
        auditoria('Editar', 'Atendimento', $at->id_atendimento, $at->paciente_id);
        $extra = $progs ? ' Paciente incluído automaticamente em: ' . implode(', ', $progs) . '.' : '';
        mensagem('Atendimento salvo.' . $extra, 'editar-atendimento&id=' . $at->id_atendimento);
        break;

    case 'finalizar':
        $at = meu_atendimento_aberto((int)$_POST['id']);
        if (!$at) {
            mensagem('Atendimento não encontrado ou já finalizado.', 'listar-atendimentos');
            break;
        }
        // RN-RF02: só assina quem tem registro ativo no conselho de classe
        if (empty(usuario()['registro'])) {
            voltar('Apenas profissionais com registro ativo no conselho (CRM, COREN, CRO) podem assinar o atendimento.');
            break;
        }
        db("UPDATE atendimento SET finalizado = 1, dt_finalizacao = ? WHERE id_atendimento = ?", 'si', agora(), $at->id_atendimento);
        if ($at->agendamento_id) {
            $pendentes = db_valor("SELECT COUNT(*) FROM prescricao WHERE atendimento_id = ? AND status_prescricao = 'Pendente'", 'i', $at->id_atendimento);
            if ($pendentes > 0) {
                mudar_status($at->agendamento_id, 'Aguardando Medicamento', 'Prescrição emitida, aguardando a farmácia');
            } else {
                mudar_status($at->agendamento_id, 'Finalizado', 'Consulta encerrada sem prescrição');
            }
        }
        auditoria('Assinar', 'Atendimento', $at->id_atendimento, $at->paciente_id, 'Assinado por ' . usuario()['registro']);
        mensagem('Atendimento finalizado e assinado. A partir de agora ele só aceita adendos.', 'fila');
        break;

    case 'adendo':
        $at = db_um("SELECT * FROM atendimento WHERE id_atendimento = ? AND finalizado = 1", 'i', $_POST['id'] ?? 0);
        $texto = trim($_POST['texto'] ?? '');
        if (!$at || $texto === '') {
            voltar('Escreva o texto do adendo.');
            break;
        }
        if (empty(usuario()['registro'])) {
            voltar('Apenas profissionais com registro ativo no conselho podem assinar adendos.');
            break;
        }
        db("INSERT INTO atendimento_adendo (atendimento_id, profissional_id, texto_adendo, dt_adendo) VALUES (?, ?, ?, ?)", 'iiss',
            $at->id_atendimento, id_funcionario(), $texto, agora());
        auditoria('Adendo', 'Atendimento', $at->id_atendimento, $at->paciente_id);
        mensagem('Adendo incluído com data e hora.', 'editar-atendimento&id=' . $at->id_atendimento);
        break;

    default:
        mensagem('Ação inválida.', 'listar-atendimentos');
}
?>
