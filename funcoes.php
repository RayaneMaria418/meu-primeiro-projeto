<?php
// =====================================================================
// Funções de apoio usadas por todas as páginas
// =====================================================================

// Perfis de acesso (RNF02 - RBAC)
const ADM = 'Administrador';
const REC = 'Recepcao';
const MED = 'Medico';
const ENF = 'Enfermeiro';
const ODO = 'Odontologo';
const FAR = 'Farmaceutico';
const ACS = 'ACS';
const PAC = 'Paciente';
const CLINICOS = [MED, ENF, ODO];
const FUNCIONARIOS = [ADM, REC, MED, ENF, ODO, FAR, ACS];

const NOMES_PERFIL = [
    ADM => 'Administrador', REC => 'Recepção', MED => 'Médico(a)', ENF => 'Enfermeiro(a)',
    ODO => 'Odontólogo(a)', FAR => 'Farmacêutico(a)', ACS => 'Agente Comunitário de Saúde', PAC => 'Paciente',
];

const STATUS_ATIVOS = ['Solicitado', 'Confirmado', 'Em Triagem', 'Em Atendimento', 'Aguardando Medicamento'];
const ORDEM_RISCO = ['Vermelho' => 1, 'Laranja' => 2, 'Amarelo' => 3, 'Verde' => 4, 'Azul' => 5];

// ---------------------------------------------------------------- saída HTML
function h($texto)
{
    return htmlspecialchars((string)($texto ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Mostra um alerta e redireciona (mesmo padrão das telas originais). */
function mensagem($texto, $pagina)
{
    print "<script>alert(" . json_encode($texto) . ");</script>";
    print "<script>location.href=" . json_encode('?page=' . $pagina) . ";</script>";
}

function voltar($texto)
{
    print "<script>alert(" . json_encode($texto) . "); history.back();</script>";
}

function sel($a, $b)
{
    return (string)$a === (string)$b ? 'selected' : '';
}

function marcado($v)
{
    return $v ? 'checked' : '';
}

// ---------------------------------------------------------------- banco de dados
/** Executa um SQL com parâmetros (prepared statement) e devolve o statement. */
function db($sql, $tipos = '', ...$params)
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** Retorna todas as linhas como objetos. */
function db_lista($sql, $tipos = '', ...$params)
{
    $res = db($sql, $tipos, ...$params)->get_result();
    $lista = [];
    while ($row = $res->fetch_object()) {
        $lista[] = $row;
    }
    return $lista;
}

/** Retorna a primeira linha (ou null). */
function db_um($sql, $tipos = '', ...$params)
{
    $res = db($sql, $tipos, ...$params)->get_result();
    return $res->fetch_object() ?: null;
}

function db_valor($sql, $tipos = '', ...$params)
{
    $res = db($sql, $tipos, ...$params)->get_result();
    $row = $res->fetch_row();
    return $row ? $row[0] : null;
}

function db_inserir($sql, $tipos = '', ...$params)
{
    global $conn;
    db($sql, $tipos, ...$params);
    return $conn->insert_id;
}

function agora()
{
    return date('Y-m-d H:i:s');
}

function hoje()
{
    return date('Y-m-d');
}

// ---------------------------------------------------------------- sessão e permissões
function usuario()
{
    return $_SESSION['usuario'] ?? null;
}

function perfil()
{
    return $_SESSION['usuario']['perfil'] ?? null;
}

function tem_perfil(...$perfis)
{
    $p = perfil();
    foreach ($perfis as $item) {
        if (is_array($item) ? in_array($p, $item, true) : $p === $item) {
            return true;
        }
    }
    return false;
}

function id_funcionario()
{
    return $_SESSION['usuario']['funcionario_id'] ?? null;
}

function id_paciente_logado()
{
    return $_SESSION['usuario']['paciente_id'] ?? null;
}

/** RN-RNF02: bloqueia a página se o perfil não puder acessá-la. */
function exigir(...$perfis)
{
    if (!tem_perfil(...$perfis)) {
        print "<div class='alert alert-danger'>Seu perfil não tem permissão para acessar esta funcionalidade.</div>";
        return false;
    }
    return true;
}

/** Paciente só acessa os próprios dados (RN-RNF01). */
function pode_ver_paciente($paciente_id)
{
    if (tem_perfil(PAC)) {
        return (int)$paciente_id === (int)id_paciente_logado();
    }
    return tem_perfil(FUNCIONARIOS);
}

// ---------------------------------------------------------------- auditoria (RF25)
function ip_cliente()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'cli';
}

function auditoria($acao, $entidade, $registro_id = null, $paciente_id = null, $detalhes = null)
{
    $u = usuario();
    db("INSERT INTO auditoria (usuario_id, nome_usuario, perfil_usuario, acao, entidade, registro_id, paciente_id, ip, detalhes, dt_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        'issssiisss',
        $u['id_usuario'] ?? null, $u['nome'] ?? null, $u['perfil'] ?? null, $acao, $entidade,
        $registro_id, $paciente_id, ip_cliente(), $detalhes ? mb_substr($detalhes, 0, 255) : null, agora());
}

// ---------------------------------------------------------------- criptografia (RNF03)
function cripto($texto)
{
    if ($texto === null || $texto === '') {
        return null;
    }
    $chave = hash('sha256', CHAVE_SEGURANCA, true);
    $iv = random_bytes(16);
    $cifrado = openssl_encrypt($texto, 'aes-256-cbc', $chave, OPENSSL_RAW_DATA, $iv);
    return 'aes:' . base64_encode($iv . $cifrado);
}

function decripto($valor)
{
    if ($valor === null || $valor === '') {
        return '';
    }
    if (strpos($valor, 'aes:') !== 0) {
        return $valor; // texto ainda não criptografado
    }
    $bruto = base64_decode(substr($valor, 4));
    $chave = hash('sha256', CHAVE_SEGURANCA, true);
    $texto = openssl_decrypt(substr($bruto, 16), 'aes-256-cbc', $chave, OPENSSL_RAW_DATA, substr($bruto, 0, 16));
    return $texto === false ? '[não foi possível descriptografar]' : $texto;
}

/** Assinatura eletrônica simples (HMAC-SHA256) usada nos comprovantes de estoque (RN-RF19). */
function assinar($dados)
{
    return hash_hmac('sha256', $dados, CHAVE_SEGURANCA);
}

// ---------------------------------------------------------------- validações
function so_digitos($v)
{
    return preg_replace('/\D/', '', (string)$v);
}

function cpf_valido($cpf)
{
    $cpf = so_digitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += $cpf[$i] * (($t + 1) - $i);
        }
        $d = ((10 * $soma) % 11) % 10;
        if ((int)$cpf[$t] !== $d) {
            return false;
        }
    }
    return true;
}

