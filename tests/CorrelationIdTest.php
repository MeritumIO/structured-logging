<?php

namespace Meritum\StructuredLogging\Test;

use Meritum\StructuredLogging\CorrelationId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Stringable;

final class CorrelationIdTest extends TestCase
{
    private const string VALID_UUID = '550e8400-e29b-4d94-a716-446655440000';

    #[Test]
    public function test_implements_stringable(): void
    {
        $this->assertInstanceOf(Stringable::class, new CorrelationId());
    }

    #[Test]
    public function test_auto_generates_uuid_when_no_argument_given(): void
    {
        $id = new CorrelationId();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->uuid
        );
    }

    #[Test]
    public function test_accepts_valid_uuid_v4(): void
    {
        $id = new CorrelationId(self::VALID_UUID);

        $this->assertSame(self::VALID_UUID, $id->uuid);
    }

    #[Test]
    public function test_generates_uuid_when_invalid_string_given(): void
    {
        $id = new CorrelationId('not-a-uuid');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->uuid
        );
    }

    #[Test]
    public function test_generates_uuid_when_null_given(): void
    {
        $id = new CorrelationId(null);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->uuid
        );
    }

    #[Test]
    public function test_generated_uuids_are_unique(): void
    {
        $a = new CorrelationId();
        $b = new CorrelationId();

        $this->assertNotSame($a->uuid, $b->uuid);
    }

    #[Test]
    public function test_to_string_returns_uuid(): void
    {
        $id = new CorrelationId(self::VALID_UUID);

        $this->assertSame(self::VALID_UUID, (string) $id);
    }

    #[Test]
    public function test_set_overwrites_with_valid_uuid(): void
    {
        $id = new CorrelationId();
        $id->set(self::VALID_UUID);

        $this->assertSame(self::VALID_UUID, $id->uuid);
    }

    #[Test]
    public function test_set_ignores_invalid_uuid(): void
    {
        $id = new CorrelationId();
        $original = $id->uuid;

        $id->set('garbage');

        $this->assertSame($original, $id->uuid);
    }

    #[Test]
    public function test_set_ignores_non_v4_uuid(): void
    {
        $id = new CorrelationId();
        $original = $id->uuid;

        $id->set('550e8400-e29b-1d94-a716-446655440000');

        $this->assertSame($original, $id->uuid);
    }
}
