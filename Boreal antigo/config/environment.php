<?php
/**
 * Configurações de Ambiente
 * Sistema de Instalação de Esquadrias
 * 
 * Este arquivo controla as configurações baseadas no ambiente
 * (desenvolvimento, teste, produção)
 */

// Definir ambiente padrão
if (!defined('ENVIRONMENT')) {
    // Detectar ambiente automaticamente
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    if (in_array($host, ['localhost', '127.0.0.1', '::1']) || strpos($host, '.local') !== false) {
        define('ENVIRONMENT', 'development');
    } elseif (strpos($host, 'test') !== false || strpos($host, 'staging') !== false) {
        define('ENVIRONMENT', 'testing');
    } else {
        define('ENVIRONMENT', 'production');
    }
}

// Configurações por ambiente
switch (ENVIRONMENT) {
    case 'development':
        // Desenvolvimento local
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        
        // Configurações de debug
        define('DEBUG', true);
        define('LOG_LEVEL', 'DEBUG');
        
        // Configurações de sessão para desenvolvimento
        ini_set('session.cookie_secure', 0);
        ini_set('session.cookie_samesite', 'Lax');
        
        // Configurações de cache
        define('CACHE_ENABLED', false);
        define('CACHE_DURATION', 0);
        
        break;
        
    case 'testing':
        // Ambiente de teste
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        
        define('DEBUG', true);
        define('LOG_LEVEL', 'INFO');
        
        // Configurações de sessão para teste
        ini_set('session.cookie_secure', 0);
        ini_set('session.cookie_samesite', 'Lax');
        
        // Cache habilitado para teste
        define('CACHE_ENABLED', true);
        define('CACHE_DURATION', 300); // 5 minutos
        
        break;
        
    case 'production':
        // Produção
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        
        define('DEBUG', false);
        define('LOG_LEVEL', 'ERROR');
        
        // Configurações de sessão para produção
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Cache habilitado para produção
        define('CACHE_ENABLED', true);
        define('CACHE_DURATION', 3600); // 1 hora
        
        break;
        
    default:
        // Fallback para desenvolvimento
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        define('DEBUG', true);
        define('LOG_LEVEL', 'DEBUG');
        define('CACHE_ENABLED', false);
        define('CACHE_DURATION', 0);
        break;
}

// Configurações globais
define('APP_NAME', 'Sistema de Instalação de Esquadrias');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de sessão
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.cookie_lifetime', 3600); // 1 hora

// Configurações de upload
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Configurações de segurança
if (ENVIRONMENT === 'production') {
    // Headers de segurança para produção
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

// Configurações de log
define('LOG_DIR', __DIR__ . '/../logs');
define('LOG_FILE', LOG_DIR . '/app.log');
define('ERROR_LOG_FILE', LOG_DIR . '/error.log');

// Função para logging
function appLog($message, $level = 'INFO', $context = []) {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message";
    
    if (!empty($context)) {
        $logEntry .= ' ' . json_encode($context);
    }
    
    $logEntry .= PHP_EOL;
    
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Função para logging de erros
function errorLog($message, $context = []) {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [ERROR] $message";
    
    if (!empty($context)) {
        $logEntry .= ' ' . json_encode($context);
    }
    
    $logEntry .= PHP_EOL;
    
    file_put_contents(ERROR_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Configurar handler de erros personalizado
if (ENVIRONMENT === 'production') {
    set_error_handler(function($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        errorLog("PHP Error: $message in $file on line $line", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);
        
        if (DEBUG) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
    });
    
    set_exception_handler(function($exception) {
        errorLog("Uncaught Exception: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        if (DEBUG) {
            throw $exception;
        } else {
            http_response_code(500);
            include __DIR__ . '/../500.php';
        }
    });
}

// Configurações de cache
if (CACHE_ENABLED) {
    // Criar diretório de cache se não existir
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    define('CACHE_DIR', $cacheDir);
}

// Configurações de backup
define('BACKUP_ENABLED', ENVIRONMENT === 'production');
define('BACKUP_DIR', __DIR__ . '/../backups');
define('BACKUP_RETENTION_DAYS', 30);

if (BACKUP_ENABLED && !is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// Configurações de notificação
define('NOTIFICATIONS_ENABLED', true);
define('EMAIL_NOTIFICATIONS', ENVIRONMENT === 'production');
define('SMS_NOTIFICATIONS', false);

// Configurações de API (para futuras implementações)
define('API_ENABLED', false);
define('API_RATE_LIMIT', 100); // requests por hora
define('API_KEY_REQUIRED', true);

// Configurações de manutenção
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_ALLOWED_IPS', ['127.0.0.1', '::1']);

// Verificar modo de manutenção
if (MAINTENANCE_MODE && !in_array($_SERVER['REMOTE_ADDR'] ?? '', MAINTENANCE_ALLOWED_IPS)) {
    http_response_code(503);
    include __DIR__ . '/../maintenance.php';
    exit;
}

// Log de inicialização
if (defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG') {
    appLog("Sistema inicializado", "DEBUG", [
        'environment' => ENVIRONMENT,
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ]);
}
?>
