<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        public readonly string $codigo,
        string $mensaje,
        public readonly int $statusCode,
        public readonly array $detalles = []
    ) {
        parent::__construct($mensaje, $statusCode);
    }
}