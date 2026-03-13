<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Exception;

use Throwable;

use function sprintf;

/**
 * Thrown when the Claude Code CLI process exceeds the configured timeout.
 */
final class TimeoutException extends ClaudeCodeException
{
    public function __construct(
        private readonly int $timeoutSeconds,
        Throwable|null $previous = null,
    ) {
        parent::__construct(
            sprintf('Claude Code CLI process timed out after %d seconds.', $timeoutSeconds),
            previous: $previous,
        );
    }

    public function getName(): string
    {
        return 'Claude Code Timeout';
    }

    public function getSolution(): ?string
    {
        return sprintf(
            'The process exceeded the %d-second timeout. '
            . 'Increase "timeout" in params under "codechap/yii3-claude-code", '
            . 'or simplify your prompt.',
            $this->timeoutSeconds,
        );
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}
