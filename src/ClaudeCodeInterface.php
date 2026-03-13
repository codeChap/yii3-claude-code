<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode;

/**
 * Contract for the Claude Code CLI wrapper.
 *
 * All configuration methods are immutable — they return a new instance
 * with the specified setting applied, leaving the original unchanged.
 *
 * Usage example:
 *
 * ```php
 * $response = $claudeCode
 *     ->withModel(Model::Haiku)
 *     ->withJson()
 *     ->query('Summarize this text');
 *
 * // Multi-turn conversation
 * $r1 = $claudeCode->query('What is the capital of France?');
 * $r2 = $claudeCode->withSessionId($r1->getSessionId())->query('And Germany?');
 * ```
 */
interface ClaudeCodeInterface
{
    /**
     * Return a new instance configured with the given model.
     */
    public function withModel(Model $model): static;

    /**
     * Return a new instance configured with the given system prompt.
     */
    public function withSystemPrompt(string $prompt): static;

    /**
     * Return a new instance configured with the given max agent turns.
     */
    public function withMaxTurns(int $maxTurns): static;

    /**
     * Return a new instance configured with the given allowed tools.
     *
     * @param array<string> $tools
     */
    public function withAllowedTools(array $tools): static;

    /**
     * Return a new instance configured with the given process timeout in seconds.
     */
    public function withTimeout(int $seconds): static;

    /**
     * Return a new instance with JSON mode enabled or disabled.
     *
     * When enabled, uses `--output-format json` and parses the response wrapper.
     * When disabled, uses `--output-format text`.
     */
    public function withJson(bool $json = true): static;

    /**
     * Return a new instance configured to resume a specific conversation.
     *
     * Passes `--resume <sessionId>` to the CLI.
     */
    public function withSessionId(string $sessionId): static;

    /**
     * Return a new instance configured to continue the last conversation.
     *
     * Passes `--continue` to the CLI.
     */
    public function withContinue(bool $continue = true): static;

    /**
     * Return a new instance configured with a working directory for the claude process.
     */
    public function withWorkingDirectory(string $path): static;

    /**
     * Send a prompt to Claude Code CLI and return the response.
     *
     * @param string $prompt The prompt to send.
     * @param callable(Response): void|null $onResponse Optional callback invoked with the Response.
     *
     * @throws Exception\ClaudeCodeException On CLI errors.
     * @throws Exception\BinaryNotFoundException If the claude binary cannot be found.
     * @throws Exception\TimeoutException If the process exceeds the configured timeout.
     * @throws Exception\ParseException If the response cannot be parsed.
     */
    public function query(string $prompt, ?callable $onResponse = null): Response;
}
