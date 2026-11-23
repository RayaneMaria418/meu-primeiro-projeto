<?php
/** Encontra o item no catálogo (nome + concentração + unidade) ou cria um novo. */
function medicamento_do_catalogo($nome, $conc, $unid, $tipo, $controlado, $dose)
{
    $m = db_um("SELECT id_medicamento FROM medicamento WHERE nm_medicamento = ? AND concentracao_medicamento = ? AND unidade_medida = ?", 'sss', $nome, $conc, $unid);
    if ($m) {
        db("UPDATE medicamento SET tipo_item = ?, controlado = ?, dose_maxima = ? WHERE id_medicamento = ?", 'sisi', $tipo, $controlado, $dose, $m->id_medicamento);
        return $m->id_medicamento;
    }
    return db_inserir("INSERT INTO medicamento (nm_medicamento, concentracao_medicamento, unidade_medida, tipo_item, controlado, dose_maxima) VALUES (?, ?, ?, ?, ?, ?)",
        'ssssis', $nome, $conc, $unid, $tipo, $controlado, $dose);
}

$nome = trim($_POST["nome_medicamento"] ?? '');
$conc = trim($_POST["concentracao"] ?? '');
$unid = trim($_POST["unidade_medida"] ?? '');
$tipo = in_array($_POST['tipo_item'] ?? '', ['Medicamento', 'Vacina', 'Insumo'], true) ? $_POST['tipo_item'] : 'Medicamento';
$qtd = (int)($_POST["quantidade"] ?? 0);
$lote = trim($_POST["lote"] ?? '');
$fabricante = trim($_POST["fabricante"] ?? '');
$validade = $_POST["data_validade"] ?? '';
$controlado = isset($_POST['controlado']) ? 1 : 0;
$dose = trim($_POST['dose_maxima'] ?? '') ?: null;

switch ($_REQUEST["acao"] ?? '') {

    case 'cadastrar':
        if ($nome === '' || $conc === '' || $unid === '' || $qtd < 1 || $lote === '' || $fabricante === '' || $validade === '') {
            voltar('Preencha todos os campos obrigatórios.');
            break;
        }
        if ($validade < hoje()) {
            voltar('Não é permitido dar entrada em lote vencido.');
            break;
        }
        try {
            $conn->begin_transaction();
            $mid = medicamento_do_catalogo($nome, $conc, $unid, $tipo, $controlado, $dose);
            $id = db_inserir("INSERT INTO estoque (medicamento_id, lote_estoque, fabricante_estoque, dt_vali_estoque, qt_estoque) VALUES (?, ?, ?, ?, ?)",
                'isssi', $mid, $lote, $fabricante, $validade, $qtd);
            $mov = registrar_movimentacao($id, 'Entrada', $qtd, trim($_POST['documento'] ?? '') ?: null, 'Entrada por nota fiscal');
            $conn->commit();
            auditoria('Criar', 'Estoque', $id, null, "$nome lote $lote");
            mensagem('Item salvo com sucesso. Comprovante de entrada gerado.', 'comprovante&id=' . $mov);
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            voltar($e->getCode() == 1062 ? 'Este lote já está cadastrado para este medicamento. Use "Movimentar" para registrar nova entrada.' : 'Não foi possível salvar.');
        }
        break;

    case 'editar':
        $id = (int)($_POST["id"] ?? 0);
        $atual = db_um("SELECT * FROM estoque WHERE id_estoque = ?", 'i', $id);
        if (!$atual || $nome === '' || $qtd < 0 || $validade === '') {
            voltar('Dados inválidos.');
            break;
        }
        try {
            $conn->begin_transaction();
            db("UPDATE medicamento SET nm_medicamento = ?, concentracao_medicamento = ?, unidade_medida = ?, tipo_item = ?, controlado = ?, dose_maxima = ?
                WHERE id_medicamento = ?", 'ssssisi', $nome, $conc, $unid, $tipo, $controlado, $dose, $atual->medicamento_id);
            db("UPDATE estoque SET lote_estoque = ?, fabricante_estoque = ?, dt_vali_estoque = ?, qt_estoque = ? WHERE id_estoque = ?",
                'sssii', $lote, $fabricante, $validade, $qtd, $id);
            // RN-RF19: toda alteração de saldo gera movimentação assinada
            if ($qtd !== (int)$atual->qt_estoque) {
                registrar_movimentacao($id, 'Ajuste de inventário', $qtd - (int)$atual->qt_estoque, null, 'Ajuste na edição do item');
            }
            $conn->commit();
            auditoria('Editar', 'Estoque', $id);
            mensagem('Editado com sucesso', 'listar-estoque');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            mensagem('Não foi possível editar.', 'listar-estoque');
        }
        break;

	case 'excluir':
        $id = (int)($_REQUEST["id"] ?? 0);
        // Só pode excluir lote lançado por engano (sem nenhuma saída registrada)
        $saidas = db_valor("SELECT COUNT(*) FROM movimentacao_estoque WHERE estoque_id = ? AND tipo_movimentacao <> 'Entrada'", 'i', $id);
        $vacinas = db_valor("SELECT COUNT(*) FROM vacinacao WHERE estoque_id = ?", 'i', $id);
        if ($saidas > 0 || $vacinas > 0) {
            mensagem('Este lote já tem movimentações registradas e não pode ser excluído. Use "Movimentar" > Baixa por avaria.', 'listar-estoque');
            break;
        }
        try {
            $conn->begin_transaction();
            db("DELETE FROM movimentacao_estoque WHERE estoque_id = ?", 'i', $id);
            db("DELETE FROM estoque WHERE id_estoque = ?", 'i', $id);
            $conn->commit();
            auditoria('Excluir', 'Estoque', $id);
            mensagem('Excluído com sucesso', 'listar-estoque');
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            mensagem('Não foi possível excluir.', 'listar-estoque');
        }
		break;
	}
?>
