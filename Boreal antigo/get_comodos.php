<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

$obraId = $_GET['obra_id'] ?? '';

if (!$obraId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da obra não fornecido']);
    exit();
}

try {
    $obra = fetchOne("SELECT usa_hierarquia_locais FROM obras WHERE id = ?", [$obraId]);

    if (!$obra) {
        http_response_code(404);
        echo json_encode(['error' => 'Obra não encontrada']);
        exit();
    }

    $comodos = [];
    if ($obra['usa_hierarquia_locais']) {
        $locais = fetchAll("SELECT id, parent_id, nome, tipo FROM locais WHERE id_obra = ? ORDER BY nome ASC", [$obraId]);
        $locaisMap = [];
        foreach ($locais as $local) {
            $locaisMap[$local['id']] = $local;
        }

        function getFullPath($local, $locaisMap) {
            $path = [$local['nome']];
            $current = $local;
            while ($current['parent_id'] !== null && isset($locaisMap[$current['parent_id']])) {
                $current = $locaisMap[$current['parent_id']];
                array_unshift($path, $current['nome']);
            }
            return implode(' - ', $path);
        }

        foreach ($locais as $local) {
            if ($local['tipo'] === 'comodo') {
                $comodos[] = [
                    'id' => $local['id'],
                    'nome' => getFullPath($local, $locaisMap)
                ];
            }
        }

    } else {
        $comodos = fetchAll("SELECT id, nome FROM comodos WHERE obra_id = ? ORDER BY nome ASC", [$obraId]);
    }
    
    header('Content-Type: application/json');
    echo json_encode($comodos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>
