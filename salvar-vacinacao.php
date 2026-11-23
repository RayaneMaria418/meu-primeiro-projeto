<?php
$paciente_id = (int)($_POST['paciente_id'] ?? 0);
$cal = db_um("SELECT * FROM vacina_calendario WHERE id_calendario = ?", 'i', $_POST['calendario_id'] ?? 0);
$lote = db_um("SELECT e.*, m.tipo_item FROM estoque e JOIN medicamento m ON m.id_medicamento = e.medicamento_id WHERE e.id_estoque = ?", 'i', $_POST['estoque_id'] ?? 0);
$via = $_POST['via'] ?? '';
$data = $_POST['data'] ?? '';

if (!$paciente_id || !$cal || !$lote || !in_array($via, ['Intramuscular', 'Subcutânea', 'Intradérmica', 'Oral'], true) || $data === '' || $data > hoje()) {
    voltar('Preencha todos os campos corretamente.');
    return;
}
// RN-RF16: validar lote, fabricante e validade antes de registrar
if ($lote->tipo_item !== 'Vacina' || !$lote->fabricante_estoque || $lote->dt_vali_estoque < $data || $lote->dt_vali_estoque < hoje() || $lote->qt_estoque < 1) {
    voltar('Lote inválido: verifique se é uma vacina, se está dentro da validade e se há doses em estoque.');
    return;
}
if (db_valor("SELECT COUNT(*) FROM vacinacao WHERE paciente_id = ? AND calendario_id = ?", 'ii', $paciente_id, $cal->id_calendario) > 0) {
    voltar('Esta dose já está registrada no cartão do paciente.');
    return;
}
try {
    $conn->begin_transaction();
    $id = db_inserir("INSERT INTO vacinacao (paciente_id, calendario_id, estoque_id, via_administracao, dt_aplicacao, aplicador_id) VALUES (?, ?, ?, ?, ?, ?)",
        'iiissi', $paciente_id, $cal->id_calendario, $lote->id_estoque, $via, $data, id_funcionario());
    // UC16 passo 5: baixa de 1 dose no estoque da sala de vacinas
    db("UPDATE estoque SET qt_estoque = qt_estoque - 1 WHERE id_estoque = ?", 'i', $lote->id_estoque);
    registrar_movimentacao($lote->id_estoque, 'Saída por vacinação', 1, 'VAC-' . $id, "{$cal->vacina} - {$cal->dose}");
    $conn->commit();
    auditoria('Criar', 'Vacinação', $id, $paciente_id, "{$cal->vacina} - {$cal->dose}");
} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    voltar('Erro ao registrar a vacina.');
    return;
}
mensagem("Vacina {$cal->vacina} ({$cal->dose}) registrada e dose baixada do estoque.", 'cartao-vacina&id=' . $paciente_id);
?>
