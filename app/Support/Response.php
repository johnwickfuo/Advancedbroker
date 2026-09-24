<?php
declare(strict_types=1);
namespace App\Support;
final class Response {
    public function __construct(public string $content = '', public int $status = 200, public array $headers = []) {}
    public static function json(array $data, int $status = 200): self { return new self((string) json_encode($data, JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']); }
    public static function redirect(string $location, int $status = 302): self { return new self('', $status, ['Location' => $location]); }
    public function send(): void { http_response_code($this->status); foreach ($this->headers as $key => $value) header("$key: $value"); echo $this->content; }
}
