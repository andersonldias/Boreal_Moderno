<?php
// Funções auxiliares do sistema

// Verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Verificar se o usuário é gestor
function isGestor() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'gestor';
}

// Verificar se o usuário é funcionário
function isFuncionario() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'funcionario';
}

// Verificar permissão de acesso
function checkPermission($requiredRole = 'gestor') {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
    
    if ($requiredRole === 'gestor' && !isGestor()) {
        header('Location: dashboard.php?error=permission_denied');
        exit();
    }
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

// Função para formatar data e hora
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '-';
    $dateObj = new DateTime($datetime);
    return $dateObj->format($format);
}

// Função para calcular percentual
function calculatePercentage($completed, $total) {
    if ($total == 0) return 0;
    return round(($completed / $total) * 100, 1);
}

// Função para gerar slug
function generateSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

// Função para validar e sanitizar input
function sanitizeInput($input) {
    if ($input === null) {
        return null;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para registrar log de atividades
function logActivity($userId, $action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

// Função para enviar notificação
function sendNotification($userId, $title, $message, $type = 'info') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type]);
    } catch (Exception $e) {
        error_log("Erro ao enviar notificação: " . $e->getMessage());
    }
}

// Função para obter notificações não lidas
function getUnreadNotifications($userId) {
    return fetchAll("SELECT * FROM notifications WHERE user_id = ? AND read_status = 0 ORDER BY created_at DESC", [$userId]);
}

// Função para marcar notificação como lida
function markNotificationAsRead($notificationId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET lida = 1 WHERE id = ?");
        $stmt->execute([$notificationId]);
        return true;
    } catch (Exception $e) {
        error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
        return false;
    }
}

// Função para obter status das obras
function getObraStatus($percentual) {
    if ($percentual == 100) return 'Concluída';
    if ($percentual >= 80) return 'Em Finalização';
    if ($percentual >= 50) return 'Em Andamento';
    if ($percentual >= 20) return 'Iniciada';
    return 'Planejada';
}

// Função para obter cor do status
function getStatusColor($status) {
    switch ($status) {
        case 'Concluída': return 'success';
        case 'Em Finalização': return 'info';
        case 'Em Andamento': return 'warning';
        case 'Iniciada': return 'primary';
        case 'Planejada': return 'secondary';
        default: return 'secondary';
    }
}

// Função para verificar se há não conformidades
function hasNonConformities($obraId) {
    global $pdo;
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM comodos WHERE obra_id = ? AND observacao IS NOT NULL AND observacao != ''");
    $stmt->execute([$obraId]);
    return $stmt->fetchColumn() > 0;
}

// Função para obter estatísticas da obra
function getObraStats($obraId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
                    COUNT(*) as total_comodos
        FROM comodos 
        WHERE obra_id = ?
    ");
    $stmt->execute([$obraId]);
    $stats = $stmt->fetch();
    
    $stats['percentual'] = calculatePercentage($stats['instalados'], $stats['total']);
    $stats['status'] = getObraStatus($stats['percentual']);
    
    return $stats;
}
?>
