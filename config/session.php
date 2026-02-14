<?php
// config/session.php
session_start();

class Session {
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function delete($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        session_destroy();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
    }

    public static function getUserType() {
        if (isset($_SESSION['admin_id'])) return 'admin';
        if (isset($_SESSION['user_id'])) return 'user';
        return null;
    }
}
?>