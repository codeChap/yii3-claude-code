<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode;

use Codechap\Yii3ClaudeCode\Exception\ParseException;
use Stringable;

use function is_array;
use function json_decode;
use function json_last_error_msg;
use function sprintf;

/**
 * Immutable value object encapsulating a response from the Claude Code CLI.
 *
 * Provides access to the extracted result text, the raw CLI output, the session ID
 * for multi-turn conversations, elapsed time, and JSON decoding helpers.
 */
final class Response implements Stringable
{
    public function __construct(
        private readonly string $result,
        private readonly string $rawOutput,
        private readonly ?string $sessionId,
        private readonly float $elapsedSeconds,
        private readonly bool $json,
    ) {
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function getRawOutput(): string
    {
        return $this->rawOutput;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getElapsedSeconds(): float
    {
        return $this->elapsedSeconds;
    }

    public function isJson(): bool
    {
        return $this->json;
    }

    /**
     * Decode the result as a JSON array.
     *
     * @return array<mixed>
     *
     * @throws ParseException If the result is not valid JSON.
     */
    public function toArray(): array
    {
        $decoded = json_decode($this->result, true);

        if (!is_array($decoded)) {
            throw new ParseException(
                sprintf('Response result is not valid JSON: %s', json_last_error_msg()),
            );
        }

        return $decoded;
    }

    public function __toString(): string
    {
        return $this->result;
    }
}
