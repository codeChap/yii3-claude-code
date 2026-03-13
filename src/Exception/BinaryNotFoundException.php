<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Exception;

use Throwable;

use function sprintf;

/**
 * Thrown when the Claude Code CLI binary cannot be found at the configured
 * or auto-detected path.
 */
final class BinaryNotFoundException extends ClaudeCodeException
{
    public function __construct(
        string $path = '',
        Throwable|null $previous = null,
    ) {
        $message = $path !== ''
            ? sprintf('Claude Code CLI binary not found at configured path: %s', $path)
            : 'Claude Code CLI binary not found in PATH.';

        parent::__construct($message, previous: $previous);
    }

    public function getName(): string
    {
        return 'Claude Code Binary Not Found';
    }

    public function getSolution(): ?string
    {
        return 'Install the Claude Code CLI (https://docs.anthropic.com/en/docs/claude-code) '
            . 'and ensure the "claude" binary is in your PATH, '
            . 'or set "binaryPath" in your params under "codechap/yii3-claude-code". '
            . 'Note: when running under a web server, the PATH may differ from your shell — '
            . 'set an explicit "binaryPath" or use "envSet" to pass the correct PATH/HOME.';
    }
}
