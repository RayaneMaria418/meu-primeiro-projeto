<?php
/** Lê e valida o formulário. Retorna [dados, erro]. */
function dados_funcionario()
{
    $d = [
        'nome'     => trim($_POST["nome"] ?? ''),
        'cpf'      => so_digitos($_POST["cpf"] ?? ''),
        'cns'      => so_digitos($_POST["cns"] ?? ''),
        'cargo'    => $_POST["cargo"] ?? '',
        'registro' => trim($_POST["registro"] ?? ''),
        'esp'      => trim($_POST["especialidade"] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'email'    => trim($_POST["email"] ?? ''),
        'senha'    => $_POST["senha"] ?? '',
    ];
    if ($d['nome'] === '' || !cpf_valido($d['cpf'])) {
        return [$d, 'Informe o nome e um CPF válido.'];
    }
    if ($d['cns'] !== '' && !cns_valido($d['cns'])) {
        return [$d, 'CNS do profissional inválido.'];
    }
    if (!in_array($d['cargo'], FUNCIONARIOS, true)) {
        return [$d, 'Selecione o cargo.'];
    }
    // RN-RF02: profissionais clínicos e farmacêuticos precisam de registro ativo no conselho
    if (in_array($d['cargo'], [MED, ENF, ODO, FAR], true) && $d['registro'] === '') {
        return [$d, 'Informe o registro no conselho de classe (CRM, COREN, CRO ou CRF).'];
    }
    if ($d['senha'] !== '' && strlen($d['senha']) < 6) {
        return [$d, 'A senha precisa ter pelo menos 6 caracteres.'];
    }
    foreach (['cns', 'registro', 'esp'] as $c) {
        if ($d[$c] === '') $d[$c] = null;
    }
    return [$d, null];
}

switch ($_REQUEST["acao"] ?? '') {

    case 'cadastrar':
        [$d, $erro] = dados_funcionario();
        if ($erro || $d['senha'] === '') {
            voltar($erro ?: 'Informe a senha de acesso.');
            break;
        }
        try {
            $conn->begin_transaction();
            $id = db_inserir("INSERT INTO funcionario (nome_funcionario, cpf_funcionario, cns_funcionario, telefone_funcionario, email_funcionario,
                                  cargo_funcionario, registro_conselho, especialidade_funcionario) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 'ssssssss',
                $d['nome'], $d['cpf'], $d['cns'], $d['telefone'], $d['email'], $d['cargo'], $d['registro'], $d['esp']);
            db("INSERT INTO usuario (login_usuario, senha_usuario, perfil_usuario, funcionario_id) VALUES (?, ?, ?, ?)", 'sssi',
                $d['cpf'], password_hash($d['senha'], PASSWORD_DEFAULT), $d['cargo'], $id);
            $conn->commit();
            auditoria('Criar', 'Funcionário', $id);
            mensagem('Cadastrado com sucesso', 'listar-funcionarios');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            voltar($e->getCode() == 1062 ? 'Já existe um usuário com este CPF.' : 'Não foi possível cadastrar.');
        }
        break;

    case 'editar':
        [$d, $erro] = dados_funcionario();
        $id = (int)($_POST["id"] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        if ($erro) {
            voltar($erro);
            break;
        }
        try {
            $conn->begin_transaction();
            db("UPDATE funcionario SET nome_funcionario = ?, cpf_funcionario = ?, cns_funcionario = ?, telefone_funcionario = ?, email_funcionario = ?,
                    cargo_funcionario = ?, registro_conselho = ?, especialidade_funcionario = ?, ativo_funcionario = ?
                WHERE id_funcionario = ?", 'ssssssssii',
                $d['nome'], $d['cpf'], $d['cns'], $d['telefone'], $d['email'], $d['cargo'], $d['registro'], $d['esp'], $ativo, $id);
            db("UPDATE usuario SET login_usuario = ?, perfil_usuario = ?, ativo_usuario = ? WHERE funcionario_id = ?", 'ssii', $d['cpf'], $d['cargo'], $ativo, $id);
            if ($d['senha'] !== '') {
                db("UPDATE usuario SET senha_usuario = ? WHERE funcionario_id = ?", 'si', password_hash($d['senha'], PASSWORD_DEFAULT), $id);
            }
            $conn->commit();
            auditoria('Editar', 'Funcionário', $id);
            mensagem('Editado com sucesso', 'listar-funcionarios');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            voltar($e->getCode() == 1062 ? 'Já existe outro usuário com este CPF.' : 'Não foi possível editar.');
        }
        break;

	case 'excluir':
        $id = (int)($_REQUEST["id"] ?? 0);
        if ($id === id_funcionario()) {
            mensagem('Você não pode excluir o seu próprio usuário.', 'listar-funcionarios');
            break;
        }
        try {
            db("DELETE FROM funcionario WHERE id_funcionario = ?", 'i', $id);
            auditoria('Excluir', 'Funcionário', $id);
            mensagem('Excluído com sucesso', 'listar-funcionarios');
        } catch (mysqli_sql_exception $e) {
            mensagem('Não é possível excluir: o funcionário tem atendimentos ou registros no histórico. Edite e desmarque "Ativo" para bloquear o acesso.', 'listar-funcionarios');
        }
		break;
	}
?>
