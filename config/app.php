<?php
// ── Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── BASE_URL is detected automatically from where index.php lives, so the
//    project works from any folder name inside htdocs (e.g. /foodBlog, /food_blog)
//    or from the web root. Example: http://localhost/foodBlog  ->  '/foodBlog'
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    define('BASE_URL', rtrim($scriptDir === '/' || $scriptDir === '.' ? '' : $scriptDir, '/'));
}

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MENU_UPLOAD_DIR', UPLOAD_DIR . 'menu/');
define('PROFILE_UPLOAD_DIR', UPLOAD_DIR . 'profiles/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

function redirect(string $path): void {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isMember(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'member';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect('/auth/login');
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        setFlash('error', 'Admin access required.');
        redirect('/auth/login');
    }
}

function requireMember(): void {
    if (!isMember()) {
        redirect('/auth/login');
    }
}

// ── HTML escaping (null-safe)
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ── CSRF protection
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(generateCsrfToken()) . '">';
}

// ── Flash messages
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── JSON helpers (used by the AJAX/API endpoints)
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function jsonError(string $message, int $statusCode = 400): void {
    jsonResponse(['success' => false, 'message' => $message, 'error' => $message], $statusCode);
}

// ── File upload helper
// The file extension is derived from the verified MIME type, NOT from the
// uploaded file name, so a ".php" file disguised as an image can never be stored.
function uploadFile(array $file, string $dest_dir, string $prefix = ''): string|false {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return false;
    if (!is_uploaded_file($file['tmp_name'])) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    if (!is_dir($dest_dir) && !mkdir($dest_dir, 0755, true)) return false;

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) return false;

    $filename = $prefix . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], rtrim($dest_dir, '/\\') . '/' . $filename)) return false;

    return $filename;
}

// ── Delete an uploaded file safely (only inside the given upload folder)
function deleteUploadedFile(?string $filename, string $dir): void {
    if (!$filename) return;
    $path = rtrim($dir, '/\\') . '/' . basename($filename);
    if (is_file($path)) @unlink($path);
}
