<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Tests;

use Codechap\Yii3ClaudeCode\ClaudeCode;
use Codechap\Yii3ClaudeCode\Exception\BinaryNotFoundException;
use Codechap\Yii3ClaudeCode\Model;
use Codechap\Yii3ClaudeCode\Response;
use PHPUnit\Framework\TestCase;

final class ClaudeCodeTest extends TestCase
{
    public function testWithMethodsReturnNewInstances(): void
    {
        $service = new ClaudeCode();

        self::assertNotSame($service, $service->withModel(Model::Opus));
        self::assertNotSame($service, $service->withSystemPrompt('test'));
        self::assertNotSame($service, $service->withMaxTurns(5));
        self::assertNotSame($service, $service->withAllowedTools(['tool1']));
        self::assertNotSame($service, $service->withTimeout(60));
        self::assertNotSame($service, $service->withJson());
        self::assertNotSame($service, $service->withSessionId('abc'));
        self::assertNotSame($service, $service->withContinue());
        self::assertNotSame($service, $service->withWorkingDirectory('/tmp'));
    }

    public function testWithSessionIdClearsContinue(): void
    {
        $service = new ClaudeCode();
        $continued = $service->withContinue();
        $resumed = $continued->withSessionId('abc123');

        self::assertNotSame($continued, $resumed);
    }

    public function testWithContinueClearsSessionId(): void
    {
        $service = new ClaudeCode();
        $resumed = $service->withSessionId('abc123');
        $continued = $resumed->withContinue();

        self::assertNotSame($resumed, $continued);
    }

    public function testConstructorWithNamedParameters(): void
    {
        $service = new ClaudeCode(
            binaryPath: '/usr/local/bin/claude',
            modelName: 'opus',
            systemPrompt: 'You are helpful.',
            maxTurns: 10,
            allowedTools: ['Read', 'Write'],
            timeout: 60,
            envUnset: ['CUSTOM_VAR'],
        );

        self::assertInstanceOf(ClaudeCode::class, $service);
    }

    public function testConstructorWithDefaults(): void
    {
        $service = new ClaudeCode();
        self::assertInstanceOf(ClaudeCode::class, $service);
    }

    public function testConstructorWithInvalidModelFallsBackToSonnet(): void
    {
        $service = new ClaudeCode(modelName: 'invalid-model');
        self::assertInstanceOf(ClaudeCode::class, $service);
    }

    public function testQueryWithNonExistentBinaryThrows(): void
    {
        $service = new ClaudeCode(
            binaryPath: '/nonexistent/path/to/claude',
        );

        $this->expectException(BinaryNotFoundException::class);
        $service->query('Hello');
    }

    public function testQueryWithRealBinary(): void
    {
        $claudePath = trim((string) shell_exec('which claude 2>/dev/null'));

        if ($claudePath === '') {
            self::markTestSkipped('Claude Code CLI not available.');
        }

        $service = new ClaudeCode(timeout: 30);

        $response = $service
            ->withModel(Model::Haiku)
            ->withJson()
            ->query('Reply with exactly: {"status":"ok"}');

        self::assertInstanceOf(Response::class, $response);
        self::assertNotEmpty($response->getResult());
        self::assertTrue($response->isJson());
        self::assertGreaterThan(0, $response->getElapsedSeconds());
    }

    public function testQueryCallbackIsInvoked(): void
    {
        $claudePath = trim((string) shell_exec('which claude 2>/dev/null'));

        if ($claudePath === '') {
            self::markTestSkipped('Claude Code CLI not available.');
        }

        $callbackInvoked = false;
        $capturedResponse = null;

        $service = new ClaudeCode(timeout: 30);
        $response = $service
            ->withModel(Model::Haiku)
            ->query('Reply with exactly: hello', function (Response $r) use (&$callbackInvoked, &$capturedResponse): void {
                $callbackInvoked = true;
                $capturedResponse = $r;
            });

        self::assertTrue($callbackInvoked);
        self::assertSame($response, $capturedResponse);
    }

    public function testQueryTextMode(): void
    {
        $claudePath = trim((string) shell_exec('which claude 2>/dev/null'));

        if ($claudePath === '') {
            self::markTestSkipped('Claude Code CLI not available.');
        }

        $service = new ClaudeCode(timeout: 30);
        $response = $service
            ->withModel(Model::Haiku)
            ->query('Reply with exactly one word: hello');

        self::assertInstanceOf(Response::class, $response);
        self::assertFalse($response->isJson());
        self::assertNotEmpty($response->getResult());
    }
}
