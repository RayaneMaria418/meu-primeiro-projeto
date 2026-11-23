<h1>Famílias (grupos familiares)</h1>
<p>
	<a href="?page=nova-familia" class="btn btn-primary">Nova Família</a>
	<a href="?page=vincular-familia" class="btn btn-outline-primary">Vincular Paciente a uma Família</a>
</p>
<?php
$sql = "SELECT f.*, m.nome_microarea, m.equipe_esf,
               (SELECT GROUP_CONCAT(p.nome_paciente ORDER BY p.nome_paciente SEPARATOR ', ') FROM paciente p WHERE p.familia_id = f.id_familia) AS membros,
               (SELECT MAX(v.dt_visita) FROM visita_acs v WHERE v.familia_id = f.id_familia) AS ultima_visita
        FROM familia f JOIN microarea m ON m.id_microarea = f.microarea_id";
// O ACS vê as famílias da sua microárea
$lista = tem_perfil(ACS)
    ? db_lista($sql . " WHERE m.acs_id = ? ORDER BY f.nome_familia", 'i', id_funcionario())
    : db_lista($sql . " ORDER BY f.nome_familia");

if ($lista) {
    print "<p>Encontrou <b>" . count($lista) . "</b> resultado(s)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>#</th><th>Família</th><th>Endereço</th><th>CEP</th><th>Microárea / Equipe</th><th>Membros</th><th>Última visita ACS</th><th>Ações</th></tr>";
    foreach ($lista as $f) {
        print "<tr><td>{$f->id_familia}</td><td>" . h($f->nome_familia) . "</td><td>" . h($f->endereco_familia) . "</td><td>" . h($f->cep_familia) . "</td>"
            . "<td>" . h($f->nome_microarea . ' / ' . $f->equipe_esf) . "</td><td>" . h($f->membros) . "</td><td>" . datahora_br($f->ultima_visita) . "</td><td class='text-nowrap'>";
        if (tem_perfil(ACS)) {
            print "<button onclick=\"location.href='?page=nova-visita&familia_id={$f->id_familia}';\" class='btn btn-primary'>Registrar visita</button> ";
        }
        print "<button onclick=\"if(confirm('Tem certeza que deseja excluir?')){ location.href='?page=salvar-familia&acao=excluir&id={$f->id_familia}'; }\" class='btn btn-danger'>Excluir</button>";
        print "</td></tr>";
    }
    print "</table></div>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
