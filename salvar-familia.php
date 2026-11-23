<?php
switch ($_REQUEST["acao"] ?? '') {
    case 'cadastrar':
        $id = db_inserir("INSERT INTO familia (nome_familia, cep_familia, endereco_familia, microarea_id) VALUES (?, ?, ?, ?)", 'sssi',
            trim($_POST['nome']), so_digitos($_POST['cep']), trim($_POST['endereco']), (int)$_POST['microarea_id']);
        auditoria('Criar', 'Família', $id);
        $paciente_id = (int)($_POST['paciente_id'] ?? 0);
        if ($paciente_id) {
            db("UPDATE paciente SET familia_id = ? WHERE id_paciente = ?", 'ii', $id, $paciente_id);
            auditoria('Editar', 'Paciente', $paciente_id, $paciente_id, 'Vinculado à família ' . $id);
            mensagem('Família cadastrada e paciente vinculado com sucesso', 'listar-familias');
        } else {
            mensagem('Família cadastrada com sucesso', 'listar-familias');
        }
        break;

    case 'vincular':
        $paciente_id = (int)$_POST['paciente_id'];
        $familia_id = (int)$_POST['familia_id'];
        db("UPDATE paciente SET familia_id = ? WHERE id_paciente = ?", 'ii', $familia_id, $paciente_id);
        auditoria('Editar', 'Paciente', $paciente_id, $paciente_id, 'Vinculado à família ' . $familia_id);
        mensagem('Paciente incluído no grupo familiar com sucesso', 'listar-familias');
        break;

    case 'excluir':
        $id = (int)($_REQUEST['id'] ?? 0);
        try {
            db("DELETE FROM familia WHERE id_familia = ?", 'i', $id);
            auditoria('Excluir', 'Família', $id);
            mensagem('Excluído com sucesso', 'listar-familias');
        } catch (mysqli_sql_exception $e) {
            mensagem('Não é possível excluir: a família tem pacientes vinculados ou visitas registradas.', 'listar-familias');
        }
        break;
}
?>
