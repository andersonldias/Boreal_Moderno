<?php
header('Content-Type: application/json');

require __DIR__ . '/config/database.php';

// Validação de entrada
if (!isset($_GET['id_obra']) || !filter_var($_GET['id_obra'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['error' => 'O parâmetro id_obra é obrigatório e deve ser um inteiro.']);
    exit;
}

$id_obra = (int)$_GET['id_obra'];
$parent_id = isset($_GET['parent_id']) ? filter_var($_GET['parent_id'], FILTER_VALIDATE_INT) : null;

// Para a página de instalações, queremos todos os dados do local, incluindo status, etc.
$sql = "";
$params = [];

if ($parent_id) {
    // Busca filhos de um local específico
    $sql = "SELECT * FROM locais WHERE id_obra = :id_obra AND parent_id = :parent_id ORDER BY nome ASC";
    $params = ['id_obra' => $id_obra, 'parent_id' => $parent_id];
} else {
    // Busca os locais raiz da obra
    $sql = "SELECT * FROM locais WHERE id_obra = :id_obra AND parent_id IS NULL ORDER BY nome ASC";
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