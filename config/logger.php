<?php
if (!function_exists('logActivity')) {
    function logActivity($pdo, $action, $details = '') {
        $user_id = $_SESSION['user_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $action, $details, $ip]);
        } catch (Exception $e) {
            // Silently fail or log to file if DB logging fails
            error_log("Logging error: " . $e->getMessage());
        }
    }
}
?>
