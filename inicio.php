<h1>Bem-vindo(a), <?php print h(usuario()['nome']); ?>!</h1>
<p class="text-muted"><?php print NOME_SISTEMA . ' — ' . h(NOMES_PERFIL[perfil()]) . ' — ' . date('d/m/Y'); ?></p>
<?php
$cards = [];

if (tem_perfil(ADM, REC)) {
    $hoje = db_valor("SELECT COUNT(*) FROM agendamento WHERE dt_agendamento = ? AND status_agendamento NOT IN ('Cancelado')", 's', hoje());
    $checkin = db_valor("SELECT COUNT(*) FROM agendamento WHERE dt_agendamento = ? AND status_agendamento = 'Confirmado' AND tipo_atendimento <> 'Teleconsulta'", 's', hoje());
    $cards[] = ['Agendamentos de hoje', $hoje, '?page=listar-agendamentos&data=' . hoje()];
    $cards[] = ['Aguardando check-in', $checkin, '?page=listar-agendamentos&data=' . hoje() . '&status=Confirmado'];
}

if (tem_perfil(CLINICOS)) {
    $triagem = db_valor("SELECT COUNT(*) FROM agendamento a WHERE a.status_agendamento = 'Em Triagem'
                         AND NOT EXISTS (SELECT 1 FROM triagem t WHERE t.agendamento_id = a.id_agendamento)");
    $consultorio = db_valor("SELECT COUNT(*) FROM agendamento a WHERE a.status_agendamento = 'Em Atendimento'
                             AND NOT EXISTS (SELECT 1 FROM atendimento x WHERE x.agendamento_id = a.id_agendamento AND x.finalizado = 1)
                             AND (a.funcionario_id = ? OR a.funcionario_id IS NULL)", 'i', id_funcionario());
    $cards[] = ['Fila de triagem', $triagem, '?page=fila'];
    if (tem_perfil(MED, ODO)) {
        $cards[] = ['Aguardando no consultório', $consultorio, '?page=fila'];
    }

    // UC08 - fluxo alternativo: emergência notifica imediatamente a equipe
    $emergencias = db_lista("SELECT p.nome_paciente, t.classificacao_risco, a.senha_agendamento FROM agendamento a
                             JOIN triagem t ON t.agendamento_id = a.id_agendamento
                             JOIN paciente p ON p.id_paciente = a.paciente_id
                             WHERE a.status_agendamento = 'Em Atendimento' AND t.classificacao_risco IN ('Vermelho','Laranja')
                               AND NOT EXISTS (SELECT 1 FROM atendimento x WHERE x.agendamento_id = a.id_agendamento AND x.finalizado = 1)");
    foreach ($emergencias as $e) {
        print "<div class='alert alert-danger'><b>Atenção:</b> paciente " . h($e->nome_paciente) . " (senha " . h($e->senha_agendamento)
            . ") classificado como " . risco_badge($e->classificacao_risco) . " aguarda atendimento médico imediato. <a href='?page=fila' class='alert-link'>Ir para a fila</a></div>";
    }
}

if (tem_perfil(FAR, ADM)) {
    $pendentes = db_valor("SELECT COUNT(*) FROM prescricao WHERE status_prescricao = 'Pendente'");
    $vencendo = db_valor("SELECT COUNT(*) FROM estoque WHERE qt_estoque > 0 AND dt_vali_estoque BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)", 'ss', hoje(), hoje());
    $vencidos = db_valor("SELECT COUNT(*) FROM estoque WHERE qt_estoque > 0 AND dt_vali_estoque < ?", 's', hoje());
    if (tem_perfil(FAR)) {
        $cards[] = ['Receitas pendentes', $pendentes, '?page=dispensacao'];
    }
    $cards[] = ['Lotes vencendo em 30 dias', $vencendo, '?page=listar-estoque&filtro=vencendo'];
    $cards[] = ['Lotes vencidos (bloqueados)', $vencidos, '?page=listar-estoque&filtro=vencidos'];
    if ($vencidos > 0) {
        print "<div class='alert alert-danger'>Há <b>$vencidos</b> lote(s) vencido(s) com saldo. A saída desses lotes está bloqueada; registre a baixa por avaria. <a href='?page=listar-estoque&filtro=vencidos' class='alert-link'>Ver lotes</a></div>";
    }
    if ($vencendo > 0) {
        print "<div class='alert alert-warning'>Há <b>$vencendo</b> lote(s) que vencem nos próximos 30 dias. <a href='?page=listar-estoque&filtro=vencendo' class='alert-link'>Ver lotes</a></div>";
    }
}

if (tem_perfil(MED, ENF)) {
    $cards[] = ['Gestantes em acompanhamento', db_valor("SELECT COUNT(*) FROM gestacao WHERE status_gestacao = 'Em acompanhamento'"), '?page=listar-gestantes'];
}

if (tem_perfil(ACS)) {
    $familias = db_valor("SELECT COUNT(*) FROM familia f JOIN microarea m ON m.id_microarea = f.microarea_id WHERE m.acs_id = ?", 'i', id_funcionario());
    $mes = db_valor("SELECT COUNT(*) FROM visita_acs WHERE acs_id = ? AND DATE_FORMAT(dt_visita, '%Y-%m') = ?", 'is', id_funcionario(), date('Y-m'));
    $cards[] = ['Famílias da minha microárea', $familias, '?page=listar-familias'];
    $cards[] = ['Visitas neste mês', $mes, '?page=listar-visitas'];
}

if (tem_perfil(ADM)) {
    $cards[] = ['Encaminhamentos aguardando regulação', db_valor("SELECT COUNT(*) FROM encaminhamento WHERE status_encaminhamento = 'Aguardando regulação'"), '?page=listar-encaminhamentos'];
}

print "<div class='row'>";
foreach ($cards as [$titulo, $valor, $link]) {
    print "<div class='col-md-3 mb-3'><div class='card'><div class='card-body'>
             <h5 class='card-title'>" . h($titulo) . "</h5>
             <p class='display-6'>" . (int)$valor . "</p>
             <a href='$link' class='btn btn-primary btn-sm'>Abrir</a>
           </div></div></div>";
}
print "</div>";
?>
