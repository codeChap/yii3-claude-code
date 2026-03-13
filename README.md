# Yii3 Claude Code

A Yii3 wrapper for the [Claude Code CLI](https://docs.anthropic.com/en/docs/claude-code) with DI integration, immutable fluent API, and multi-turn conversation support.

Works on **Linux**, **macOS**, and **Windows**. Supports both **subscription** and **API key** authentication.

## Requirements

- PHP 8.2 - 8.5
- [Claude Code CLI](https://docs.anthropic.com/en/docs/claude-code) installed and in your PATH (or configured via `binaryPath`)

## Installation

```bash
composer require codechap/yii3-claude-code
```

Configuration is auto-loaded via Yii3's [config-plugin](https://github.com/yiisoft/config).

## Quick start

```php
use Codechap\Yii3ClaudeCode\ClaudeCodeInterface;

final class MyController
{
    public function __construct(
        private readonly ClaudeCodeInterface $claude,
    ) {}

    public function actionAsk(): string
    {
        $response = $this->claude->query('What is the capital of France?');

        return $response->getResult();
    }
}
```

## Authentication

### Subscription (default)

If you're logged in via `claude auth login`, no extra config is needed. The package unsets `ANTHROPIC_API_KEY` in the subprocess by default to prevent accidental API charges.

### API key

Set the API key in your params to use pay-as-you-go billing instead of a subscription:

```php
// config/params.php
return [
    'codechap/yii3-claude-code' => [
        'apiKey' => 'sk-ant-...',
    ],
];
```

Or at runtime:

```php
$response = $claude
    ->withApiKey('sk-ant-...')
    ->query('Hello');
```

When an API key is provided, it takes priority — the `ANTHROPIC_API_KEY` environment variable is set in the subprocess instead of being unset.

> **Note:** With an API key, you are billed per token (pay-as-you-go). With a subscription, usage is included in your monthly plan. Verify your auth method with `claude auth status`.

## Configuration

Override defaults in your application's `config/params.php`:

```php
return [
    'codechap/yii3-claude-code' => [
        // Path to the claude binary. Empty string = auto-detect via PATH.
        'binaryPath' => '',

        // Default model: 'sonnet', 'opus', or 'haiku'.
        'model' => 'sonnet',

        // Default system prompt sent with every query.
        'systemPrompt' => '',

        // Maximum number of agentic turns (null = unlimited).
        'maxTurns' => null,

        // Tools the CLI is allowed to use.
        'allowedTools' => [],

        // Process timeout in seconds.
        'timeout' => 300,

        // Environment variables to unset in the subprocess (recursion prevention).
        'envUnset' => ['CLAUDECODE', 'ANTHROPIC_API_KEY'],

        // Anthropic API key (null = use subscription auth).
        'apiKey' => null,

        // Custom environment variables passed to the subprocess.
        'envSet' => [],
    ],
];
```

## Usage

All `with*` methods are **immutable** — they return a new instance, leaving the original unchanged.

### Model selection

```php
use Codechap\Yii3ClaudeCode\Model;

$response = $claude
    ->withModel(Model::Opus)
    ->query('Explain quantum computing');
```

### JSON mode

```php
$response = $claude
    ->withJson()
    ->query('List 3 colors as a JSON array');

$array = $response->toArray(); // Decoded JSON
```

### Multi-turn conversations

```php
// Start a conversation
$r1 = $claude
    ->withJson()
    ->query('What is the capital of France?');

// Continue with session ID
$r2 = $claude
    ->withJson()
    ->withSessionId($r1->getSessionId())
    ->query('And Germany?');

// Or continue the most recent conversation
$r3 = $claude
    ->withContinue()
    ->query('What about Italy?');
```

### System prompts

```php
$response = $claude
    ->withSystemPrompt('Reply in haiku form only.')
    ->query('Describe the ocean');
```

### Allowed tools

```php
$response = $claude
    ->withAllowedTools(['Read', 'Grep', 'Glob'])
    ->query('Find all TODO comments in this project');
```

### Working directory

```php
$response = $claude
    ->withWorkingDirectory('/path/to/project')
    ->query('Describe this codebase');
```

### Custom environment variables

Useful when running under a web server where `$HOME` and `$PATH` differ from your shell:

```php
$response = $claude
    ->withEnv([
        'HOME' => '/home/myuser',
        'PATH' => '/usr/local/bin:/usr/bin:/bin',
    ])
    ->query('Hello');
```

### Additional CLI flags

Pass arbitrary flags to the `claude` binary that don't have a dedicated `with*` method. Three forms are supported:

```php
// Boolean flag (no value)
$response = $claude
    ->withFlags(['--dangerously-skip-permissions'])
    ->query('Delete all temp files');

// Single-value flag
$response = $claude
    ->withFlags(['--effort' => 'high'])
    ->query('Refactor this module');

// Multi-value flag
$response = $claude
    ->withFlags(['--add-dir' => ['/data', '/config']])
    ->query('Analyze these directories');

// Mix all three forms
$response = $claude
    ->withFlags([
        '--dangerously-skip-permissions',
        '--effort' => 'high',
        '--add-dir' => ['/data', '/config'],
    ])
    ->query('Go wild');
```

These are appended after the built-in flags. Run `claude --help` to see all available flags.

### Timeout

```php
$response = $claude
    ->withTimeout(600) // 10 minutes
    ->query('Refactor this entire module');
```

### Callback

```php
$claude->query('Hello', function (Response $response): void {
    logger()->info('Claude responded', [
        'session' => $response->getSessionId(),
        'elapsed' => $response->getElapsedSeconds(),
    ]);
});
```

## Console command

Requires [`yiisoft/yii-console`](https://github.com/yiisoft/yii-console).

```bash
# Basic query
./yii claude:query "What is PHP?"

# With options
./yii claude:query "Explain this code" --model=opus --json
./yii claude:query "Continue our discussion" --continue
./yii claude:query "More details" --resume=session-id-here
./yii claude:query "Hello" --system-prompt="Be concise"
./yii claude:query "Hello" --api-key=sk-ant-...
```

Use `-v` for verbose output (session ID and elapsed time).

## Response object

| Method | Returns | Description |
|--------|---------|-------------|
| `getResult()` | `string` | Extracted response text |
| `getRawOutput()` | `string` | Unprocessed CLI output |
| `getSessionId()` | `?string` | Session ID for multi-turn (JSON mode) |
| `getElapsedSeconds()` | `float` | Wall-clock time in seconds |
| `isJson()` | `bool` | Whether JSON mode was used |
| `toArray()` | `array` | Decode result as JSON (throws `ParseException` on failure) |
| `__toString()` | `string` | Same as `getResult()` |

## Default CLI flags

Every query invokes the `claude` binary with these flags:

| Flag | Value | Description |
|------|-------|-------------|
| `--print` | *(always set)* | Non-interactive mode — sends prompt via stdin and exits. Cannot be disabled. |
| `--output-format` | `text` or `json` | Controlled by `withJson()`. Defaults to `text`. |
| `--model` | `sonnet` | Configurable via params (`model`) or `withModel()`. Defaults to `sonnet`. |

The resulting command looks like:

```
claude --print --output-format text --model sonnet
```

Additional flags (`--system-prompt`, `--max-turns`, `--allowedTools`, `--resume`, `--continue`) are appended only when their corresponding `with*` method has been called. For any other CLI flag (e.g. `--dangerously-skip-permissions`, `--effort`, `--add-dir`), use `withFlags()`.

## Platform notes

### Binary detection

The package auto-detects the `claude` binary using `which` (Linux/macOS) or `where` (Windows). If auto-detection fails — common under web servers with a minimal `$PATH` — set `binaryPath` explicitly:

```php
'binaryPath' => '/home/myuser/.npm-global/bin/claude',
```

### Web server context

When PHP runs under Apache/nginx, the subprocess inherits the web server's environment. This means:

- `$HOME` points to the web server user (e.g. `www-data`), which won't have Claude authenticated
- `$PATH` may not include the directory where `claude` is installed

Solutions:
1. Set `binaryPath` to the full path of the `claude` binary
2. Use `envSet` to pass the correct `HOME` (for subscription auth) or use `apiKey` (for API auth)

```php
'binaryPath' => '/home/deploy/.npm-global/bin/claude',
'envSet' => ['HOME' => '/home/deploy'],
```

### Recursion prevention

By default, `CLAUDECODE` and `ANTHROPIC_API_KEY` are unset in the subprocess to prevent:
- Infinite recursion if your app is itself running inside Claude Code
- Accidental API charges when you intend to use a subscription

This is configurable via `envUnset`. When `apiKey` is set, it overrides the `ANTHROPIC_API_KEY` unset.

## Testing

```bash
composer test          # PHPUnit with --testdox
composer analyse       # Psalm static analysis
```

Integration tests that call the real Claude CLI are automatically skipped if the binary is not available.

## License

MIT
