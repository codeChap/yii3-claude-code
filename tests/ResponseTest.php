<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Tests;

use Codechap\Yii3ClaudeCode\Exception\ParseException;
use Codechap\Yii3ClaudeCode\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testGetters(): void
    {
        $response = new Response(
            result: 'Hello world',
            rawOutput: '{"result":"Hello world","session_id":"abc"}',
            sessionId: 'abc',
            elapsedSeconds: 1.234,
            json: true,
        );

        self::assertSame('Hello world', $response->getResult());
        self::assertSame('{"result":"Hello world","session_id":"abc"}', $response->getRawOutput());
        self::assertSame('abc', $response->getSessionId());
        self::assertSame(1.234, $response->getElapsedSeconds());
        self::assertTrue($response->isJson());
    }

    public function testNullSessionId(): void
    {
        $response = new Response('text', 'text', null, 0.5, false);

        self::assertNull($response->getSessionId());
        self::assertFalse($response->isJson());
    }

    public function testToArrayDecodesJson(): void
    {
        $json = '{"name":"Claude","version":3}';
        $response = new Response($json, $json, null, 0.1, true);

        $expected = ['name' => 'Claude', 'version' => 3];
        self::assertSame($expected, $response->toArray());
    }

    public function testToArrayThrowsOnInvalidJson(): void
    {
        $response = new Response('not json', 'not json', null, 0.1, true);

        $this->expectException(ParseException::class);
        $response->toArray();
    }

    public function testToStringReturnsResult(): void
    {
        $response = new Response('Hello world', 'raw', null, 0.1, false);

        self::assertSame('Hello world', (string) $response);
    }
}
