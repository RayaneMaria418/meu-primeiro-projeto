<h1>Prontuário Eletrônico do Paciente</h1>
<?php
$id = (int)($_REQUEST['id'] ?? 0);
$pac = db_um("SELECT p.*, f.nome_familia, m.nome_microarea, m.equipe_esf FROM paciente p
              LEFT JOIN familia f ON f.id_familia = p.familia_id
              LEFT JOIN microarea m ON m.id_microarea = f.microarea_id
              WHERE p.id_paciente = ?", 'i', $id);
if (!$pac) {
    print "<div class='alert alert-danger'>Paciente não encontrado.</div>";
    return;
}

// RN-RF25: toda visualização do prontuário fica registrada
auditoria('Visualizar', 'Prontuário', $id, $id);

print cabecalho_paciente($id);
print alerta_vacinas($id);

$programas = db_lista("SELECT * FROM programa_paciente WHERE paciente_id = ? ORDER BY programa", 'i', $id);
$gestacao = db_um("SELECT * FROM gestacao WHERE paciente_id = ? AND status_gestacao = 'Em acompanhamento'", 'i', $id);
$ja = array_map(fn($p) => $p->programa, $programas);
$sugestoes = array_diff(programas_sugeridos($pac), $ja);
?>
<div class="row">
    <div class="col-md-6">
        <h4>Dados do paciente</h4>
        <table class="table table-bordered table-sm">
            <tr><th>Nascimento</th><td><?php print data_br($pac->dt_nasc_paciente) . ' (' . idade_texto($pac->dt_nasc_paciente) . ')'; ?></td></tr>
            <tr><th>Sexo</th><td><?php print $pac->sexo_paciente === 'F' ? 'Feminino' : 'Masculino'; ?></td></tr>
            <tr><th>Telefone</th><td><?php print h($pac->telefone_paciente); ?></td></tr>
            <tr><th>Contato de emergência</th><td><?php print h($pac->contato_emergencia); ?></td></tr>
            <tr><th>Família / Microárea</th><td><?php print $pac->nome_familia ? h("{$pac->nome_familia} - {$pac->nome_microarea} ({$pac->equipe_esf})") : badge('Sem vínculo territorial', 'secondary'); ?></td></tr>
        </table>

        <h4>Programas prioritários</h4>
        <p>
            <?php
                foreach ($programas as $pr) {
                    print badge($pr->programa, 'info text-dark') . " <small class='text-muted'>(" . h($pr->origem) . ", " . data_br($pr->dt_inclusao) . ")</small> ";
                }
                if (!$programas) print "<span class='text-muted'>Nenhum programa.</span>";
            ?>
        </p>
        <form action="?page=salvar-programa" method="POST" class="row g-2 mb-3">
            <input type="hidden" name="acao" value="incluir">
            <input type="hidden" name="paciente_id" value="<?php print $id; ?>">
            <input type="hidden" name="voltar" value="prontuario&id=<?php print $id; ?>">
            <div class="col-8">
                <select name="programa" class="form-control" required>
                    <option value="">-- Incluir em programa --</option>
                    <?php
                        foreach (['Hipertensão', 'Diabetes', 'Pré-natal', 'Idoso', 'Puericultura'] as $prog) {
                            if (!in_array($prog, $ja, true)) {
                                print "<option>" . h($prog) . (in_array($prog, $sugestoes, true) ? ' (sugerido pela idade)' : '') . "</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="col-4"><button type="submit" class="btn btn-outline-primary">Incluir</button></div>
        </form>
        <?php if ($sugestoes) print "<div class='alert alert-info py-2'>Sugestão pela idade: " . h(implode(', ', $sugestoes)) . ".</div>"; ?>

        <?php if ($pac->sexo_paciente === 'F' && tem_perfil(MED, ENF)) { ?>
            <h4>Pré-natal</h4>
            <?php if ($gestacao) {
                $sem = semanas_gestacao($gestacao->dum);
                print "<p>Gestação em acompanhamento: <b>$sem semanas</b>, DPP " . data_br($gestacao->dpp)
                    . ". <a href='?page=editar-gestacao&id={$gestacao->id_gestacao}'>Abrir ficha de pré-natal</a></p>";
            } else {
                print "<p><a href='?page=nova-gestacao&paciente_id=$id' class='btn btn-outline-primary btn-sm'>Iniciar acompanhamento de pré-natal</a></p>";
            } ?>
        <?php } ?>
    </div>
    <div class="col-md-6">
        <h4>Alergias e antecedentes</h4>
        <form action="?page=salvar" method="POST">
            <input type="hidden" name="acao" value="clinico">
            <input type="hidden" name="id" value="<?php print $id; ?>">
            <div class="mb-3">
                <label>Alergias</label>
                <textarea name="alergias" class="form-control" rows="2"><?php print h(decripto($pac->alergias_paciente)); ?></textarea>
            </div>
            <div class="mb-3">
                <label>Antecedentes familiares</label>
                <textarea name="antecedentes" class="form-control" rows="2"><?php print h(decripto($pac->antecedentes_familiares)); ?></textarea>
            </div>
            <div class="mb-3"><button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                <small class="text-muted">Gravado com criptografia AES-256.</small></div>
        </form>
        <h4>Ações</h4>
        <p>
            <a href="?page=cartao-vacina&id=<?php print $id; ?>" class="btn btn-outline-primary btn-sm">Cartão de vacina</a>
            <?php if (tem_perfil(ENF)) { ?><a href="?page=nova-vacinacao&paciente_id=<?php print $id; ?>" class="btn btn-outline-primary btn-sm">Registrar vacina</a><?php } ?>
            <?php if (tem_perfil(MED, ENF)) { ?><a href="?page=teleconsulta&paciente_id=<?php print $id; ?>" class="btn btn-outline-primary btn-sm">Teleconsulta / contato telefônico</a><?php } ?>
        </p>
    </div>
</div>

<h4 class="mt-3">Linha do tempo</h4>
<?php
$atendimentos = db_lista("SELECT at.*, f.nome_funcionario, f.cargo_funcionario, t.pressao_arterial, t.glicemia, t.temperatura, t.peso, t.altura,
                                 t.queixa_triagem, t.classificacao_risco, e.nome_funcionario AS enfermeiro
                          FROM atendimento at
                          JOIN funcionario f ON f.id_funcionario = at.profissional_id
                          LEFT JOIN triagem t ON t.agendamento_id = at.agendamento_id
                          LEFT JOIN funcionario e ON e.id_funcionario = t.enfermeiro_id
                          WHERE at.paciente_id = ? ORDER BY at.dt_atendimento DESC", 'i', $id);

// triagens que ainda não viraram atendimento
$triagens_abertas = db_lista("SELECT t.*, e.nome_funcionario AS enfermeiro FROM triagem t
                              JOIN agendamento a ON a.id_agendamento = t.agendamento_id
                              JOIN funcionario e ON e.id_funcionario = t.enfermeiro_id
                              WHERE a.paciente_id = ? AND NOT EXISTS (SELECT 1 FROM atendimento x WHERE x.agendamento_id = t.agendamento_id)
                              ORDER BY t.dt_triagem DESC", 'i', $id);
foreach ($triagens_abertas as $t) {
    print "<div class='card mb-2'><div class='card-body'><b>" . datahora_br($t->dt_triagem) . " — Triagem</b> por " . h($t->enfermeiro) . " " . risco_badge($t->classificacao_risco)
        . "<br>PA " . h($t->pressao_arterial) . " | Glicemia " . h($t->glicemia) . " | Temp. " . h($t->temperatura) . " °C | Peso " . h($t->peso) . " kg | Altura " . h($t->altura) . " m"
        . "<br>Queixa: " . h($t->queixa_triagem) . "</div></div>";
}

if (!$atendimentos && !$triagens_abertas) {
    print "<p class='alert alert-secondary'>Nenhum registro clínico.</p>";
}

foreach ($atendimentos as $at) {
    $situacao = $at->finalizado ? badge('Finalizado e assinado', 'success') : badge('Em aberto', 'warning text-dark');
    print "<div class='card mb-3'><div class='card-body'>";
    print "<h5 class='card-title'>" . datahora_br($at->dt_atendimento) . " — " . h($at->tipo_registro) . " com " . h($at->nome_funcionario) . " $situacao</h5>";
    if ($at->classificacao_risco) {
        print "<p class='mb-1'><b>Triagem</b> (" . h($at->enfermeiro) . ") " . risco_badge($at->classificacao_risco) . ": PA " . h($at->pressao_arterial)
            . ", glicemia " . h($at->glicemia) . ", temp. " . h($at->temperatura) . " °C, peso " . h($at->peso) . " kg, altura " . h($at->altura) . " m.</p>";
    }
    print "<p class='mb-1'><b>Queixa:</b> " . h($at->queixa_principal) . "</p>";
    print "<p class='mb-1'><b>Diagnóstico:</b> " . h($at->diagnostico_atendimento) . ($at->cid_atendimento ? " (CID " . h($at->cid_atendimento) . ")" : '') . "</p>";
    print "<p class='mb-1'><b>Conduta:</b> " . h($at->conduta_atendimento) . "</p>";

    foreach (db_lista("SELECT p.*, GROUP_CONCAT(CONCAT(m.nm_medicamento, ' ', m.concentracao_medicamento, ' - ', i.posologia, ' (', i.quantidade, ')') SEPARATOR '; ') AS itens
                       FROM prescricao p JOIN prescricao_item i ON i.prescricao_id = p.id_prescricao JOIN medicamento m ON m.id_medicamento = i.medicamento_id
                       WHERE p.atendimento_id = ? GROUP BY p.id_prescricao", 'i', $at->id_atendimento) as $pr) {
        print "<p class='mb-1'><b>Prescrição</b> " . status_badge($pr->status_prescricao) . ": " . h($pr->itens)
            . " <a href='?page=documento&tipo=receita&id={$pr->id_prescricao}'>ver receita</a></p>";
    }
    foreach (db_lista("SELECT s.*, e.nm_exame FROM solicitacao_exame s JOIN exame e ON e.id_exame = s.exame_id WHERE s.atendimento_id = ?", 'i', $at->id_atendimento) as $ex) {
        print "<p class='mb-1'><b>Exame solicitado:</b> " . h($ex->nm_exame) . " <a href='?page=documento&tipo=exame&id={$ex->id_solicitacao}'>ver guia</a></p>";
    }
    foreach (db_lista("SELECT * FROM atestado WHERE atendimento_id = ?", 'i', $at->id_atendimento) as $as) {
        print "<p class='mb-1'><b>" . h($as->tipo_atestado) . "</b> emitido em " . datahora_br($as->dt_emissao) . " <a href='?page=documento&tipo=atestado&id={$as->id_atestado}'>ver documento</a></p>";
    }
    foreach (db_lista("SELECT * FROM encaminhamento WHERE atendimento_id = ?", 'i', $at->id_atendimento) as $en) {
        print "<p class='mb-1'><b>Encaminhamento:</b> " . h($en->especialidade) . " " . status_badge($en->status_encaminhamento) . ($en->codigo_regulacao ? " — código " . h($en->codigo_regulacao) : '') . "</p>";
    }
    foreach (db_lista("SELECT a.*, f.nome_funcionario FROM atendimento_adendo a JOIN funcionario f ON f.id_funcionario = a.profissional_id WHERE a.atendimento_id = ? ORDER BY a.dt_adendo", 'i', $at->id_atendimento) as $ad) {
        print "<div class='alert alert-light border py-1 mb-1'><b>Adendo</b> " . datahora_br($ad->dt_adendo) . " — " . h($ad->nome_funcionario) . ": " . h($ad->texto_adendo) . "</div>";
    }
    print "<a href='?page=editar-atendimento&id={$at->id_atendimento}' class='btn btn-success btn-sm mt-2'>" . ($at->finalizado ? 'Incluir adendo' : 'Continuar atendimento') . "</a>";
    print "</div></div>";
}
?>
