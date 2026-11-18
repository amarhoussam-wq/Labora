<?php
/**
 * LABORA - Authentication & Authorization Functions
 */

function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        $back = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . BASE_URL . 'auth/login.php' . ($back ? '?redirect=' . $back : ''));
        exit;
    }
}

function requireRole(string ...$roles): void {
    if (!isset($_SESSION['user_id'])) {
        $isLab = in_array('lab_manager', $roles, true);
        $back = urlencode($_SERVER['REQUEST_URI'] ?? '');
        $param = $isLab ? 'role=lab' : '';
        if ($back) {
            $param .= ($param ? '&' : '') . 'redirect=' . $back;
        }
        header('Location: ' . BASE_URL . 'auth/login.php' . ($param ? '?' . $param : ''));
        exit;
    }
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function currentUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function currentUserName(): ?string {
    return $_SESSION['user_name'] ?? null;
}

function currentLabId(): ?int {
    return isset($_SESSION['lab_id']) ? (int)$_SESSION['lab_id'] : null;
}

function loginUser(array $user, ?int $labId = null): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email']= $user['email'];
    $_SESSION['user_role'] = $user['role'];
    if ($labId !== null) {
        $_SESSION['lab_id'] = $labId;
    }
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
