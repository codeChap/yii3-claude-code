<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode;

use Codechap\Yii3ClaudeCode\Exception\BinaryNotFoundException;
use Codechap\Yii3ClaudeCode\Exception\ClaudeCodeException;
use Codechap\Yii3ClaudeCode\Exception\ParseException;
use Codechap\Yii3ClaudeCode\Exception\TimeoutException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function array_key_exists;
use function is_array;
use function is_file;
use function is_executable;
use function is_string;
use function json_decode;
use function json_encode;
use function microtime;
use function round;
use function shell_exec;
use function sprintf;
use function str_starts_with;
use function trim;

/**
 * Claude Code CLI wrapper for Yii3 applications.
 *
 * Executes prompts via the locally installed `claude` binary using a Claude subscription
 * rather than API credits. Supports one-shot queries, multi-turn conversations via
 * `--resume` and `--continue`, JSON mode, and model selection.
 *
 * All configuration methods are immutable — they return a new instance with the
 * specified setting applied, leaving the original unchanged.
 *
 * @see ClaudeCodeInterface
 */
final class ClaudeCode implements ClaudeCodeInterface
{
    private Model $model;
    /** @var array<string> */
    private array $allowedTools;
    /** @var array<string> */
    private array $envUnset;
    private bool $json = false;
    private ?string $sessionId = null;
    private bool $continue = false;
    private ?string $workingDirectory = null;

    /**
     * @param string $binaryPath Path to the claude binary. Empty string for auto-detection.
     * @param string $modelName Model alias: sonnet, opus, or haiku.
     * @param string $systemPrompt Default system prompt for the CLI.
     * @param int|null $maxTurns Maximum number of agent turns.
     * @param array<string> $allowedTools List of tools the CLI is allowed to use.
     * @param int $timeout Process timeout in seconds.
     * @param array<string> $envUnset Environment variables to unset for recursion prevention.
     */
    public function __construct(
        private string $binaryPath = '',
        string $modelName = 'sonnet',
        private string $systemPrompt = '',
        private ?int $maxTurns = null,
        array $allowedTools = [],
        private int $timeout = 300,
        array $envUnset = ['CLAUDECODE', 'ANTHROPIC_API_KEY'],
    ) {
        $this->model = Model::tryFrom($modelName) ?? Model::Sonnet;
        $this->allowedTools = $allowedTools;
        $this->envUnset = $envUnset;
    }

    public function withModel(Model $model): static
    {
        $clone = clone $this;
        $clone->model = $model;
        return $clone;
    }

    public function withSystemPrompt(string $prompt): static
    {
        $clone = clone $this;
        $clone->systemPrompt = $prompt;
        return $clone;
    }

    public function withMaxTurns(int $maxTurns): static
    {
        $clone = clone $this;
        $clone->maxTurns = $maxTurns;
        return $clone;
    }

    public function withAllowedTools(array $tools): static
    {
        $clone = clone $this;
        $clone->allowedTools = $tools;
        return $clone;
    }

