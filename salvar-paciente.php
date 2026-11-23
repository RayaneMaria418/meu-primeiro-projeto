<?php
/** Lê e valida os dados do formulário (RN-RF01). Retorna [dados, erro]. */
function dados_paciente()
{
    $d = [
        'nome'      => trim($_POST["nome"] ?? ''),
        'cpf'       => so_digitos($_POST["cpf"] ?? ''),
        'cns'       => so_digitos($_POST["cns"] ?? ''),
        'nasc'      => $_POST["data_nasc"] ?? '',
        'sexo'      => $_POST["sexo"] ?? '',
        'telefone'  => trim($_POST["telefone"] ?? ''),
        'email'     => trim($_POST["email"] ?? ''),
        'endereco'  => trim($_POST["endereco"] ?? ''),
        'cep'       => so_digitos($_POST["cep"] ?? ''),
        'contato'   => trim($_POST["contato_emergencia"] ?? ''),
        'resp_nome' => trim($_POST["responsavel_nome"] ?? ''),
        'resp_cpf'  => so_digitos($_POST["responsavel_cpf"] ?? ''),
        'pcd'       => isset($_POST["pcd"]) ? 1 : 0,
        'lgpd'      => isset($_POST["consentimento_lgpd"]) ? 1 : 0,
        'familia'   => ($_POST["familia_id"] ?? '') !== '' ? (int)$_POST["familia_id"] : null,
    ];
    if ($d['nome'] === '' || $d['nasc'] === '' || !in_array($d['sexo'], ['F', 'M'], true)) {
        return [$d, 'Preencha nome, data de nascimento e sexo.'];
    }
    if ($d['nasc'] > hoje()) {
        return [$d, 'A data de nascimento não pode estar no futuro.'];
    }
    if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
        return [$d, 'CPF inválido.'];
    }
    if ($d['cns'] !== '' && !cns_valido($d['cns'])) {
        return [$d, 'Número do Cartão SUS inválido.'];
    }
    if ($d['cpf'] === '' && $d['cns'] === '') {
        // Recém-nascido sem documentação: vínculo temporário ao CPF do responsável legal
        if (!isset($_POST['recem_nascido']) || $d['resp_nome'] === '' || !cpf_valido($d['resp_cpf'])) {
            return [$d, 'Informe um CPF válido ou o Cartão SUS. Para recém-nascido sem documentos, marque a opção e informe nome e CPF válido do responsável legal.'];
        }
    }
    if ($d['resp_cpf'] !== '' && !cpf_valido($d['resp_cpf'])) {
        return [$d, 'CPF do responsável inválido.'];
    }
    if (!$d['lgpd']) {
        return [$d, 'É necessário registrar o consentimento do paciente para o tratamento dos dados (LGPD).'];
    }
    foreach (['cpf', 'cns', 'cep', 'resp_cpf'] as $campo) {
        if ($d[$campo] === '') {
            $d[$campo] = null;
        }
    }
    return [$d, null];
}

switch ($_REQUEST["acao"] ?? '') {
    case 'cadastrar':
        if (!exigir(ADM, REC)) break;
        [$d, $erro] = dados_paciente();
        if ($erro) {
            voltar($erro);
            break;
        }
        try {
            $id = db_inserir("INSERT INTO paciente (nome_paciente, cpf_paciente, cns_paciente, dt_nasc_paciente, sexo_paciente, telefone_paciente, email_paciente,
                                  endereco_paciente, cep_paciente, contato_emergencia, responsavel_nome, responsavel_cpf, pcd_paciente, consentimento_lgpd, familia_id)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 'ssssssssssssiii',
                $d['nome'], $d['cpf'], $d['cns'], $d['nasc'], $d['sexo'], $d['telefone'], $d['email'], $d['endereco'], $d['cep'],
                $d['contato'], $d['resp_nome'], $d['resp_cpf'], $d['pcd'], $d['lgpd'], $d['familia']);
            auditoria('Criar', 'Paciente', $id, $id);
            mensagem('Cadastrado com sucesso', 'listar');
        } catch (mysqli_sql_exception $e) {
            voltar($e->getCode() == 1062 ? 'Já existe um paciente com este CPF ou Cartão SUS.' : 'Erro ao cadastrar o paciente.');
        }
        break;

    case 'editar':
        if (!exigir(ADM, REC)) break;
        [$d, $erro] = dados_paciente();
        $id = (int)($_POST["id"] ?? 0);
        if ($erro) {
            voltar($erro);
            break;
        }
        try {
            db("UPDATE paciente SET nome_paciente = ?, cpf_paciente = ?, cns_paciente = ?, dt_nasc_paciente = ?, sexo_paciente = ?, telefone_paciente = ?,
                    email_paciente = ?, endereco_paciente = ?, cep_paciente = ?, contato_emergencia = ?, responsavel_nome = ?, responsavel_cpf = ?,
                    pcd_paciente = ?, consentimento_lgpd = ?, familia_id = ?
                WHERE id_paciente = ?", 'ssssssssssssiiii',
                $d['nome'], $d['cpf'], $d['cns'], $d['nasc'], $d['sexo'], $d['telefone'], $d['email'], $d['endereco'], $d['cep'],
                $d['contato'], $d['resp_nome'], $d['resp_cpf'], $d['pcd'], $d['lgpd'], $d['familia'], $id);
            auditoria('Editar', 'Paciente', $id, $id);
            mensagem('Editado com sucesso', 'listar');
        } catch (mysqli_sql_exception $e) {
            voltar($e->getCode() == 1062 ? 'Já existe outro paciente com este CPF ou Cartão SUS.' : 'Erro ao editar.');
        }
        break;

    case 'clinico':
        // Alergias e antecedentes familiares (PEP) - gravados criptografados (AES-256)
        if (!exigir(CLINICOS)) break;
        $id = (int)($_POST["id"] ?? 0);
        db("UPDATE paciente SET alergias_paciente = ?, antecedentes_familiares = ? WHERE id_paciente = ?", 'ssi',
            cripto(trim($_POST['alergias'] ?? '')), cripto(trim($_POST['antecedentes'] ?? '')), $id);
        auditoria('Editar', 'Prontuário', $id, $id, 'Alergias / antecedentes familiares');
        mensagem('Dados clínicos atualizados', 'prontuario&id=' . $id);
        break;

    case 'excluir':
        if (!exigir(ADM, REC)) break;
        $id = (int)($_REQUEST["id"] ?? 0);
        try {
            db("DELETE FROM paciente WHERE id_paciente = ?", 'i', $id);
            auditoria('Excluir', 'Paciente', $id, $id);
            mensagem('Excluído com sucesso', 'listar');
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451) {
                mensagem('Não é possível excluir este paciente pois ele tem AGENDAMENTOS, ATENDIMENTOS ou VACINAS no histórico.', 'listar');
            } else {
                mensagem('Não foi possível excluir o paciente.', 'listar');
            }
        }
        break;
}
?>
