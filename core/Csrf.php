<?php

/**
 * Helper Proteksi CSRF (Cross-Site Request Forgery) — VULN-04
 *
 * Menggunakan pola Synchronizer Token per sesi:
 * - Token acak kriptografis 256-bit (64 karakter hex) via random_bytes(32)
 * - Validasi konstan-waktu menggunakan hash_equals() (anti timing attack)
 * - Mendukung input hidden `_csrf_token` pada POST maupun header `X-CSRF-Token`
 */
class Csrf
{
    public const SESSION_KEY = '_sigma_csrf_token';
    public const FIELD_NAME  = '_csrf_token';
    public const HEADER_NAME = 'HTTP_X_CSRF_TOKEN';

    /**
     * Mengambil token CSRF sesi aktif; membuat baru secara kriptografis bila belum ada.
     */
    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (
            empty($_SESSION[self::SESSION_KEY])
            || !is_string($_SESSION[self::SESSION_KEY])
            || strlen($_SESSION[self::SESSION_KEY]) !== 64
        ) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Merotasi token CSRF sesi (dipanggil pada transisi autentikasi login/logout).
     */
    public static function rotate(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Menghasilkan elemen <input type="hidden"> HTML siap pakai untuk formulir POST.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Memverifikasi token CSRF yang dikirim oleh klien terhadap token di sesi aktif.
     */
    public static function verify(?string $submittedToken = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        if ($submittedToken === null) {
            if (isset($_POST[self::FIELD_NAME]) && is_string($_POST[self::FIELD_NAME])) {
                $submittedToken = $_POST[self::FIELD_NAME];
            } elseif (isset($_SERVER[self::HEADER_NAME]) && is_string($_SERVER[self::HEADER_NAME])) {
                $submittedToken = $_SERVER[self::HEADER_NAME];
            }
        }

        if (!is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($sessionToken, trim($submittedToken));
    }
}
