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

        // PDOException::getCode() returns a SQLSTATE string via PHP engine internals,
        // bypassing the int return type declared on Exception::getCode().
        $code = $exception->getCode();

        parent::__construct(
            $exception->getMessage(),
            Severity::Error,
            $context,
            false,
            is_int($code) ? $code : 0,
            $exception
        );
    }

    public function getErrorCode(): string
    {
        return 'UNKNOWN_0000';
    }
}
