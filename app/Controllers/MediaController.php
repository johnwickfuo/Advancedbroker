<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Support\{Request,Response};
final class MediaController extends Controller {
    public function asset(Request $r): Response {
        $asset = app('country_admin')->asset((int) $r->route('asset'));
        if (!$asset) return new Response('Not found', 404);
        $name = basename((string) $asset['storage_path']);
        if ($name !== (string) $asset['storage_path']) return new Response('Not found', 404);
        $path = dirname(__DIR__, 2) . '/storage/uploads/public/' . $name;
        if (!is_file($path)) return new Response('Not found', 404);
        return new Response((string) file_get_contents($path), 200, ['Content-Type'=>(new \finfo(FILEINFO_MIME_TYPE))->file($path),'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'public, max-age=86400']);
    }
    public function license(Request $r): Response {
        $db = app('database'); $row = $db?->one('SELECT * FROM licenses WHERE id=?', [(int)$r->route('license')]);
        if (!$row || empty($row['document_path'])) return new Response('Not found',404);
        return $this->privateFile((string)$row['document_path'], (string)($row['original_filename'] ?: 'license'), (string)$row['mime_type'], (bool)$r->input('download'));
    }
    public function privateFile(string $storagePath,string $filename,string $mime,bool $download=false): Response {
        $name = basename($storagePath); if ($name !== $storagePath) return new Response('Not found',404);
        $path = dirname(__DIR__,2).'/storage/private/'.$name;
        if (!is_file($path)) return new Response('Not found',404);
        $safe = str_replace(["\r", "\n", '"'], '', basename($filename));
        return new Response((string)file_get_contents($path),200,['Content-Type'=>$mime,'Content-Disposition'=>($download?'attachment':'inline').'; filename="'.$safe.'"','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store, max-age=0','Pragma'=>'no-cache']);
    }
}