function cns_valido($cns)
{
    $cns = so_digitos($cns);
    if (strlen($cns) !== 15 || !in_array($cns[0], ['1', '2', '7', '8', '9'], true)) {
        return false;
    }
    $soma = 0;
    for ($i = 0; $i < 15; $i++) {
        $soma += $cns[$i] * (15 - $i);
    }
    return $soma % 11 === 0;
}

function cid_valido($cid)
{
    return $cid === '' || preg_match('/^[A-Z][0-9]{2}(\.?[0-9A-Z]{0,2})?$/', strtoupper($cid));
}

// ---------------------------------------------------------------- formatação
function data_br($d)
{
    return $d ? date('d/m/Y', strtotime($d)) : '';
}

function datahora_br($d)
{
    return $d ? date('d/m/Y H:i', strtotime($d)) : '';
}

function hora_br($h)
{
    return $h ? substr($h, 0, 5) : '';
}

function cpf_br($cpf)
{
    $c = so_digitos($cpf);
    return strlen($c) === 11 ? substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9) : $cpf;
}

function idade_anos($dt_nasc)
{
    return (new DateTime($dt_nasc))->diff(new DateTime('today'))->y;
}

function idade_meses($dt_nasc)
{
    $d = (new DateTime($dt_nasc))->diff(new DateTime('today'));
    return $d->y * 12 + $d->m;
}

function idade_texto($dt_nasc)
{
    $anos = idade_anos($dt_nasc);
    if ($anos >= 2) {
        return $anos . ' anos';
    }
    $m = idade_meses($dt_nasc);
    return $m . ($m === 1 ? ' mês' : ' meses');
}