    public function withTimeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->timeout = $seconds;
        return $clone;
    }

    public function withJson(bool $json = true): static
    {
        $clone = clone $this;
        $clone->json = $json;
        return $clone;
    }

    public function withSessionId(string $sessionId): static
    {
        $clone = clone $this;
        $clone->sessionId = $sessionId;
        $clone->continue = false;
        return $clone;
    }

    public function withContinue(bool $continue = true): static
    {
        $clone = clone $this;
        $clone->continue = $continue;
        if ($continue) {
            $clone->sessionId = null;
        }
        return $clone;
    }

    public function withWorkingDirectory(string $path): static
    {
        $clone = clone $this;
        $clone->workingDirectory = $path;
        return $clone;
    }

    public function query(string $prompt, ?callable $onResponse = null): Response
    {
        $binary = $this->resolveBinaryPath();
        $args = $this->buildArgs($binary);

        $process = new Process($args);
        $process->setInput($prompt);
        $process->setTimeout((float) $this->timeout);

        if ($this->workingDirectory !== null) {
            $process->setWorkingDirectory($this->workingDirectory);
        }

        $env = $this->buildEnv();
        /** @psalm-suppress InvalidArgument Symfony Process accepts false to unset env vars */
        $process->setEnv($env);

        $startTime = microtime(true);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            throw new TimeoutException($this->timeout, $e);
        }

        $elapsed = round(microtime(true) - $startTime, 3);

        if (!$process->isSuccessful()) {
            throw new ClaudeCodeException(
                sprintf('Claude Code CLI failed (exit code %d): %s', (int) $process->getExitCode(), $process->getErrorOutput()),
                (int) $process->getExitCode(),
                $process->getErrorOutput(),
            );
        }

        $raw = trim($process->getOutput());

        if ($raw === '') {
            throw new ClaudeCodeException(
                'Claude Code CLI returned an empty response.',
                $process->getExitCode(),
                $process->getErrorOutput(),
            );
        }

        $response = $this->parseResponse($raw, $elapsed);

        if ($onResponse !== null) {
            $onResponse($response);
        }

        return $response;
    }

    /**
     * Resolve the claude binary path.
     *
     * @throws BinaryNotFoundException If the binary cannot be found.
     */
    private function resolveBinaryPath(): string
    {
        if ($this->binaryPath !== '') {
            if (!is_file($this->binaryPath) || !is_executable($this->binaryPath)) {
                throw new BinaryNotFoundException($this->binaryPath);
            }
            return $this->binaryPath;
        }

        /** @psalm-suppress ForbiddenCode Required to locate the claude binary */
        $path = trim((string) shell_exec('which claude 2>/dev/null'));

        if ($path === '') {
            throw new BinaryNotFoundException();
        }

        return $path;
    }

    /**
     * Build the CLI arguments array.
     *
     * @return array<string>
     */
    private function buildArgs(string $binary): array
    {
        $args = [
            $binary,
            '--print',
            '--output-format', $this->json ? 'json' : 'text',
            '--model', $this->model->value,
        ];

        if ($this->systemPrompt !== '') {
            $args[] = '--system-prompt';
            $args[] = $this->systemPrompt;
        }

        if ($this->maxTurns !== null) {
            $args[] = '--max-turns';
            $args[] = (string) $this->maxTurns;
        }

        if ($this->allowedTools !== []) {
            $args[] = '--allowedTools';
            foreach ($this->allowedTools as $tool) {
                $args[] = $tool;
            }
        }

        if ($this->sessionId !== null) {
            $args[] = '--resume';
            $args[] = $this->sessionId;
        } elseif ($this->continue) {
            $args[] = '--continue';
        }

        return $args;
    }

    /**
     * Build the environment variable overrides with recursion-prevention vars removed.
     *
     * Symfony Process merges these with the inherited parent environment.
     * Setting a value to `false` explicitly unsets it in the child process.
     *
     * @return array<string, string|false>
     */
    private function buildEnv(): array
    {
        /** @var array<string, string|false> $env */
        $env = [];

        foreach ($this->envUnset as $var) {
            $env[$var] = false;
        }

        return $env;
    }

    /**
     * Parse CLI output into a Response object.
     *
     * @throws ParseException If the JSON response wrapper cannot be parsed.
     */
    private function parseResponse(string $raw, float $elapsed): Response
    {
        $sessionId = null;
        $result = $raw;

        if ($this->json) {
            $wrapper = json_decode($raw, true);

            if (is_array($wrapper) && array_key_exists('result', $wrapper)) {
                $sessionId = isset($wrapper['session_id']) && is_string($wrapper['session_id'])
                    ? $wrapper['session_id']
                    : null;

                $resultField = $wrapper['result'];
                $result = is_string($resultField)
                    ? $resultField
                    : json_encode($resultField, JSON_THROW_ON_ERROR);
            }

            if (trim($result) === '') {
                throw new ParseException('Claude Code CLI returned an empty result in JSON wrapper.');
            }
        }

        $result = $this->stripCodeFences($result);

        return new Response($result, $raw, $sessionId, $elapsed, $this->json);
    }

    /**
     * Strip markdown code fences from the response text.
     */
    private function stripCodeFences(string $text): string
    {
        if (str_starts_with($text, '```')) {
            $text = (string) preg_replace('/^```(?:\w+)?\s*\n?/', '', $text);
            $text = (string) preg_replace('/\n?```\s*$/', '', $text);
        }

        return trim($text);
    }
}
