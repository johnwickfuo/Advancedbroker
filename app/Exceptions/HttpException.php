<?php
declare(strict_types=1);
namespace App\Exceptions;

class HttpException extends \RuntimeException {
    public readonly int $status;
    public function __construct(int|string $first=500,int|string $second='HTTP error',?\Throwable $previous=null) {
        if(is_int($first)){ $this->status=$first; $message=is_string($second)?$second:'HTTP error'; }
        else { $message=$first; $this->status=is_int($second)?$second:500; }
        parent::__construct($message,0,$previous);
    }
}
