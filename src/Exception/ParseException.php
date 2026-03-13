<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Exception;

use Throwable;

/**
 * Thrown when the Claude Code CLI response cannot be parsed,
 * typically due to invalid JSON in the response wrapper.
 */
final class ParseException extends ClaudeCodeException
{
    public function __construct(
        string $message = 'Failed to parse Claude Code CLI response.',
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function getName(): string
    {
        return 'Claude Code Parse Error';
    }

    public function getSolution(): ?string
    {
        return 'The response from Claude Code CLI could not be parsed. '
            . 'Check Claude CLI version compatibility and ensure --output-format json produces valid output.';
    }
}
