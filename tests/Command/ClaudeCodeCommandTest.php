<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Tests\Command;

use Codechap\Yii3ClaudeCode\ClaudeCodeInterface;
use Codechap\Yii3ClaudeCode\Command\ClaudeCodeCommand;
use Codechap\Yii3ClaudeCode\Exception\ClaudeCodeException;
use Codechap\Yii3ClaudeCode\Model;
use Codechap\Yii3ClaudeCode\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ClaudeCodeCommandTest extends TestCase
{
    public function testSuccessfulQuery(): void
    {
        $response = new Response('Hello world', 'raw', 'session-123', 1.5, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('query')
            ->with('test prompt')
            ->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test prompt']);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Hello world', $tester->getDisplay());
    }

    public function testSuccessfulQueryVerboseShowsSessionId(): void
    {
        $response = new Response('Hello', 'raw', 'session-456', 2.345, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test'], ['verbosity' => 128]); // VERBOSE

        $display = $tester->getDisplay();
        self::assertStringContainsString('Session ID: session-456', $display);
        self::assertStringContainsString('2.345s', $display);
    }

    public function testFailureShowsError(): void
    {
        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->method('query')
            ->willThrowException(new ClaudeCodeException('Something went wrong'));

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Something went wrong', $tester->getDisplay());
    }

    public function testInvalidModelShowsError(): void
    {
        $mock = $this->createMock(ClaudeCodeInterface::class);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--model' => 'invalid']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Invalid model', $tester->getDisplay());
    }

    public function testModelOptionCallsWithModel(): void
    {
        $response = new Response('ok', 'ok', null, 0.1, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('withModel')
            ->with(Model::Opus)
            ->willReturn($mock);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--model' => 'opus']);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testJsonOptionCallsWithJson(): void
    {
        $response = new Response('{}', '{}', null, 0.1, true);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('withJson')
            ->willReturn($mock);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--json' => true]);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testResumeOptionCallsWithSessionId(): void
    {
        $response = new Response('ok', 'ok', 'sess-789', 0.1, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('withSessionId')
            ->with('sess-789')
            ->willReturn($mock);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--resume' => 'sess-789']);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testContinueOptionCallsWithContinue(): void
    {
        $response = new Response('ok', 'ok', null, 0.1, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('withContinue')
            ->willReturn($mock);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--continue' => true]);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testApiKeyOptionCallsWithApiKey(): void
    {
        $response = new Response('ok', 'ok', null, 0.1, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('withApiKey')
            ->with('sk-ant-test-key')
            ->willReturn($mock);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--api-key' => 'sk-ant-test-key']);

        self::assertSame(0, $tester->getStatusCode());
    }

    public function testSystemPromptOptionCallsWithSystemPrompt(): void
    {
        $response = new Response('ok', 'ok', null, 0.1, false);

        $mock = $this->createMock(ClaudeCodeInterface::class);
        $mock->expects(self::once())
            ->method('withSystemPrompt')
            ->with('Be concise')
            ->willReturn($mock);
        $mock->method('query')->willReturn($response);

        $command = new ClaudeCodeCommand($mock);
        $tester = new CommandTester($command);
        $tester->execute(['prompt' => 'test', '--system-prompt' => 'Be concise']);

        self::assertSame(0, $tester->getStatusCode());
    }
}
