<?php
require_once __DIR__ . '/config.php';
// Pengaturan khusus instance (tidak ikut git): mis. define('TRUST_CF_IP', true); bila di belakang Cloudflare.
if (is_file(__DIR__ . '/config.local.php')) require_once __DIR__ . '/config.local.php';
if (!defined('TRUST_CF_IP')) define('TRUST_CF_IP', false);

function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function clientIp(): string {
    if (TRUST_CF_IP && !empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

// Cookie sesi PHP: HttpOnly + SameSite=Lax (+ Secure bila HTTPS)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (isHttps()) ini_set('session.cookie_secure', '1');
date_default_timezone_set('Asia/Jakarta');

function getSetting(string $key, string $default = ''): string {
    try {
        $st = getDB()->prepare("SELECT value FROM settings WHERE key=?");
        $st->execute([$key]);
        $r = $st->fetch();
        return $r ? $r['value'] : $default;
    } catch (\Exception $e) {
        return $default;
    }
}

function saveSetting(string $key, string $value): void {
    getDB()->prepare("INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value")->execute([$key, $value]);
}

function getAppTitle(): string { return getSetting('app_title', APP_TITLE); }
function getAppSubtitle(): string { return getSetting('app_subtitle', APP_SUBTITLE); }

function getDB(): PDO {
    static $db = null;
    if ($db) return $db;
    $isNew = !file_exists(DB_FILE);
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec("PRAGMA journal_mode=WAL");
    $db->exec("PRAGMA busy_timeout=5000");
    $db->exec("PRAGMA synchronous=NORMAL");
    $db->exec("PRAGMA cache_size=-20000");
    if ($isNew) initDB($db);
    migrateDB($db);
    return $db;
}

function migrateDB(PDO $db): void {
    // Add settings table if missing
    $db->exec("CREATE TABLE IF NOT EXISTS settings(key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '')");
    // Add user_report_access table if missing
    $db->exec("CREATE TABLE IF NOT EXISTS user_report_access(user_id INTEGER NOT NULL, report_id INTEGER NOT NULL, PRIMARY KEY(user_id, report_id))");
    // Add menus table if missing (sidebar grouping)
    $db->exec("CREATE TABLE IF NOT EXISTS menus(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,sort_order INTEGER DEFAULT 0,is_default INTEGER DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    // Add menu_id column to reports if missing
    $cols = array_column($db->query("PRAGMA table_info(reports)")->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (!in_array('menu_id', $cols)) {
        $db->exec("ALTER TABLE reports ADD COLUMN menu_id INTEGER");
    }
    // Audit log table
    $db->exec("CREATE TABLE IF NOT EXISTS audit_log(id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, username TEXT, action TEXT NOT NULL, detail TEXT, ip TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    // Seed default menu (LAPORAN) if none exists
    $cnt = (int)$db->query("SELECT COUNT(*) FROM menus")->fetchColumn();
    if ($cnt === 0) {
        $db->prepare("INSERT INTO menus(name,sort_order,is_default) VALUES('LAPORAN',0,1)")->execute();
    }
}

function getMenus(): array {
    return getDB()->query("SELECT * FROM menus ORDER BY sort_order,name")->fetchAll();
}
function getDefaultMenuId(): ?int {
    $r = getDB()->query("SELECT id FROM menus WHERE is_default=1 ORDER BY sort_order LIMIT 1")->fetch();
    return $r ? (int)$r['id'] : null;
}

function getUserAllowedReports(int $userId): ?array {
    $st = getDB()->prepare("SELECT report_id FROM user_report_access WHERE user_id=?");
    $st->execute([$userId]);
    $ids = $st->fetchAll(PDO::FETCH_COLUMN);
    return empty($ids) ? null : $ids; // null = semua, array = hanya ini
}

function canAccessReport(array $user, int $reportId): bool {
    if ($user['role'] === 'admin') return true;
    $allowed = getUserAllowedReports($user['id']);
    return $allowed === null || in_array($reportId, $allowed);
}

function saveUserReportAccess(int $userId, ?array $reportIds): void {
    $db = getDB();
    $db->prepare("DELETE FROM user_report_access WHERE user_id=?")->execute([$userId]);
    if ($reportIds !== null && !empty($reportIds)) {
        $st = $db->prepare("INSERT INTO user_report_access(user_id, report_id) VALUES(?,?)");
        foreach ($reportIds as $rid) {
            $st->execute([$userId, intval($rid)]);
        }
    }
}

function getAccessibleReports(array $user): array {
    $db = getDB();
    $all = $db->query("SELECT r.id,r.name,r.icon,r.menu_id FROM reports r ORDER BY r.sort_order,r.name")->fetchAll();
    if ($user['role'] === 'admin') return $all;
    $allowed = getUserAllowedReports($user['id']);
    if ($allowed === null) return $all;
    return array_values(array_filter($all, fn($r) => in_array($r['id'], $allowed)));
}

function initDB(PDO $db): void {
    $db->exec("CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,username TEXT UNIQUE NOT NULL,password_hash TEXT NOT NULL,name TEXT NOT NULL,role TEXT NOT NULL DEFAULT 'viewer',is_active INTEGER DEFAULT 1,created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE sessions(token TEXT PRIMARY KEY,user_id INTEGER NOT NULL,expires_at DATETIME NOT NULL)");
    $db->exec("CREATE TABLE connections(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,endpoint_uuid TEXT NOT NULL,api_key_path TEXT DEFAULT '',bearer_token TEXT DEFAULT '',apikey_header TEXT DEFAULT '',salt_key TEXT DEFAULT '',query_params TEXT DEFAULT '{}',sql_reference TEXT DEFAULT '',detected_fields TEXT DEFAULT '[]',is_active INTEGER DEFAULT 1,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE reports(id INTEGER PRIMARY KEY AUTOINCREMENT,connection_id INTEGER NOT NULL,name TEXT NOT NULL,description TEXT DEFAULT '',icon TEXT DEFAULT '',config_fields TEXT DEFAULT '[]',config_filters TEXT DEFAULT '[]',config_charts TEXT DEFAULT '[]',override_params TEXT DEFAULT '',sort_order INTEGER DEFAULT 0,created_by INTEGER,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE data_cache(id INTEGER PRIMARY KEY AUTOINCREMENT,connection_id INTEGER NOT NULL,data_json TEXT NOT NULL,record_count INTEGER DEFAULT 0,synced_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE sync_log(id INTEGER PRIMARY KEY AUTOINCREMENT,connection_id INTEGER NOT NULL,status TEXT NOT NULL,record_count INTEGER DEFAULT 0,duration REAL DEFAULT 0,error_message TEXT DEFAULT '',synced_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    // Password awal acak (bukan default publik). Disimpan di file yang tidak bisa diakses via web.
    $initialPassword = bin2hex(random_bytes(6));
    $hash = password_hash($initialPassword, PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO users(username,password_hash,name,role) VALUES(?,?,?,?)")->execute(['admin', $hash, 'Administrator', 'admin']);
    $credFile = __DIR__ . '/.initial_admin_password';
    @file_put_contents($credFile, "Username: admin\nPassword: " . $initialPassword . "\n\nSegera login, ganti password di menu Profil, lalu hapus file ini.\n");
    @chmod($credFile, 0600);
}

// Pembatasan percobaan login: 5 gagal per 10 menit per IP
function loginBlocked(string $ip): bool {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts(ip TEXT NOT NULL, at INTEGER NOT NULL)");
    $db->prepare("DELETE FROM login_attempts WHERE at < ?")->execute([time() - 600]);
    $st = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip=?");
    $st->execute([$ip]);
    return (int)$st->fetchColumn() >= 5;
}

function recordLoginFailure(string $ip): void {
    getDB()->prepare("INSERT INTO login_attempts(ip, at) VALUES(?, ?)")->execute([$ip, time()]);
}

function clearLoginFailures(string $ip): void {
    getDB()->prepare("DELETE FROM login_attempts WHERE ip=?")->execute([$ip]);
}

function login(string $username, string $password): ?array {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM users WHERE username=? AND is_active=1");
    $st->execute([$username]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) return null;
    $token = bin2hex(random_bytes(32));
    $db->prepare("INSERT INTO sessions(token,user_id,expires_at) VALUES(?,?,datetime('now','+7 days'))")->execute([$token, $user['id']]);
    $db->prepare("DELETE FROM sessions WHERE expires_at < datetime('now')")->execute();
    setcookie('dsc_token', $token, [
        'expires' => time() + SESSION_LIFETIME, 'path' => '/', 'secure' => isHttps(), 'httponly' => true, 'samesite' => 'Lax',
    ]);
    return $user;
}

function logout(): void {
    $token = $_COOKIE['dsc_token'] ?? '';
    if ($token) getDB()->prepare("DELETE FROM sessions WHERE token=?")->execute([$token]);
    setcookie('dsc_token', '', [
        'expires' => time() - 3600, 'path' => '/', 'secure' => isHttps(), 'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function getCurrentUser(): ?array {
    $token = $_COOKIE['dsc_token'] ?? '';
    if (!$token) return null;
    $st = getDB()->prepare("SELECT u.* FROM users u JOIN sessions s ON s.user_id=u.id WHERE s.token=? AND s.expires_at>datetime('now') AND u.is_active=1");
    $st->execute([$token]);
    return $st->fetch() ?: null;
}

function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) { header('Location: ?page=login'); exit; }
    return $user;
}

function requireRole(array $user, string ...$roles): void {
    if (!in_array($user['role'], $roles)) { http_response_code(403); die('<h1>403</h1>'); }
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function jsonResponse(array $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function decryptSicantik(string $encrypted, string $salt): ?string {
    $key = hash('sha256', $salt, true);
    $raw = @hex2bin($encrypted);
    if ($raw === false || strlen($raw) < 28) return null;
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, -16);
    $ct = substr($raw, 12, -16);
    $d = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $d === false ? null : $d;
}

function apiDebugLog(string $msg): void {
    $f = __DIR__ . '/debug_api.log';
    $ts = date('Y-m-d H:i:s');
    file_put_contents($f, "[$ts] $msg\n", FILE_APPEND | LOCK_EX);
}

function callSicantikAPI(array $conn, array $extra = []): array {
    $params = json_decode($conn['query_params'] ?? '{}', true) ?: [];
    if ($extra) $params = array_merge($params, $extra);
    $url = BASE_API_URL . $conn['endpoint_uuid'];
    if ($params) $url .= '?' . http_build_query($params);

    // Headers must match SPLP gateway expectations (same as working asc.weebs.my.id)
    $h = [
        'auth: Bearer ' . trim($conn['bearer_token']),
        'apikey: ' . trim($conn['apikey_header'] ?? ''),
        'Accept: */*',
        'User-Agent: PostmanRuntime/2.2.1',
    ];

    apiDebugLog("=== API CALL ===");
    apiDebugLog("URL: $url");
    apiDebugLog("Headers: " . json_encode(array_map(fn($x) => preg_replace('/^(auth|apikey):.*$/i', '$1: ***', $x), $h)));

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => $h,
    ]);
    $t = microtime(true);
    $resp = curl_exec($ch);
    $dur = round(microtime(true) - $t, 2);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    apiDebugLog("HTTP: $code | Duration: {$dur}s | cURL error: " . ($err ?: 'none'));
    apiDebugLog("Response (first 500): " . substr($resp ?: '(empty)', 0, 500));

    if ($err) return ['success'=>false,'http_status'=>0,'duration'=>$dur,'error'=>$err,'data'=>[]];
    $body = json_decode($resp, true);
    if (!$body) return ['success'=>false,'http_status'=>$code,'duration'=>$dur,'error'=>'Invalid JSON','data'=>[],'raw'=>$resp];

    $dec = [];
    if (isset($body['data']) && is_string($body['data']) && !empty($conn['salt_key'])) {
        $plain = decryptSicantik($body['data'], $conn['salt_key']);
        apiDebugLog("Decrypt: " . ($plain ? 'OK (' . strlen($plain) . ' bytes)' : 'FAILED'));
        if ($plain) $dec = json_decode($plain, true) ?: [];
    } elseif (isset($body['data']) && is_array($body['data'])) {
        $dec = $body['data'];
    }
    if (isset($dec['items'])) $dec = $dec['items'];
    elseif (isset($dec['data'])) $dec = $dec['data'];
    if ($dec && !isset($dec[0])) $dec = [$dec];

    apiDebugLog("Result: " . ($code === 200 && !empty($dec) ? 'SUCCESS' : 'FAIL') . " | Records: " . count($dec));

    return ['success'=>$code===200&&!empty($dec),'http_status'=>$code,'duration'=>$dur,'error'=>'','data'=>$dec,'raw'=>$resp];
}

function detectFields(array $rows): array {
    if (empty($rows)) return [];
    $f = [];
    foreach ($rows[0] as $k => $v) {
        $t = is_numeric($v) ? 'number' : (preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$v) ? 'date' : 'text');
        $f[] = ['key'=>$k, 'label'=>ucwords(str_replace('_',' ',$k)), 'type'=>$t];
    }
    return $f;
}

/**
 * Smart-merge: sync report config_fields with latest detected_fields.
 * - Existing fields: keep label, order, enabled (clear missing flag if field returned)
 * - New fields from API: append at end, enabled=false
 * - Fields gone from API: mark missing=true
 */
function mergeReportFields(PDO $db, int $connId, array $newFields): void {
    $newKeys = array_column($newFields, 'key');
    $newByKey = [];
    foreach ($newFields as $f) $newByKey[$f['key']] = $f;

    $reports = $db->prepare("SELECT id, config_fields FROM reports WHERE connection_id=?");
    $reports->execute([$connId]);
    $update = $db->prepare("UPDATE reports SET config_fields=?, updated_at=datetime('now') WHERE id=?");

    foreach ($reports->fetchAll() as $rpt) {
        $cfg = json_decode($rpt['config_fields'] ?: '[]', true);
        if (empty($cfg)) continue;

        $existingKeys = [];
        // Pass 1: update existing fields
        foreach ($cfg as &$f) {
            $existingKeys[] = $f['key'];
            if (in_array($f['key'], $newKeys)) {
                unset($f['missing']); // field is back / still present
                $f['type'] = $newByKey[$f['key']]['type'] ?? $f['type']; // refresh type
            } else {
                $f['missing'] = true;
            }
        }
        unset($f);

        // Pass 2: append new fields not yet in config
        $order = count($cfg);
        foreach ($newKeys as $k) {
            if (!in_array($k, $existingKeys)) {
                $cfg[] = [
                    'key' => $k,
                    'label' => $newByKey[$k]['label'] ?? ucwords(str_replace('_', ' ', $k)),
                    'type' => $newByKey[$k]['type'] ?? 'text',
                    'enabled' => false,
                    'order' => $order++,
                ];
            }
        }

        $update->execute([json_encode($cfg), $rpt['id']]);
    }
}

function syncConnection(int $id): array {
    $db = getDB();
    $st = $db->prepare("SELECT * FROM connections WHERE id=?");
    $st->execute([$id]);
    $conn = $st->fetch();
    if (!$conn) return ['success'=>false,'error'=>'Not found','data'=>[],'duration'=>0];
    $r = callSicantikAPI($conn);
    // ponytail: single transaction = 1 fsync instead of 3-4, reduces write-lock duration
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO sync_log(connection_id,status,record_count,duration,error_message) VALUES(?,?,?,?,?)")
           ->execute([$id, $r['success']?'ok':'error', count($r['data']), $r['duration'], $r['error']]);
        if ($r['success'] && $r['data']) {
            $db->prepare("DELETE FROM data_cache WHERE connection_id=?")->execute([$id]);
            $db->prepare("INSERT INTO data_cache(connection_id,data_json,record_count) VALUES(?,?,?)")
               ->execute([$id, json_encode($r['data']), count($r['data'])]);
            $newFields = detectFields($r['data']);
            $db->prepare("UPDATE connections SET detected_fields=?,updated_at=datetime('now') WHERE id=?")
               ->execute([json_encode($newFields), $id]);
            // ponytail: smart-merge config_fields for all reports using this connection
            mergeReportFields($db, $id, $newFields);
        }
        $db->commit();
    } catch (\Exception $e) {
        $db->rollBack();
        throw $e;
    }
    return $r;
}

function getCachedData(int $id): array {
    $st = getDB()->prepare("SELECT data_json FROM data_cache WHERE connection_id=? ORDER BY synced_at DESC LIMIT 1");
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ? (json_decode($r['data_json'], true) ?: []) : [];
}

function getLastSync(int $id): ?array {
    $st = getDB()->prepare("SELECT * FROM sync_log WHERE connection_id=? ORDER BY synced_at DESC LIMIT 1");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

// ── Audit Log ──
function auditLog(string $action, string $detail = '', ?array $user = null): void {
    $db = getDB();
    $userId = $user['id'] ?? null;
    $username = $user['username'] ?? '-';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    $ts = date('Y-m-d H:i:s');
    $db->prepare("INSERT INTO audit_log(user_id,username,action,detail,ip,created_at) VALUES(?,?,?,?,?,?)")
       ->execute([$userId, $username, $action, $detail, $ip, $ts]);
}

// ── API Token helpers ──
function generateApiToken(): string {
    $token = bin2hex(random_bytes(32));
    saveSetting('api_token', $token);
    return $token;
}

function validateApiToken(): bool {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return false;
    $stored = getSetting('api_token', '');
    return $stored !== '' && hash_equals($stored, trim($m[1]));
}
