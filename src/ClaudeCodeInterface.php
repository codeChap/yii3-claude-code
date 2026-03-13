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
 * // Subscription-based (default)
 * $response = $claudeCode
 *     ->withModel(Model::Haiku)
 *     ->withJson()
 *     ->query('Summarize this text');
 *
 * // API key authentication
 * $response = $claudeCode
 *     ->withApiKey('sk-ant-...')
 *     ->query('Hello');
 *
 * // Multi-turn conversation
 * $r1 = $claudeCode->query('What is the capital of France?');
 * $r2 = $claudeCode->withSessionId($r1->getSessionId())->query('And Germany?');
 *
 * // Custom environment variables
 * $response = $claudeCode
 *     ->withEnv(['HOME' => '/home/myuser'])
 *     ->query('Hello');
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
     * Return a new instance configured with an Anthropic API key.
     *
     * When set, uses API key authentication instead of subscription-based auth.
     * Pass null to clear the API key and revert to subscription mode.
     */
    public function withApiKey(?string $apiKey): static;

    /**
     * Return a new instance with custom environment variables for the subprocess.
     *
     * These are merged into the process environment and take priority over envUnset.
     * Useful for setting HOME, PATH, or other context the claude binary may need.
     *
     * @param array<string, string> $env
     */
    public function withEnv(array $env): static;

    /**
     * Return a new instance with additional CLI flags appended to the command.
     *
     * Supports three forms:
     * - Boolean flags (numeric key): `['--dangerously-skip-permissions']`
     * - Single-value flags (string key): `['--effort' => 'high']`
     * - Multi-value flags (string key, array value): `['--add-dir' => ['/path1', '/path2']]`
     *
     * @param array<int|string, string|array<string>> $flags
     */
    public function withFlags(array $flags): static;

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
