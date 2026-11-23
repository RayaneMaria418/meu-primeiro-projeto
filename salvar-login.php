<?php
// Processado antes do HTML (ver index.php)
function ir_para($pagina, $aviso = null, $tipo = 'danger')
{
    if ($aviso) {
        $_SESSION['aviso'] = $aviso;
        $_SESSION['aviso_tipo'] = $tipo;
    }
    header('Location: index.php' . ($pagina ? '?page=' . $pagina : ''));
    exit;
}

switch ($_REQUEST["acao"] ?? '') {
    case 'entrar':
        $login = so_digitos($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $u = db_um("SELECT u.*, COALESCE(f.nome_funcionario, p.nome_paciente) AS nome, f.registro_conselho, f.ativo_funcionario
                    FROM usuario u
                    LEFT JOIN funcionario f ON f.id_funcionario = u.funcionario_id
                    LEFT JOIN paciente p ON p.id_paciente = u.paciente_id
                    WHERE u.login_usuario = ?", 's', $login);

        $ok = false;
        if ($u && $u->ativo_usuario && ($u->funcionario_id === null || $u->ativo_funcionario)) {
            if (strpos($u->senha_usuario, 'inicial:') === 0) {
                // senha inicial dos dados de exemplo: confere e já troca por hash bcrypt (RNF03)
                $ok = hash_equals(substr($u->senha_usuario, 8), $senha);
                if ($ok) {
                    db("UPDATE usuario SET senha_usuario = ? WHERE id_usuario = ?", 'si', password_hash($senha, PASSWORD_DEFAULT), $u->id_usuario);
                }
            } else {
                $ok = password_verify($senha, $u->senha_usuario);
            }
        }

        if (!$ok) {
            $_SESSION['usuario'] = null;
            auditoria('Falha de login', 'Usuário', null, null, 'Login informado: ' . $login);
            ir_para('login', 'CPF ou senha inválidos.');
        }

        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id_usuario'      => (int)$u->id_usuario,
            'nome'            => $u->nome,
            'perfil'          => $u->perfil_usuario,
            'funcionario_id'  => $u->funcionario_id ? (int)$u->funcionario_id : null,
            'paciente_id'     => $u->paciente_id ? (int)$u->paciente_id : null,
            'registro'        => $u->registro_conselho,
        ];
        db("UPDATE usuario SET ultimo_acesso = ? WHERE id_usuario = ?", 'si', agora(), $u->id_usuario);
        auditoria('Login', 'Usuário', $u->id_usuario);
        ir_para('');
        break;

    case 'primeiro-acesso':
        // Paciente já cadastrado na UBS cria a senha informando CPF + data de nascimento
        $cpf = so_digitos($_POST['cpf'] ?? '');
        $nasc = $_POST['dt_nasc'] ?? '';
        $senha = $_POST['senha'] ?? '';

        if (strlen($senha) < 6 || $senha !== ($_POST['senha2'] ?? '')) {
            ir_para('primeiro-acesso', 'A senha precisa ter pelo menos 6 caracteres e as duas senhas devem ser iguais.');
        }
        $p = db_um("SELECT id_paciente FROM paciente WHERE cpf_paciente = ? AND dt_nasc_paciente = ?", 'ss', $cpf, $nasc);
        if (!$p) {
            ir_para('primeiro-acesso', 'Não encontramos um cadastro com esse CPF e data de nascimento. Procure a recepção da UBS.');
        }
        if (db_valor("SELECT COUNT(*) FROM usuario WHERE login_usuario = ?", 's', $cpf) > 0) {
            ir_para('login', 'Você já possui acesso. Entre com seu CPF e senha.', 'info');
        }
        db("INSERT INTO usuario (login_usuario, senha_usuario, perfil_usuario, paciente_id) VALUES (?, ?, 'Paciente', ?)",
            'ssi', $cpf, password_hash($senha, PASSWORD_DEFAULT), $p->id_paciente);
        auditoria('Criar', 'Usuário', null, $p->id_paciente, 'Primeiro acesso do paciente');
        ir_para('login', 'Acesso criado! Entre com seu CPF e a senha cadastrada.', 'success');
        break;

    case 'sair':
        if (usuario()) {
            auditoria('Logout', 'Usuário', usuario()['id_usuario']);
        }
        $_SESSION = [];
        session_destroy();
        session_start();
        ir_para('login', 'Você saiu do sistema.', 'info');
        break;

    default:
        ir_para('login');
}
?>
