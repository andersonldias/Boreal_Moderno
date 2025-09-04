<?php
header('Content-Type: application/json');

require __DIR__ . '/config/database.php';

// Validação básica de entrada
if (!isset($_GET['id_obra']) || !filter_var($_GET['id_obra'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['error' => 'O parâmetro id_obra é obrigatório e deve ser um inteiro.']);
    exit;
}

$id_obra = (int)$_GET['id_obra'];

// O parent_id é opcional. Se não for fornecido, busca os itens raiz (onde parent_id é NULL).
$parent_id = isset($_GET['parent_id']) ? filter_var($_GET['parent_id'], FILTER_VALIDATE_INT) : null;

$sql = "";
$params = [];

if ($parent_id) {
    // Busca filhos de um local específico
    $sql = "SELECT id, nome, tipo FROM locais WHERE id_obra = :id_obra AND parent_id = :parent_id ORDER BY nome ASC";
    $params = ['id_obra' => $id_obra, 'parent_id' => $parent_id];
} else {
    // Busca os locais raiz da obra (Blocos ou Andares, se não houver blocos)
    $sql = "SELECT id, nome, tipo FROM locais WHERE id_obra = :id_obra AND parent_id IS NULL ORDER BY nome ASC";
    $params = ['id_obra' => $id_obra];
}

$locais = fetchAll($sql, $params);

if ($locais === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao consultar o banco de dados.']);
    exit;
}

echo json_encode($locais);
?>