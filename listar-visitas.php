<h1>Visitas domiciliares</h1>
<?php if (tem_perfil(ACS)) { ?>
<p><a href="?page=nova-visita" class="btn btn-primary">Nova Visita</a></p>
<div id="aviso-offline" class="alert alert-warning d-none"></div>
<script src="js/visitas-offline.js"></script>
<?php }
$sql = "SELECT v.*, f.nome_familia, f.endereco_familia, m.nome_microarea, a.nome_funcionario FROM visita_acs v
        JOIN familia f ON f.id_familia = v.familia_id JOIN microarea m ON m.id_microarea = f.microarea_id
        JOIN funcionario a ON a.id_funcionario = v.acs_id";
$lista = tem_perfil(ACS)
    ? db_lista($sql . " WHERE v.acs_id = ? ORDER BY v.dt_visita DESC", 'i', id_funcionario())
    : db_lista($sql . " ORDER BY v.dt_visita DESC LIMIT 300");
if ($lista) {
    print "<p>Encontrou <b>" . count($lista) . "</b> resultado(s)</p>";
    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>Data da visita</th><th>Família</th><th>Microárea</th><th>ACS</th><th>Motivo</th><th>Situação</th><th>Condições observadas</th><th>Metas</th></tr>";
    foreach ($lista as $v) {
        print "<tr><td>" . datahora_br($v->dt_visita) . "</td><td>" . h($v->nome_familia) . "</td><td>" . h($v->nome_microarea) . "</td><td>" . h($v->nome_funcionario) . "</td>"
            . "<td>" . h($v->motivo_visita) . "</td><td>" . h($v->situacao_familia) . "</td><td>" . h($v->condicoes_observadas) . "</td>"
            . "<td>" . ($v->dentro_prazo ? badge('Conta', 'success') : badge('Fora do prazo', 'secondary')) . ($v->registro_offline ? ' ' . badge('offline', 'info text-dark') : '') . "</td></tr>";
    }
    print "</table></div>";
} else {
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