/** Primeiro nome (mantém "Dr."/"Dra." + nome). */
function nome_curto($nome)
{
    $partes = explode(' ', trim($nome));
    return in_array($partes[0], ['Dr.', 'Dra.'], true) && isset($partes[1]) ? $partes[0] . ' ' . $partes[1] : $partes[0];
}

function badge($texto, $cor)
{
    return "<span class='badge bg-$cor'>" . h($texto) . "</span>";
}

function status_badge($status)
{
    $cores = [
        'Solicitado' => 'secondary', 'Confirmado' => 'primary', 'Em Triagem' => 'info', 'Em Atendimento' => 'warning',
        'Aguardando Medicamento' => 'dark', 'Finalizado' => 'success', 'Cancelado' => 'danger', 'Ausente' => 'secondary',
        'Pendente' => 'warning', 'Atendida' => 'success', 'Cancelada' => 'danger',
        'Aguardando regulação' => 'warning', 'Aprovado' => 'success', 'Negado' => 'danger', 'Utilizado' => 'secondary',
    ];
    return badge($status, $cores[$status] ?? 'secondary');
}

function risco_badge($risco)
{
    $cores = ['Vermelho' => 'danger', 'Laranja' => 'warning text-dark', 'Amarelo' => 'warning text-dark', 'Verde' => 'success', 'Azul' => 'primary'];
    $style = $risco === 'Laranja' ? " style='background-color:#fd7e14 !important'" : '';
    return $risco ? "<span class='badge bg-" . ($cores[$risco] ?? 'secondary') . "'$style>" . h($risco) . "</span>" : '';
}

function validade_badge($dt_validade)
{
    $dias = (int)floor((strtotime($dt_validade) - strtotime(hoje())) / 86400);
    if ($dias < 0) {
        return badge('Vencido', 'danger');
    }
    if ($dias <= 30) {
        return badge("Vence em $dias dia(s)", 'warning text-dark');
    }
    return badge('Válido', 'success');
}

const DIAS_SEMANA = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];

// ---------------------------------------------------------------- agenda (RF06, RN-RF24)
/** Ausência registrada para o profissional na data (ou null). */
function ausencia_na_data($funcionario_id, $data)
{
    return db_um("SELECT * FROM ausencia WHERE funcionario_id = ? AND ? BETWEEN dt_inicio AND dt_fim", 'is', $funcionario_id, $data);
}

