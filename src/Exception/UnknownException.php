<?php

namespace Meritum\StructuredLogging\Exception;

use Throwable;
use Meritum\StructuredLogging\Severity;

final class UnknownException extends DomainException
{
    public function __construct(Throwable $exception)
    {
        $context = [
            'original_class' => $exception::class,
            'original_code'  => $exception->getCode(),
            'original_file'  => $exception->getFile(),
            'original_line'  => $exception->getLine(),
        ];

        parent::__construct(
            $exception->getMessage(),
            Severity::Error,
            $context,
            false,
            $exception->getCode(),
            $exception
        );
    }

    public function getErrorCode(): string
    {
        return 'UNKNOWN_0000';
    }
}
