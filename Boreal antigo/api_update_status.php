<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

function json_response($success, $message, $data = []) {
    http_response_code($success ? 200 : 400);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Método de requisição inválido.');
}

if (!isLoggedIn() || !isGestor()) {
    json_response(false, 'Acesso não autorizado.');
}

$input = json_decode(file_get_contents('php://input'), true);

$localId = $input['comodo_id'] ?? null;
$novoStatus = $input['status'] ?? null;
$observacao = isset($input['observacao']) ? sanitizeInput($input['observacao']) : '';
$funcionarioIds = $input['funcionario_ids'] ?? [];

if (empty($localId) || empty($novoStatus)) {
    json_response(false, 'Dados insuficientes para atualizar o status.');
}

try {
    $pdo->beginTransaction();

    $dataInstalacao = ($novoStatus == 'instalado') ? date('Y-m-d H:i:s') : null;
    $stmtUpdateLocal = $pdo->prepare("UPDATE locais SET status = ?, observacao = ?, data_instalacao = ? WHERE id = ?");
    $stmtUpdateLocal->execute([$novoStatus, $observacao, $dataInstalacao, $localId]);

    $stmtDelete = $pdo->prepare("DELETE FROM instalacoes WHERE comodo_id = ?");
    $stmtDelete->execute([$localId]);

    if ($novoStatus == 'instalado' && !empty($funcionarioIds)) {
        $funcionarios_ids_string = implode(',', array_map('intval', $funcionarioIds));
        $stmtInsertInst = $pdo->prepare("INSERT INTO instalacoes (comodo_id, data_instalacao, observacoes, funcionarios_ids) VALUES (?, ?, ?, ?)");
        $stmtInsertInst->execute([$localId, $dataInstalacao, $observacao, $funcionarios_ids_string]);
    }

    $pdo->commit();
    json_response(true, 'Status atualizado com sucesso!');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(false, 'Erro de banco de dados: ' . $e->getMessage());
}
?>