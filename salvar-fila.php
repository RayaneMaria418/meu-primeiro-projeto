<?php
// UC09: o profissional clica em "Chamar Próximo"; o painel mostra a senha com aviso sonoro
$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($acao === 'chamar-triagem') {
    if (!exigir(ENF)) return;
    $fila = fila_triagem();
    $alvo = null;
    foreach ($fila as $a) {
        if ($id ? (int)$a->id_agendamento === $id : !$a->chamado_em) {
            $alvo = $a;
            break;
        }
    }
    $tipo = 'Triagem';
    $local = 'Sala de Triagem';
} elseif ($acao === 'chamar-consultorio') {
    if (!exigir(MED, ODO, ENF)) return;
    $fila = fila_consultorio(id_funcionario());
    $alvo = null;
    foreach ($fila as $a) {
        if ($id ? (int)$a->id_agendamento === $id : (!$a->chamado_em && !$a->atendimento_id)) {
            $alvo = $a;
            break;
        }
    }
    $tipo = 'Consultório';
    $local = 'Consultório - ' . usuario()['nome'];
} else {
    mensagem('Ação inválida.', 'fila');
    return;
}

if (!$alvo) {
    mensagem('Não há pacientes aguardando chamada.', 'fila');
    return;
}
db("INSERT INTO chamada (agendamento_id, senha_chamada, tipo_chamada, local_chamada, prioridade_chamada, dt_chamada) VALUES (?, ?, ?, ?, ?, ?)",
    'isssss', $alvo->id_agendamento, $alvo->senha_agendamento, $tipo, $local, $alvo->prioridade_agendamento, agora());
// O médico que chama assume o paciente que ainda não tinha profissional definido
if ($tipo === 'Consultório' && !$alvo->funcionario_id) {
    db("UPDATE agendamento SET funcionario_id = ? WHERE id_agendamento = ?", 'ii', id_funcionario(), $alvo->id_agendamento);
}
auditoria('Chamar', 'Fila', $alvo->id_agendamento, $alvo->paciente_id, "$tipo - senha {$alvo->senha_agendamento}");
mensagem("Senha {$alvo->senha_agendamento} ({$alvo->nome_paciente}) chamada no painel: $local.", 'fila');
?>
