<h1>Listagem de pacientes</h1>
<?php

$lista = db_lista("SELECT p.*, m.nome_microarea FROM paciente p
                   LEFT JOIN familia f ON f.id_familia = p.familia_id
                   LEFT JOIN microarea m ON m.id_microarea = f.microarea_id
                   ORDER BY p.nome_paciente");

$qtd = count($lista);

if($qtd > 0){
    print "<p>Encontrou <b>$qtd</b> resultado(s)</p>";

    print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";

    print "<tr>";
    print "<th>#</th>";
    print "<th>Nome</th>";
    print "<th>CPF</th>";
    print "<th>Cartão SUS</th>";
    print "<th>Telefone</th>";
    print "<th>E-mail</th>";
    print "<th>Data de Nascimento</th>";
    print "<th>Microárea</th>";
    print "<th>Ações</th>";
    print "</tr>";

    foreach ($lista as $row) {
        print "<tr>";
        print "<td>" . $row->id_paciente . "</td>";
        print "<td>" . h($row->nome_paciente) . "</td>";
        print "<td>" . ($row->cpf_paciente ? cpf_br($row->cpf_paciente) : "<small>Resp.: " . cpf_br($row->responsavel_cpf) . "</small>") . "</td>";
        print "<td>" . h($row->cns_paciente) . "</td>";
        print "<td>" . h($row->telefone_paciente) . "</td>";
        print "<td>" . h($row->email_paciente) . "</td>";
        print "<td>" . data_br($row->dt_nasc_paciente) . " <small>(" . idade_texto($row->dt_nasc_paciente) . ")</small></td>";
        print "<td>" . ($row->nome_microarea ? h($row->nome_microarea) : badge('Sem vínculo', 'secondary')) . "</td>";
        print "<td class='text-nowrap'>";
        if (tem_perfil(CLINICOS)) {
            print "<button onclick=\"location.href='?page=prontuario&id=" . $row->id_paciente . "';\" class='btn btn-primary'>Prontuário</button> ";
        }
        if (tem_perfil(ADM, REC)) {
            print "<button onclick=\"location.href='?page=editar&id=" . $row->id_paciente . "';\" class='btn btn-success'>Editar</button> ";
            print "<button onclick=\"if(confirm('Tem certeza que deseja excluir?')){ location.href='?page=salvar&acao=excluir&id=" . $row->id_paciente . "'; }\" class='btn btn-danger'>Excluir</button>";
        }
        print "</td>";
        print "</tr>";
    }
    print "</table></div>";
}else{
    print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
}
?>
