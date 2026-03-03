<?php
// Central session hardening and initialization
// Include this file at the top of pages instead of calling session_start() directly.

// Only proceed once
if (session_status() === PHP_SESSION_NONE) {
    // Strict mode prevents uninitialized sessions
    ini_set('session.use_strict_mode', 1);

    // HTTPOnly cookies to mitigate XSS stealing
    ini_set('session.cookie_httponly', 1);

    // Set secure flag only when HTTPS is detected.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isHttps) {
        ini_set('session.cookie_secure', 1);
    } else {
        // Keep cookie_secure off for local dev; do not force here to avoid breaking HTTP dev environments
        ini_set('session.cookie_secure', 0);
    }

    // Configure SameSite; prefer Lax to allow top-level GET navigations while preventing some CSRF
    $cookieParams = session_get_cookie_params();
    $lifetime = $cookieParams['lifetime'] ?? 0;
    $path = $cookieParams['path'] ?? '/';
    $domain = $cookieParams['domain'] ?? '';
    $secure = $isHttps;
    $httponly = true;

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => 'Lax'
        ]);
    } else {
        // Older PHP: set without samesite, and rely on header fallback where possible
        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    }

    session_start();

    // Regenerate session id on first initialization to avoid fixation
    if (empty($_SESSION['__session_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['__session_initiated'] = time();
    }

    // Idle timeout (seconds)
    $idleTimeout = 1800; // 30 minutes
    if (isset($_SESSION['__last_activity']) && (time() - $_SESSION['__last_activity']) > $idleTimeout) {
        // Destroy and restart session to enforce timeout
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION['__session_initiated'] = time();
    }
    $_SESSION['__last_activity'] = time();
}

// Helper: call on successful login to rotate session and set user metadata
if (!function_exists('session_secure_login')) {
    function session_secure_login($userId, $role = null) {
        // regenerate session id on privilege change/login
        session_regenerate_id(true);
        $_SESSION['__session_initiated'] = time();
        $_SESSION['__last_activity'] = time();
        $_SESSION['user_id'] = $userId;
        if ($role !== null) $_SESSION['user_role'] = $role;
    }
}

?>