<?php

namespace Meritum\StructuredLogging\Test\Context;

use Meritum\StructuredLogging\Context\CorrelationIdEnricher;
use Meritum\StructuredLogging\CorrelationId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CorrelationIdEnricherTest extends TestCase
{
    private const string UUID = '550e8400-e29b-4d94-a716-446655440000';

    #[Test]
    public function test_adds_correlation_id_to_context(): void
    {
        $enricher = new CorrelationIdEnricher(new CorrelationId(self::UUID));

        $result = $enricher->enrich([]);

        $this->assertSame(self::UUID, $result['correlation_id']);
    }

    #[Test]
    public function test_preserves_existing_context(): void
    {
        $enricher = new CorrelationIdEnricher(new CorrelationId(self::UUID));

        $result = $enricher->enrich(['key' => 'value']);

        $this->assertSame('value', $result['key']);
        $this->assertSame(self::UUID, $result['correlation_id']);
    }

    #[Test]
    public function test_does_not_overwrite_existing_correlation_id(): void
    {
        $enricher = new CorrelationIdEnricher(new CorrelationId(self::UUID));

        $result = $enricher->enrich(['correlation_id' => 'existing']);

        $this->assertSame('existing', $result['correlation_id']);
    }

    #[Test]
    public function test_accepts_plain_string(): void
    {
        $enricher = new CorrelationIdEnricher(self::UUID);

        $result = $enricher->enrich([]);

        $this->assertSame(self::UUID, $result['correlation_id']);
    }
}
