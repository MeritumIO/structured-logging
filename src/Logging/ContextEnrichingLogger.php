<?php

namespace Meritum\StructuredLogging\Logging;

use Stringable;
use Psr\Log\LoggerInterface;
use Meritum\StructuredLogging\ContextEnricher;

final class ContextEnrichingLogger implements LoggerInterface
{
    /**
     * @var ContextEnricher[]
     *
     */
    private readonly array $enrichers;

    public function __construct(
        private readonly LoggerInterface $logger,
        ContextEnricher ...$enrichers
    ) {
        $this->enrichers = $enrichers;
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->log($level, $message, $enrichedContext);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->debug($message, $enrichedContext);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->info($message, $enrichedContext);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->notice($message, $enrichedContext);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->warning($message, $enrichedContext);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->error($message, $enrichedContext);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->critical($message, $enrichedContext);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->alert($message, $enrichedContext);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context);

        $this->logger->emergency($message, $enrichedContext);
    }

    /**
     * @param mixed[] $context
     *
     * @return mixed[]
     */
    private function enrichContext(array $context): array
    {
        foreach ($this->enrichers as $enricher) {
            $context = $enricher->enrich($context);
        }

        return $context;
    }
}
