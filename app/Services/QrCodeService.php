<?php
declare(strict_types=1);
namespace App\Services;
final class QrCodeService {
    public function dataUri(string $payload): ?string { $library='/usr/share/phpqrcode/qrlib.php'; if(!is_file($library)||!extension_loaded('gd'))return null; require_once $library; ob_start(); \QRcode::png($payload, null, QR_ECLEVEL_M, 5, 2); $png=(string)ob_get_clean(); return $png===''?null:'data:image/png;base64,'.base64_encode($png); }
}
