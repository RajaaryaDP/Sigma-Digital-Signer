<?php

require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\Byte;

class VerificationQrCode
{
    public static function url(string $token): string
    {
        return self::baseUrl() . rawurlencode($token);
    }

    public static function dataUri(string $token): string
    {
        $options = new QROptions([
            'outputType'      => 'svg',
            'outputBase64'    => false,
        ]);
        $svg = (new QRCode($options))->render(self::url($token));

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public static function matrix(string $token): array
    {
        $qrCode = new QRCode(new QROptions(['addQuietzone' => true]));
        $qrCode->addSegment(new Byte(self::url($token)));

        return $qrCode->getQRMatrix()->getMatrix(true);
    }

    public static function baseUrl(): string
    {
        if (defined('SIGMA_VERIFY_BASE_URL') && is_string(SIGMA_VERIFY_BASE_URL) && SIGMA_VERIFY_BASE_URL !== '') {
            return rtrim(SIGMA_VERIFY_BASE_URL, '/') . '/verify?token=';
        }

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $allowedHosts = ['localhost', '127.0.0.1', 'sigma.local', 'tik.pnj.ac.id', 'www.tik.pnj.ac.id'];
        $baseUrlPart = defined('BASE_URL') ? BASE_URL : '';

        if (preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/i', $host)) {
            $hostName = strtolower((string) preg_replace('/:\d+$/', '', $host));
            if (in_array($hostName, $allowedHosts, true)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                return rtrim($protocol . $host . $baseUrlPart, '/') . '/verify?token=';
            }
        }

        return 'http://localhost:5000' . $baseUrlPart . '/verify?token=';
    }
}
