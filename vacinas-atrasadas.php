<h1>Relatório de vacinas atrasadas</h1>
<p>Pacientes com doses pendentes para a faixa etária, segundo o Calendário Nacional de Vacinação cadastrado no sistema.</p>
<?php
$linhas = [];
foreach (db_lista("SELECT id_paciente, nome_paciente, dt_nasc_paciente, telefone_paciente, responsavel_nome FROM paciente ORDER BY nome_paciente") as $p) {
    $pend = vacinas_pendentes($p->id_paciente);
    if ($pend) {
        $linhas[] = [$p, $pend];
    }
}
if ($linhas) {
    print "<p>Encontrou <b>" . count($linhas) . "</b> paciente(s) com vacinas pendentes.</p>";
    print "<table class='table table-bordered table-striped table-hover'><tr><th>Paciente</th><th>Idade</th><th>Contato</th><th>Vacinas pendentes</th><th>Ações</th></tr>";
    foreach ($linhas as [$p, $pend]) {
        print "<tr><td>" . h($p->nome_paciente) . "</td><td>" . idade_texto($p->dt_nasc_paciente) . "</td>"
            . "<td>" . h($p->telefone_paciente ?: $p->responsavel_nome) . "</td>"
            . "<td>" . h(implode(', ', array_map(fn($v) => "{$v->vacina} ({$v->dose})", $pend))) . "</td><td class='text-nowrap'>";
        if (tem_perfil(ENF)) {
            print "<button onclick=\"location.href='?page=nova-vacinacao&paciente_id={$p->id_paciente}';\" class='btn btn-primary'>Vacinar</button> ";
        }
        print "<button onclick=\"location.href='?page=cartao-vacina&id={$p->id_paciente}';\" class='btn btn-success'>Cartão</button></td></tr>";
    }
    print "</table>";
} else {
    print "<p class='alert alert-success'>Nenhum paciente com vacinas atrasadas.</p>";
}
?>