/** Horários livres (de 30 em 30 minutos) conforme a escala do profissional. */
function horarios_livres($funcionario_id, $data, $ignorar_agendamento = 0)
{
    if (!$funcionario_id || !$data || $data < hoje() || ausencia_na_data($funcionario_id, $data)) {
        return [];
    }
    $dia = (int)date('N', strtotime($data));
    $escalas = db_lista("SELECT hr_inicio, hr_fim FROM escala WHERE funcionario_id = ? AND dia_semana = ? ORDER BY hr_inicio", 'ii', $funcionario_id, $dia);
    $ocupados = [];
    foreach (db_lista("SELECT hr_agendamento FROM agendamento WHERE funcionario_id = ? AND dt_agendamento = ? AND id_agendamento <> ?
                       AND status_agendamento NOT IN ('Cancelado','Ausente')", 'isi', $funcionario_id, $data, $ignorar_agendamento) as $a) {
        $ocupados[] = substr($a->hr_agendamento, 0, 5);
    }
    $livres = [];
    foreach ($escalas as $e) {
        for ($t = strtotime("$data {$e->hr_inicio}"); $t + 1800 <= strtotime("$data {$e->hr_fim}"); $t += 1800) {
            $hora = date('H:i', $t);
            if ($data === hoje() && $t <= time()) {
                continue;
            }
            if (!in_array($hora, $ocupados, true)) {
                $livres[] = $hora;
            }
        }
    }
    return $livres;
}

/** Retorna uma mensagem de erro, ou null se o horário puder ser agendado. */
function validar_horario($funcionario_id, $data, $hora, $ignorar_agendamento = 0)
{
    $aus = ausencia_na_data($funcionario_id, $data);
    if ($aus) {
        return "O profissional está ausente nesta data ({$aus->motivo}). Escolha outra data.";
    }
    if (!in_array(substr($hora, 0, 5), horarios_livres($funcionario_id, $data, $ignorar_agendamento), true)) {
        return 'Horário indisponível na escala do profissional. Escolha um dos horários livres.';
    }
    return null;
}

function registrar_historico($agendamento_id, $anterior, $novo, $justificativa = null, $dh_anterior = null, $dh_nova = null)
{
    db("INSERT INTO agendamento_historico (agendamento_id, status_anterior, status_novo, data_hora_anterior, data_hora_nova, justificativa, usuario_id, dt_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 'isssssis',
        $agendamento_id, $anterior, $novo, $dh_anterior, $dh_nova, $justificativa, usuario()['id_usuario'] ?? null, agora());
}

function mudar_status($agendamento_id, $novo, $justificativa = null)
{
    $ag = db_um("SELECT status_agendamento FROM agendamento WHERE id_agendamento = ?", 'i', $agendamento_id);
    if (!$ag || $ag->status_agendamento === $novo) {
        return;
    }
    db("UPDATE agendamento SET status_agendamento = ? WHERE id_agendamento = ?", 'si', $novo, $agendamento_id);
    registrar_historico($agendamento_id, $ag->status_agendamento, $novo, $justificativa);
}

/** Cargos que podem atender cada tipo de agendamento. */
function cargos_do_tipo($tipo)
{
    $mapa = [
        'Consulta médica' => [MED], 'Consulta especializada' => [MED], 'Pré-natal' => [MED, ENF],
        'Consulta de enfermagem' => [ENF], 'Procedimento' => [ENF, MED], 'Consulta odontológica' => [ODO],
        'Teleconsulta' => [MED, ENF],
    ];
    return $mapa[$tipo] ?? CLINICOS;
}

const TIPOS_AGENDAMENTO = ['Consulta médica', 'Consulta de enfermagem', 'Consulta odontológica', 'Consulta especializada', 'Pré-natal', 'Procedimento', 'Teleconsulta'];

/**
 * Fila de triagem do dia (RN-RF09): intercala 2 preferenciais para 1 normal,
 * continuando a sequência a partir das últimas chamadas feitas hoje.
 */
function fila_triagem()
{
    $aguardando = db_lista("SELECT a.*, p.nome_paciente, p.dt_nasc_paciente,
                                   (SELECT MAX(c.dt_chamada) FROM chamada c WHERE c.agendamento_id = a.id_agendamento AND c.tipo_chamada = 'Triagem') AS chamado_em
                            FROM agendamento a JOIN paciente p ON p.id_paciente = a.paciente_id
                            WHERE a.status_agendamento = 'Em Triagem'
                              AND NOT EXISTS (SELECT 1 FROM triagem t WHERE t.agendamento_id = a.id_agendamento)
                            ORDER BY a.dt_checkin, a.id_agendamento");
    $chamados = array_values(array_filter($aguardando, fn($a) => $a->chamado_em));
    $pref = array_values(array_filter($aguardando, fn($a) => !$a->chamado_em && $a->prioridade_agendamento === 'Preferencial'));
    $norm = array_values(array_filter($aguardando, fn($a) => !$a->chamado_em && $a->prioridade_agendamento !== 'Preferencial'));

    // quantos preferenciais seguidos foram chamados por último hoje
    $seguidos = 0;
    foreach (db_lista("SELECT prioridade_chamada FROM chamada WHERE tipo_chamada = 'Triagem' AND DATE(dt_chamada) = ? ORDER BY dt_chamada DESC, id_chamada DESC LIMIT 2", 's', hoje()) as $c) {
        if ($c->prioridade_chamada !== 'Preferencial') break;
        $seguidos++;
    }
    $ordem = [];
    while ($pref || $norm) {
        if ($pref && ($seguidos < 2 || !$norm)) {
            $ordem[] = array_shift($pref);
            $seguidos++;
        } else {
            $ordem[] = array_shift($norm);
            $seguidos = 0;
        }
    }
    return array_merge($chamados, $ordem);
}

/**
 * Fila do consultório (RN-RF08): risco Vermelho e Laranja passam na frente de todos
 * e aparecem para qualquer médico; depois Amarelo, Verde e Azul.
 */
function fila_consultorio($funcionario_id)
{
    $lista = db_lista("SELECT a.*, p.nome_paciente, p.dt_nasc_paciente, t.classificacao_risco, t.dt_triagem, f.nome_funcionario,
                              (SELECT MAX(c.dt_chamada) FROM chamada c WHERE c.agendamento_id = a.id_agendamento AND c.tipo_chamada = 'Consultório') AS chamado_em,
                              (SELECT x.id_atendimento FROM atendimento x WHERE x.agendamento_id = a.id_agendamento) AS atendimento_id
                       FROM agendamento a
                       JOIN paciente p ON p.id_paciente = a.paciente_id
                       JOIN triagem t ON t.agendamento_id = a.id_agendamento
                       LEFT JOIN funcionario f ON f.id_funcionario = a.funcionario_id
                       WHERE a.status_agendamento = 'Em Atendimento'
                         AND NOT EXISTS (SELECT 1 FROM atendimento x WHERE x.agendamento_id = a.id_agendamento AND x.finalizado = 1)
                         AND (a.funcionario_id = ? OR a.funcionario_id IS NULL OR (? AND t.classificacao_risco IN ('Vermelho','Laranja')))",
        'ii', $funcionario_id, tem_perfil(MED) ? 1 : 0);
    usort($lista, function ($x, $y) {
        return [ORDEM_RISCO[$x->classificacao_risco], $x->prioridade_agendamento === 'Preferencial' ? 0 : 1, $x->dt_triagem]
           <=> [ORDEM_RISCO[$y->classificacao_risco], $y->prioridade_agendamento === 'Preferencial' ? 0 : 1, $y->dt_triagem];
    });
    return $lista;
}

function notificar($paciente_id, $mensagem)
{
    db("INSERT INTO notificacao (paciente_id, mensagem, dt_notificacao) VALUES (?, ?, ?)", 'iss', $paciente_id, $mensagem, agora());
}

/** Prioridade por lei (RN-RF09): idosos, gestantes e pessoas com deficiência. */
function eh_preferencial($paciente_id)
{
    $p = db_um("SELECT dt_nasc_paciente, pcd_paciente FROM paciente WHERE id_paciente = ?", 'i', $paciente_id);
    if (!$p) {
        return false;
    }
    $gestante = db_valor("SELECT COUNT(*) FROM gestacao WHERE paciente_id = ? AND status_gestacao = 'Em acompanhamento'", 'i', $paciente_id);
    return idade_anos($p->dt_nasc_paciente) >= 60 || $p->pcd_paciente || $gestante > 0;
}

function gerar_senha($prioridade)
{
    $prefixo = $prioridade === 'Preferencial' ? 'P' : 'N';
    $n = db_valor("SELECT COUNT(*) FROM agendamento WHERE DATE(dt_checkin) = ? AND senha_agendamento LIKE ?", 'ss', hoje(), $prefixo . '%');
    return $prefixo . str_pad($n + 1, 3, '0', STR_PAD_LEFT);
}

// ---------------------------------------------------------------- programas (RF04, RN-RF04)
function programas_pelo_cid($cid)
{
    $cid = strtoupper(str_replace('.', '', (string)$cid));
    $programas = [];
    if (preg_match('/^I1[0-5]/', $cid)) {
        $programas[] = 'Hipertensão';
    }
    if (preg_match('/^E1[0-4]/', $cid)) {
        $programas[] = 'Diabetes';
    }
    if (preg_match('/^(Z3[2-6]|O)/', $cid)) {
        $programas[] = 'Pré-natal';
    }
    return $programas;
}

function programas_sugeridos($paciente)
{
    $s = [];
    if (idade_anos($paciente->dt_nasc_paciente) >= 60) {
        $s[] = 'Idoso';
    }
    if (idade_meses($paciente->dt_nasc_paciente) < 24) {
        $s[] = 'Puericultura';
    }
    return $s;
}

function incluir_programa($paciente_id, $programa, $origem = 'Manual')
{
    db("INSERT IGNORE INTO programa_paciente (paciente_id, programa, origem, dt_inclusao) VALUES (?, ?, ?, ?)",
        'isss', $paciente_id, $programa, $origem, hoje());
}

// ---------------------------------------------------------------- pré-natal (RF05)
function semanas_gestacao($dum)
{
    return (int)floor((new DateTime($dum))->diff(new DateTime('today'))->days / 7);
}

/** DPP pela regra de Naegele: DUM + 280 dias. */
function calcular_dpp($dum)
{
    return date('Y-m-d', strtotime($dum . ' +280 days'));
}

/** Agenda recomendada do pré-natal (Ministério da Saúde): mensal até 28 sem., quinzenal até 36 e semanal até 41. */
function agenda_pre_natal($dum)
{
    $consultas = [];
    for ($s = 4; $s <= 41; $s += ($s < 28 ? 4 : ($s < 36 ? 2 : 1))) {
        $consultas[] = ['semana' => $s, 'data' => date('Y-m-d', strtotime($dum . ' +' . ($s * 7) . ' days'))];
    }
    return $consultas;
}

const EXAMES_PRE_NATAL = [
    '1º trimestre' => 'Hemograma, tipagem sanguínea e fator Rh, glicemia de jejum, sorologias (HIV, sífilis, hepatites B e C, toxoplasmose), urina tipo 1 e urocultura, ultrassonografia obstétrica',
    '2º trimestre' => 'Teste de tolerância à glicose (24 a 28 semanas), Coombs indireto (se Rh negativo), ultrassonografia morfológica',
    '3º trimestre' => 'Hemograma, sorologias (HIV, sífilis, hepatite B), urocultura, pesquisa de estreptococo do grupo B (35 a 37 semanas)',
];

// ---------------------------------------------------------------- vacinas (RF17)
function vacinas_pendentes($paciente_id)
{
    $p = db_um("SELECT dt_nasc_paciente FROM paciente WHERE id_paciente = ?", 'i', $paciente_id);
    if (!$p) {
        return [];
    }
    $meses = idade_meses($p->dt_nasc_paciente);
    return db_lista("SELECT c.* FROM vacina_calendario c
                     WHERE ? BETWEEN c.idade_meses AND c.idade_max_meses
                       AND NOT EXISTS (SELECT 1 FROM vacinacao v WHERE v.paciente_id = ? AND v.calendario_id = c.id_calendario)
                     ORDER BY c.idade_meses, c.vacina", 'ii', $meses, $paciente_id);
}

// ---------------------------------------------------------------- estoque (RF19, RF20)
function saldo_valido($medicamento_id)
{
    return (int)db_valor("SELECT COALESCE(SUM(qt_estoque), 0) FROM estoque WHERE medicamento_id = ? AND dt_vali_estoque >= ?", 'is', $medicamento_id, hoje());
}

/** Registra a movimentação com assinatura eletrônica do responsável (RN-RF19). */
function registrar_movimentacao($estoque_id, $tipo, $quantidade, $documento = null, $observacao = null)
{
    $u = usuario();
    $dh = agora();
    $assinatura = assinar("$estoque_id|$tipo|$quantidade|" . ($u['id_usuario'] ?? '') . "|$dh");
    return db_inserir("INSERT INTO movimentacao_estoque (estoque_id, tipo_movimentacao, quantidade, documento, observacao_movimentacao, usuario_id, dt_movimentacao, assinatura)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 'isississ',
        $estoque_id, $tipo, $quantidade, $documento, $observacao, $u['id_usuario'] ?? null, $dh, $assinatura);
}

/**
 * Baixa a quantidade pedida usando primeiro os lotes que vencem antes (FEFO).
 * Lotes vencidos nunca são usados (RN-RF20). Lança exceção se faltar saldo.
 */
function baixar_estoque($medicamento_id, $quantidade, $tipo, $documento = null)
{
    if (saldo_valido($medicamento_id) < $quantidade) {
        throw new RuntimeException('Saldo insuficiente em lotes dentro da validade.');
    }
    $restante = $quantidade;
    $lotes = db_lista("SELECT * FROM estoque WHERE medicamento_id = ? AND dt_vali_estoque >= ? AND qt_estoque > 0 ORDER BY dt_vali_estoque, id_estoque",
        'is', $medicamento_id, hoje());
    foreach ($lotes as $lote) {
        if ($restante <= 0) {
            break;
        }
        $usar = min($restante, (int)$lote->qt_estoque);
        db("UPDATE estoque SET qt_estoque = qt_estoque - ? WHERE id_estoque = ?", 'ii', $usar, $lote->id_estoque);
        registrar_movimentacao($lote->id_estoque, $tipo, $usar, $documento);
        $restante -= $usar;
    }
}

// ---------------------------------------------------------------- componentes de tela
function select_pacientes($nome = 'paciente_id', $selecionado = null, $obrigatorio = true)
{
    $html = "<select name='$nome' class='form-control'" . ($obrigatorio ? ' required' : '') . "><option value=''>-- Selecione um Paciente --</option>";
    foreach (db_lista("SELECT id_paciente, nome_paciente, cpf_paciente, cns_paciente FROM paciente ORDER BY nome_paciente") as $p) {
        $doc = $p->cpf_paciente ? 'CPF ' . cpf_br($p->cpf_paciente) : ($p->cns_paciente ? 'SUS ' . $p->cns_paciente : '');
        $html .= "<option value='{$p->id_paciente}' " . sel($p->id_paciente, $selecionado) . ">" . h($p->nome_paciente) . " ($doc)</option>";
    }
    return $html . "</select>";
}

function select_profissionais($nome = 'funcionario_id', $selecionado = null, $cargos = CLINICOS, $obrigatorio = true)
{
    $marcas = implode(',', array_fill(0, count($cargos), '?'));
    $lista = db_lista("SELECT id_funcionario, nome_funcionario, cargo_funcionario, especialidade_funcionario FROM funcionario
                       WHERE ativo_funcionario = 1 AND cargo_funcionario IN ($marcas) ORDER BY nome_funcionario",
        str_repeat('s', count($cargos)), ...$cargos);
    $html = "<select name='$nome' class='form-control'" . ($obrigatorio ? ' required' : '') . "><option value=''>-- Selecione um Profissional --</option>";
    foreach ($lista as $f) {
        $esp = $f->especialidade_funcionario ? ' - ' . $f->especialidade_funcionario : '';
        $html .= "<option value='{$f->id_funcionario}' " . sel($f->id_funcionario, $selecionado) . ">" . h($f->nome_funcionario . $esp) . "</option>";
    }
    return $html . "</select>";
}

/** Cabeçalho com dados do paciente, usado nas telas clínicas. */
function cabecalho_paciente($paciente_id)
{
    $p = db_um("SELECT * FROM paciente WHERE id_paciente = ?", 'i', $paciente_id);
    if (!$p) {
        return '';
    }
    $doc = $p->cpf_paciente ? 'CPF: ' . cpf_br($p->cpf_paciente) : 'Responsável: ' . h($p->responsavel_nome) . ' (CPF ' . cpf_br($p->responsavel_cpf) . ')';
    $html = "<div class='alert alert-light border'><b>" . h($p->nome_paciente) . "</b> — " . idade_texto($p->dt_nasc_paciente)
        . " — $doc" . ($p->cns_paciente ? " — Cartão SUS: " . h($p->cns_paciente) : '');
    $alergias = decripto($p->alergias_paciente);
    if ($alergias !== '' && tem_perfil(CLINICOS)) {
        $html .= "<br><span class='text-danger'><b>Alergias:</b> " . h($alergias) . "</span>";
    }
    return $html . "</div>";
}

/** Alerta de vacinas atrasadas (RN-RF17). */
function alerta_vacinas($paciente_id)
{
    $pend = vacinas_pendentes($paciente_id);
    if (!$pend) {
        return '';
    }
    $itens = array_map(fn($v) => h($v->vacina . ' (' . $v->dose . ')'), $pend);
    $link = tem_perfil(ENF) ? " <a href='?page=nova-vacinacao&paciente_id=$paciente_id' class='alert-link'>Registrar vacina</a>" : '';
    return "<div class='alert alert-warning'><b>Vacinas pendentes para a idade:</b> " . implode(', ', $itens) . ".$link</div>";
}
?>
