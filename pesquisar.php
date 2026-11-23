<h1>Resultado da Pesquisa</h1>
<?php

    $termo = trim($_REQUEST['termo'] ?? '');

    if(empty($termo)){
        print "<div class='alert alert-warning'>Digite algo para pesquisar!</div>";
    } else {
        $like = '%' . $termo . '%';
        $doc = so_digitos($termo);
        $doc_like = $doc !== '' ? '%' . $doc . '%' : '-';

        $lista = db_lista("SELECT * FROM paciente
                           WHERE nome_paciente LIKE ? OR cpf_paciente LIKE ? OR cns_paciente LIKE ? OR email_paciente LIKE ?
                           ORDER BY nome_paciente", 'ssss', $like, $doc_like, $doc_like, $like);

        $qtd = count($lista);

        if($qtd > 0){
            print "<p>Encontrou <b>$qtd</b> resultado(s) para '<b>" . h($termo) . "</b>'.</p>";
            print "<table class='table table-bordered table-striped table-hover'>";
            print "<tr>";
            print "<th>#</th>";
            print "<th>Nome</th>";
            print "<th>CPF</th>";
            print "<th>Cartão SUS</th>";
            print "<th>E-mail</th>";
            print "<th>Ações</th>";
            print "</tr>";

            foreach ($lista as $row) {
                print "<tr>";
                print "<td>" . $row->id_paciente . "</td>";
                print "<td>" . h($row->nome_paciente) . "</td>";
                print "<td>" . cpf_br($row->cpf_paciente) . "</td>";
                print "<td>" . h($row->cns_paciente) . "</td>";
                print "<td>" . h($row->email_paciente) . "</td>";
                print "<td>";
                if (tem_perfil(CLINICOS)) {
                    print "<button onclick=\"location.href='?page=prontuario&id=" . $row->id_paciente . "';\" class='btn btn-primary btn-sm'>Prontuário</button> ";
                }
                if (tem_perfil(ADM, REC)) {
                    print "<button onclick=\"location.href='?page=editar&id=" . $row->id_paciente . "';\" class='btn btn-success btn-sm'>Editar</button> ";
                    print "<button onclick=\"location.href='?page=novo-agendamento&paciente_id=" . $row->id_paciente . "';\" class='btn btn-outline-primary btn-sm'>Agendar</button> ";
                }
                if (tem_perfil(FAR)) {
                    print "<button onclick=\"location.href='?page=dispensacao&documento=" . urlencode($row->cpf_paciente ?: $row->cns_paciente) . "';\" class='btn btn-primary btn-sm'>Dispensar</button>";
                }
                print "</td>";
                print "</tr>";
            }
            print "</table>";
        } else {
            print "<div class='alert alert-danger'>Nenhum paciente encontrado com esses dados.</div>";
        }
    }
?>
