<?php

namespace App\Exception;

class ResourceNotFoundException extends ApiException
{
    public function __construct(string $resource = 'Resource', string $id = '')
    {
        $message = sprintf('%s with ID %s not found', $resource, $id);
        parent::__construct($message, 404);
    }
} 