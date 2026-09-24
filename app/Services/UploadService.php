<?php
declare(strict_types=1);
namespace App\Services;
final class UploadService {
    private const IMAGE_MIMES = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    public function __construct(private readonly string $publicPath, private readonly string $privatePath, private readonly int $maxBytes) {}
    public function store(array $file, bool $private = true, bool $imageOnly = false): string {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) throw new \RuntimeException('Invalid upload.');
        if ((int)$file['size'] < 1 || (int)$file['size'] > $this->maxBytes) throw new \RuntimeException('File exceeds permitted size.');
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $allowed = $imageOnly ? self::IMAGE_MIMES : self::IMAGE_MIMES + ['application/pdf'=>'pdf'];
        if (!isset($allowed[$mime])) throw new \RuntimeException('Unsupported file type.');
        if (isset(self::IMAGE_MIMES[$mime]) && @getimagesize($file['tmp_name']) === false) throw new \RuntimeException('Invalid image upload.');
        if ($mime === 'application/pdf' && file_get_contents($file['tmp_name'], false, null, 0, 5) !== '%PDF-') throw new \RuntimeException('Invalid PDF upload.');
        $root = $private ? $this->privatePath : $this->publicPath; if (!is_dir($root) && !mkdir($root,0750,true) && !is_dir($root)) throw new \RuntimeException('Upload storage is unavailable.');
        $filename = bin2hex(random_bytes(20)) . '.' . $allowed[$mime]; $target = $root . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) throw new \RuntimeException('Upload could not be saved.'); chmod($target,0640); return $filename;
    }
}
