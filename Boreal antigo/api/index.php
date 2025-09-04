<?php
// Definir cabeçalhos para garantir que o conteúdo seja exibido como HTML
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Adicionar log para depuração
error_log('Request URI: ' . $_SERVER['REQUEST_URI']);

// Get the request path
$request_uri = $_SERVER['REQUEST_URI'];

// Remove query string from the request path
$request_path = strtok($request_uri, '?');

// Se o caminho terminar com .php, processar diretamente
if (preg_match('/\.php$/', $request_path)) {
    // Extrair o nome do arquivo sem o caminho
    $filename = basename($request_path);
    
    // Construir o caminho completo para o arquivo solicitado
    $file_path = __DIR__ . '/../' . $filename;
    
    // Verificar se o arquivo existe
    if (file_exists($file_path)) {
        // Incluir o arquivo solicitado
        require_once $file_path;
        exit;
    }
}

// If the request is for the root, serve the main index.php
if ($request_path === '/' || $request_path === '') {
    require_once __DIR__ . '/../index.php';
    exit;
}

// Get the requested filename
$filename = basename($request_path);

// Construct the full path to the requested file in the parent directory
$file_path = __DIR__ . '/../' . $filename;

// Check if the file exists and is a .php file
if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
    // Include the requested file
    require_once $file_path;
    exit;
} else if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) !== 'php') {
    // Serve arquivos estáticos diretamente
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $mime_types = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2'
    ];
    
    if (isset($mime_types[$extension])) {
        header('Content-Type: ' . $mime_types[$extension]);
    } else {
        header('Content-Type: application/octet-stream');
    }
    
    readfile($file_path);
    exit;
} else {
    // Log the 404 error
    error_log('404 Not Found: ' . $file_path);
    
    // Return a 404 error
    header('HTTP/1.1 404 Not Found');
    echo '<html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested file could not be found.</p></body></html>';
    exit;
}
