<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Exception;

use RuntimeException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

/**
 * Base exception for Claude Code CLI errors.
 *
 * Carries the process exit code and stderr output for diagnostic purposes.
 */
class ClaudeCodeException extends RuntimeException implements FriendlyExceptionInterface
{
    public function __construct(
        string $message = '',
        private readonly ?int $processExitCode = null,
        private readonly string $stderr = '',
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return 'Claude Code CLI Error';
    }

    public function getSolution(): ?string
    {
        return 'Ensure the Claude Code CLI is installed and authenticated. '
            . 'Check your configuration in params under "codechap/yii3-claude-code". '
            . 'Run "claude --version" to verify the CLI is accessible.';
    }

    public function getProcessExitCode(): ?int
    {
        return $this->processExitCode;
    }

    public function getStderr(): string
    {
        return $this->stderr;
    }
}
