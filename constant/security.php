<?php
// Guardar como: constant/security.php
require_once __DIR__ . '/connect.php';

function security_client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function security_log(string $eventType, string $severity = 'INFO', string $description = '', ?int $userId = null, ?string $email = null): void {
    global $connect;
    $ip = security_client_ip();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);
    $allowed = ['INFO','WARNING','CRITICAL'];
    if (!in_array($severity, $allowed, true)) {
        $severity = 'INFO';
    }
    $stmt = $connect->prepare("INSERT INTO security_events (user_id,email,event_type,severity,ip_address,user_agent,description) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('issssss', $userId, $email, $eventType, $severity, $ip, $ua, $description);
    $stmt->execute();
    $stmt->close();
}

function security_record_attempt(string $email, bool $success): void {
    global $connect;
    $ip = security_client_ip();
    $ok = $success ? 1 : 0;
    $stmt = $connect->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?,?,?)");
    $stmt->bind_param('ssi', $email, $ip, $ok);
    $stmt->execute();
    $stmt->close();
}

function security_failed_count(string $email, int $minutes = 15): int {
    global $connect;
    $ip = security_client_ip();
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM login_attempts WHERE email=? AND ip_address=? AND success=0 AND attempted_at >= (NOW() - INTERVAL ? MINUTE)");
    $stmt->bind_param('ssi', $email, $ip, $minutes);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

function security_clear_failed_attempts(string $email): void {
    global $connect;
    $ip = security_client_ip();
    $stmt = $connect->prepare("DELETE FROM login_attempts WHERE email=? AND ip_address=? AND success=0");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $stmt->close();
}

function security_open_bruteforce_incident(string $email, int $failures): void {
    global $connect;
    $ip = security_client_ip();

    $stmt = $connect->prepare("SELECT id FROM security_incidents WHERE incident_type='BRUTE_FORCE_DETECTED' AND status <> 'RESOLVED' AND related_email=? AND ip_address=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        return;
    }

    $desc = "Se detectaron {$failures} intentos fallidos de autenticacion en una ventana de 15 minutos.";
    $stmt = $connect->prepare("INSERT INTO security_incidents (incident_type,severity,status,related_email,ip_address,description) VALUES ('BRUTE_FORCE_DETECTED','CRITICAL','OPEN',?,?,?)");
    $stmt->bind_param('sss', $email, $ip, $desc);
    $stmt->execute();
    $stmt->close();
}

function security_verify_password_and_migrate(int $userId, string $plainPassword, string $storedHash): bool {
    global $connect;

    // Compatibilidad temporal con el legado MD5 del proyecto original.
    if (preg_match('/^[a-f0-9]{32}$/i', $storedHash)) {
        if (!hash_equals(strtolower($storedHash), md5($plainPassword))) {
            return false;
        }

        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $connect->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param('si', $newHash, $userId);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    if (!password_verify($plainPassword, $storedHash)) {
        return false;
    }

    if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $connect->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param('si', $newHash, $userId);
        $stmt->execute();
        $stmt->close();
    }
    return true;
}

function security_is_locked(string $email): bool {
    global $connect;
    $ip = security_client_ip();
    $stmt = $connect->prepare("SELECT id FROM security_events WHERE event_type='BRUTE_FORCE_DETECTED' AND email=? AND ip_address=? AND created_at > NOW() - INTERVAL 15 MINUTE LIMIT 1");
    $stmt->bind_param('ss', $email, $ip);
    $stmt->execute();
    $locked = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $locked;
}
