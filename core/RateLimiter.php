<?php

/**
 * Rate Limiter / Throttling Ganda (Sesi + IP) untuk Endpoint Autentikasi — VULN-10
 *
 * Melindungi endpoint `/auth/login` dan `/auth/register` dari serangan *brute-force*
 * serta enumerasi `(NIM/NIP, email)` civitas dengan dua lapis pembatas:
 *  1. Lapis Sesi (ketat): maks 5 kegagalan dalam 5 menit -> jeda 60 detik.
 *  2. Lapis IP (tahan penghapusan cookie, ramah NAT kampus): maks 25 kegagalan
 *     dalam 10 menit -> jeda 120 detik (hanya memakai REMOTE_ADDR, tidak percaya X-Forwarded-For).
 */
class RateLimiter
{
    public const SESSION_MAX_ATTEMPTS    = 5;
    public const SESSION_WINDOW_SECONDS  = 300; // 5 menit
    public const SESSION_LOCKOUT_SECONDS = 60;  // 1 menit

    public const IP_MAX_ATTEMPTS    = 25;
    public const IP_WINDOW_SECONDS  = 600; // 10 menit
    public const IP_LOCKOUT_SECONDS = 120; // 2 menit

    private const SESSION_KEY = '_sigma_rate_limit';

    /**
     * Mengembalikan IP klien langsung dari REMOTE_ADDR (anti spoofing header X-Forwarded-For).
     */
    public static function getClientIp(): string
    {
        $raw = $_SERVER['REMOTE_ADDR'] ?? '';
        if (is_string($raw) && trim($raw) !== '') {
            return trim($raw);
        }
        return '0.0.0.0';
    }

    /**
     * Memeriksa apakah permintaan untuk `$scope` (mis. 'login' atau 'register') diizinkan.
     *
     * @return array{allowed: bool, retry_after: int, reason: ?string}
     */
    public static function check(
        string $scope,
        ?string $ip = null,
        ?int $now = null,
        ?string $bucketDir = null
    ): array {
        $now      = $now ?? time();
        $scopeKey = self::normalizeScope($scope);
        $clientIp = $ip ?? self::getClientIp();

        $sessionRetry = self::checkSessionLock($scopeKey, $now);
        $ipRetry      = self::checkIpLock($scopeKey, $clientIp, $now, $bucketDir);

        if ($sessionRetry > 0 || $ipRetry > 0) {
            $retryAfter = max($sessionRetry, $ipRetry);
            $reason     = ($ipRetry >= $sessionRetry && $ipRetry > 0) ? 'ip' : 'session';
            return [
                'allowed'     => false,
                'retry_after' => $retryAfter,
                'reason'      => $reason,
            ];
        }

        return [
            'allowed'     => true,
            'retry_after' => 0,
            'reason'      => null,
        ];
    }

    /**
     * Mencatat satu percobaan gagal pada lapis sesi dan lapis IP.
     *
     * @return array{allowed: bool, retry_after: int, remaining: int}
     */
    public static function recordFailure(
        string $scope,
        ?string $ip = null,
        ?int $now = null,
        ?string $bucketDir = null
    ): array {
        $now      = $now ?? time();
        $scopeKey = self::normalizeScope($scope);
        $clientIp = $ip ?? self::getClientIp();

        $sessionRes = self::recordSessionFailure($scopeKey, $now);
        $ipRes      = self::recordIpFailure($scopeKey, $clientIp, $now, $bucketDir);

        $retryAfter = max($sessionRes['retry_after'], $ipRes['retry_after']);
        return [
            'allowed'     => $retryAfter === 0,
            'retry_after' => $retryAfter,
            'remaining'   => min($sessionRes['remaining'], $ipRes['remaining']),
        ];
    }

    /**
     * Menghapus catatan kegagalan sesi untuk `$scope` setelah aksi berhasil.
     */
    public static function clearSession(string $scope): void
    {
        $scopeKey = self::normalizeScope($scope);
        if (isset($_SESSION[self::SESSION_KEY][$scopeKey])) {
            unset($_SESSION[self::SESSION_KEY][$scopeKey]);
        }
    }

    private static function normalizeScope(string $scope): string
    {
        $clean = preg_replace('/[^a-z0-9_-]/i', '', strtolower(trim($scope)));
        return ($clean !== null && $clean !== '') ? $clean : 'default';
    }

    private static function checkSessionLock(string $scopeKey, int $now): int
    {
        if (!isset($_SESSION[self::SESSION_KEY][$scopeKey]) || !is_array($_SESSION[self::SESSION_KEY][$scopeKey])) {
            return 0;
        }
        $bucket = $_SESSION[self::SESSION_KEY][$scopeKey];
        $lockedUntil = (int) ($bucket['locked_until'] ?? 0);
        if ($lockedUntil > $now) {
            return $lockedUntil - $now;
        }
        return 0;
    }

    /**
     * @return array{retry_after: int, remaining: int}
     */
    private static function recordSessionFailure(string $scopeKey, int $now): array
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $bucket = $_SESSION[self::SESSION_KEY][$scopeKey] ?? ['attempts' => [], 'locked_until' => 0];
        if (!is_array($bucket)) {
            $bucket = ['attempts' => [], 'locked_until' => 0];
        }

