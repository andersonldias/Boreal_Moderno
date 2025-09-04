<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Resposta padrão de erro
function errorResponse($message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// Verifica se o usuário é gestor
if (!isLoggedIn() || !isGestor()) {
    errorResponse('Acesso negado.');
}

// Verifica o método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método de requisição inválido.');
}

// Obtém os dados da requisição
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['action'])) {
    errorResponse('Dados inválidos ou ação não especificada.');
}

$action = $data['action'];

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'create':
            if (empty($data['id_obra']) || empty($data['nome']) || empty($data['tipo'])) {
                errorResponse('Parâmetros insuficientes para criar local.');
            }
            $parent_id = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO locais (id_obra, parent_id, nome, tipo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['id_obra'], $parent_id, sanitizeInput($data['nome']), sanitizeInput($data['tipo'])]);
            $lastId = $pdo->lastInsertId();
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Local criado com sucesso!', 'new_id' => $lastId]);
            break;

        case 'update':
            if (empty($data['id']) || !isset($data['nome'])) {
                errorResponse('ID do local ou novo nome não fornecido.');
            }
            $stmt = $pdo->prepare("UPDATE locais SET nome = ? WHERE id = ?");
            $stmt->execute([sanitizeInput($data['nome']), $data['id']]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Local atualizado com sucesso!']);
            break;

        case 'delete':
            if (empty($data['id'])) {
                errorResponse('ID do local não fornecido.');
            }
            // A constraint ON DELETE CASCADE no banco de dados cuidará da exclusão dos filhos.
            $stmt = $pdo->prepare("DELETE FROM locais WHERE id = ?");
            $stmt->execute([$data['id']]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Local e seus sub-itens foram excluídos com sucesso!']);
            break;

        default:
            errorResponse('Ação desconhecida.');
            break;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse('Erro no servidor: ' . $e->getMessage());
}
?>