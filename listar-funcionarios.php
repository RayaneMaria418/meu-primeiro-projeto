<h1>Listagem de funcionários</h1>
<?php
    $lista = db_lista("SELECT f.*, u.ultimo_acesso FROM funcionario f LEFT JOIN usuario u ON u.funcionario_id = f.id_funcionario ORDER BY f.nome_funcionario");

    $qtd = count($lista);

    if($qtd > 0){
        print "<p>Encontrou <b>$qtd</b> resultado(s)</p>";
        print "<div class='table-responsive'><table class='table table-bordered table-striped table-hover'>";
        print "<tr>";
        print "<th>#</th>";
        print "<th>Nome</th>";
        print "<th>Cargo</th>";
        print "<th>Registro</th>";
        print "<th>Especialidade</th>";
        print "<th>Telefone</th>";
        print "<th>E-mail</th>";
        print "<th>Situação</th>";
        print "<th>Ações</th>";
        print "</tr>";

        foreach ($lista as $row) {
            print "<tr>";
            print "<td>" . $row->id_funcionario . "</td>";
            print "<td>" . h($row->nome_funcionario) . "</td>";
            print "<td>" . h(NOMES_PERFIL[$row->cargo_funcionario]) . "</td>";
            print "<td>" . h($row->registro_conselho) . "</td>";
            print "<td>" . h($row->especialidade_funcionario) . "</td>";
            print "<td>" . h($row->telefone_funcionario) . "</td>";
            print "<td>" . h($row->email_funcionario) . "</td>";
            print "<td>" . ($row->ativo_funcionario ? badge('Ativo', 'success') : badge('Inativo', 'secondary')) . "</td>";
            print "<td class='text-nowrap'>
                    <button onclick=\"location.href='?page=editar-funcionario&id=" . $row->id_funcionario . "';\" class='btn btn-success'>Editar</button>
                    <button onclick=\"location.href='?page=escala&id=" . $row->id_funcionario . "';\" class='btn btn-primary'>Escala</button>
                    <button onclick=\"if(confirm('Tem certeza que deseja excluir?')){ location.href='?page=salvar-funcionario&acao=excluir&id=" . $row->id_funcionario . "'; }\" class='btn btn-danger'>Excluir</button>
                   </td>";
            print "</tr>";
        }
        print "</table></div>";
    }else{
        print "<p class='alert alert-danger'>Não foram encontrados resultado(s)!</p>";
    }
?>
