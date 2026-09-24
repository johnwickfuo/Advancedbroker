<?php
declare(strict_types=1);
namespace App\Exceptions;

class HttpException extends \RuntimeException {
    public function __construct(public readonly int $status, string $message='', ?\Throwable $previous=null) {
        parent::__construct($message !== '' ? $message : 'HTTP error', 0, $previous);
    }
}
