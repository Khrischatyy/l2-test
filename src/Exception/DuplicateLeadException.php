<?php

namespace App\Exception;

class DuplicateLeadException extends \RuntimeException
{
    public function __construct(string $message = 'Duplicate lead detected', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 