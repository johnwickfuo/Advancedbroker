<?php
declare(strict_types=1);
namespace App\Support;

final class Logger {
    public function __construct(private readonly string $directory) {}
    public function error(string $message, array $context = []): void { $this->write('app', 'error', $message, $context); }
    public function security(string $message, array $context = []): void { $this->write('security', 'warning', $message, $context); }
    public function financial(string $message, array $context = []): void { $this->write('financial', 'info', $message, $context); }
    public function job(string $message, array $context = []): void { $this->write('jobs', 'info', $message, $context); }
    private function write(string $channel, string $level, string $message, array $context): void { if (!is_dir($this->directory)) mkdir($this->directory, 0750, true); $record = ['time' => gmdate('c'), 'level' => $level, 'message' => $message, 'context' => $this->redact($context)]; file_put_contents($this->directory . '/' . $channel . '-' . gmdate('Y-m-d') . '.log', json_encode($record, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX); }
    private function redact(array $context): array { $sensitive=['password','token','secret','authorization','cookie','otp','recovery','passport','iban','account_number','bank','document','kyc','file']; foreach($context as $key=>$value){$lower=strtolower((string)$key);foreach($sensitive as $needle)if(str_contains($lower,$needle)){$context[$key]='[redacted]';continue 2;}if(is_array($value))$context[$key]=$this->redact($value);}return $context; }
}
