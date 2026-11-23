<?php
switch ($_REQUEST["acao"] ?? '') {
    case 'cadastrar':
        $acs = ($_POST['acs_id'] ?? '') !== '' ? (int)$_POST['acs_id'] : null;
        $id = db_inserir("INSERT INTO microarea (nome_microarea, equipe_esf, acs_id) VALUES (?, ?, ?)", 'ssi',
            trim($_POST['nome']), trim($_POST['equipe']), $acs);
        auditoria('Criar', 'Microárea', $id);
        mensagem('Microárea cadastrada com sucesso', 'listar-microareas');
        break;

    case 'excluir':
        $id = (int)($_REQUEST['id'] ?? 0);
        try {
            db("DELETE FROM microarea WHERE id_microarea = ?", 'i', $id);
            auditoria('Excluir', 'Microárea', $id);
            mensagem('Excluído com sucesso', 'listar-microareas');
        } catch (mysqli_sql_exception $e) {
            mensagem('Não é possível excluir: existem famílias vinculadas a esta microárea.', 'listar-microareas');
        }
        break;
}
?>