        $result = self::applyFailureToBucket(
            $bucket,
            $now,
            self::SESSION_MAX_ATTEMPTS,
            self::SESSION_WINDOW_SECONDS,
            self::SESSION_LOCKOUT_SECONDS
        );
        $_SESSION[self::SESSION_KEY][$scopeKey] = $result['bucket'];

        return [
            'retry_after' => $result['retry_after'],
            'remaining'   => $result['remaining'],
        ];
    }

    private static function resolveBucketDir(?string $overrideDir): ?string
    {
        $dir = ($overrideDir !== null && $overrideDir !== '')
            ? $overrideDir
            : rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'sigma_ratelimit';

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
                return null;
            }
        }
        return is_writable($dir) ? $dir : null;
    }

    private static function bucketFilePath(string $dir, string $scopeKey, string $clientIp): string
    {
        $hash = hash('sha256', $scopeKey . '|' . $clientIp);
        return $dir . DIRECTORY_SEPARATOR . 'rl_' . $hash . '.json';
    }

    private static function checkIpLock(string $scopeKey, string $clientIp, int $now, ?string $bucketDir): int
    {
        $dir = self::resolveBucketDir($bucketDir);
        if ($dir === null) {
            return 0;
        }

        $path = self::bucketFilePath($dir, $scopeKey, $clientIp);
        if (!is_file($path) || !is_readable($path)) {
            return 0;
        }

        $fp = @fopen($path, 'r');
        if ($fp === false) {
            return 0;
        }

        $lockedUntil = 0;
        if (@flock($fp, LOCK_SH)) {
            $raw  = stream_get_contents($fp);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($data)) {
                $lockedUntil = (int) ($data['locked_until'] ?? 0);
            }
            @flock($fp, LOCK_UN);
        }
        fclose($fp);

        return ($lockedUntil > $now) ? ($lockedUntil - $now) : 0;
    }

    /**
     * @return array{retry_after: int, remaining: int}
     */
    private static function recordIpFailure(string $scopeKey, string $clientIp, int $now, ?string $bucketDir): array
    {
        $dir = self::resolveBucketDir($bucketDir);
        if ($dir === null) {
            return ['retry_after' => 0, 'remaining' => self::IP_MAX_ATTEMPTS];
        }

        $path = self::bucketFilePath($dir, $scopeKey, $clientIp);
        $fp   = @fopen($path, 'c+');
        if ($fp === false) {
            return ['retry_after' => 0, 'remaining' => self::IP_MAX_ATTEMPTS];
        }

        $retryAfter = 0;
        $remaining  = self::IP_MAX_ATTEMPTS;

        if (@flock($fp, LOCK_EX)) {
            rewind($fp);
            $raw    = stream_get_contents($fp);
            $bucket = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
            if (!is_array($bucket)) {
                $bucket = ['attempts' => [], 'locked_until' => 0];
            }

            $res = self::applyFailureToBucket(
                $bucket,
                $now,
                self::IP_MAX_ATTEMPTS,
                self::IP_WINDOW_SECONDS,
                self::IP_LOCKOUT_SECONDS
            );

            $retryAfter = $res['retry_after'];
            $remaining  = $res['remaining'];

            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($res['bucket']));
            fflush($fp);
            @flock($fp, LOCK_UN);
        }
        fclose($fp);

        return [
            'retry_after' => $retryAfter,
            'remaining'   => $remaining,
        ];
    }

    /**
     * @param array<string, mixed> $bucket
     * @return array{bucket: array{attempts: int[], locked_until: int}, retry_after: int, remaining: int}
     */
    private static function applyFailureToBucket(
        array $bucket,
        int $now,
        int $maxAttempts,
        int $windowSeconds,
        int $lockoutSeconds
    ): array {
        $lockedUntil = (int) ($bucket['locked_until'] ?? 0);
        if ($lockedUntil > $now) {
            return [
                'bucket'      => ['attempts' => [], 'locked_until' => $lockedUntil],
                'retry_after' => $lockedUntil - $now,
                'remaining'   => 0,
            ];
        }

        $rawAttempts = isset($bucket['attempts']) && is_array($bucket['attempts']) ? $bucket['attempts'] : [];
        $cutoff      = $now - $windowSeconds;
        $attempts    = [];
        foreach ($rawAttempts as $ts) {
            $tsInt = (int) $ts;
            if ($tsInt > $cutoff && $tsInt <= $now + 60) {
                $attempts[] = $tsInt;
            }
        }
        $attempts[] = $now;

        if (count($attempts) >= $maxAttempts) {
            $newLock = $now + $lockoutSeconds;
            return [
                'bucket'      => ['attempts' => [], 'locked_until' => $newLock],
                'retry_after' => $lockoutSeconds,
                'remaining'   => 0,
            ];
        }

        return [
            'bucket'      => ['attempts' => $attempts, 'locked_until' => 0],
            'retry_after' => 0,
            'remaining'   => max(0, $maxAttempts - count($attempts)),
        ];
    }
}
