<h1>Cartão de vacinação</h1>
<?php
$id = (int)($_GET['id'] ?? 0);
if (!pode_ver_paciente($id) || !db_um("SELECT id_paciente FROM paciente WHERE id_paciente = ?", 'i', $id)) {
    print "<div class='alert alert-danger'>Paciente não encontrado.</div>";
    return;
}
auditoria('Visualizar', 'Cartão de vacina', $id, $id);
print cabecalho_paciente($id);
print alerta_vacinas($id);
if (tem_perfil(ENF)) {
    print "<p><a href='?page=nova-vacinacao&paciente_id=$id' class='btn btn-primary'>Registrar vacina</a></p>";
}
$lista = db_lista("SELECT v.*, c.vacina, c.dose, e.lote_estoque, e.fabricante_estoque, f.nome_funcionario
                   FROM vacinacao v
                   JOIN vacina_calendario c ON c.id_calendario = v.calendario_id
                   JOIN estoque e ON e.id_estoque = v.estoque_id
                   JOIN funcionario f ON f.id_funcionario = v.aplicador_id
                   WHERE v.paciente_id = ? ORDER BY v.dt_aplicacao, c.idade_meses", 'i', $id);
if ($lista) {
    print "<table class='table table-bordered table-striped table-hover'>";
    print "<tr><th>Data</th><th>Vacina</th><th>Dose</th><th>Lote</th><th>Fabricante</th><th>Via</th><th>Aplicador</th></tr>";
    foreach ($lista as $v) {
        print "<tr><td>" . data_br($v->dt_aplicacao) . "</td><td>" . h($v->vacina) . "</td><td>" . h($v->dose) . "</td><td>" . h($v->lote_estoque) . "</td>"
            . "<td>" . h($v->fabricante_estoque) . "</td><td>" . h($v->via_administracao) . "</td><td>" . h($v->nome_funcionario) . "</td></tr>";
    }
    print "</table>";
} else {
    print "<p class='alert alert-secondary'>Nenhuma vacina registrada.</p>";
}
?>
