<?php

namespace Meritum\StructuredLogging\Exception;

use Throwable;
use DateTimeImmutable;
use DateTimeInterface;
use Meritum\StructuredLogging\Severity;

abstract class DomainException extends \Exception
{
    public protected(set) Severity $severity;

    /**
     * @var array<string, mixed>
     */
    public protected(set) array $context = [];

    public protected(set) bool $retryable;

    public protected(set) DateTimeImmutable $occurredAt;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $_metadata = null;

    /**
     * @var array<string, mixed>
     */
    public array $metadata {
        get => $this->_metadata ??= $this->captureMetadata();
    }

    /**
     * @var array<string, mixed>
     */
    public array $structuredData {
        get => [
            'error_code'     => $this->getErrorCode(),
            'message'        => $this->getMessage(),
            'severity'       => $this->severity->value,
            'retryable'      => $this->retryable,
            'occurred_at'    => $this->occurredAt->format(DateTimeInterface::RFC3339),
            'detail'         => $this->context,
            'metadata'       => $this->metadata,
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        Severity $severity,
        array $context = [],
        bool $retryable = false,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->severity      = $severity;
        $this->context       = $context;
        $this->retryable     = $retryable;
        $this->occurredAt    = new DateTimeImmutable();
    }

    /**
     * Get the error code
     * Format: PREFIX_NNNN (e.g. DB_0001)
     */
    abstract public function getErrorCode(): string;

    /**
     * @return array<string, mixed>
     */
    protected function captureMetadata(): array
    {
        return [
            'file'        => $this->getFile(),
            'line'        => $this->getLine(),
            'class'       => static::class,
        ];
    }

    protected function generateErrorCodeSuffix(int $code): string
    {
        return str_pad((string) $code, 4, '0', STR_PAD_LEFT);
    }
}
