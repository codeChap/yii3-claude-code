<?php

declare(strict_types=1);

namespace Codechap\Yii3ClaudeCode\Command;

use Codechap\Yii3ClaudeCode\ClaudeCodeInterface;
use Codechap\Yii3ClaudeCode\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use function sprintf;

/**
 * Console command for sending prompts to the Claude Code CLI.
 *
 * Supports model selection, JSON output, multi-turn conversations via
 * `--resume` and `--continue`, and custom system prompts.
 */
#[AsCommand(
    name: 'claude:query',
    description: 'Send a prompt to Claude Code CLI and display the response',
)]
final class ClaudeCodeCommand extends Command
{
    public function __construct(
        private readonly ClaudeCodeInterface $claudeCode,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('prompt', InputArgument::REQUIRED, 'The prompt to send to Claude Code')
            ->addOption('model', 'm', InputOption::VALUE_REQUIRED, 'Model: sonnet, opus, haiku')
            ->addOption('json', 'j', InputOption::VALUE_NONE, 'Request JSON output')
            ->addOption('resume', 'r', InputOption::VALUE_REQUIRED, 'Resume a conversation by session ID')
            ->addOption('continue', 'c', InputOption::VALUE_NONE, 'Continue the last conversation')
            ->addOption('system-prompt', null, InputOption::VALUE_REQUIRED, 'Set a system prompt')
            ->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'Anthropic API key (uses API auth instead of subscription)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $prompt */
        $prompt = $input->getArgument('prompt');
        $service = $this->claudeCode;

        /** @var string|null $modelValue */
        $modelValue = $input->getOption('model');
        if ($modelValue !== null) {
            $model = Model::tryFrom($modelValue);
            if ($model === null) {
                $io->error(sprintf('Invalid model "%s". Valid options: sonnet, opus, haiku.', $modelValue));
                return Command::FAILURE;
            }
            $service = $service->withModel($model);
        }

        if ($input->getOption('json')) {
            $service = $service->withJson();
        }

        /** @var string|null $sessionId */
        $sessionId = $input->getOption('resume');
        if ($sessionId !== null) {
            $service = $service->withSessionId($sessionId);
        }

        if ($input->getOption('continue')) {
            $service = $service->withContinue();
        }

        /** @var string|null $systemPrompt */
        $systemPrompt = $input->getOption('system-prompt');
        if ($systemPrompt !== null) {
            $service = $service->withSystemPrompt($systemPrompt);
        }

        /** @var string|null $apiKey */
        $apiKey = $input->getOption('api-key');
        if ($apiKey !== null) {
            $service = $service->withApiKey($apiKey);
        }

        try {
            $response = $service->query($prompt);
        } catch (Throwable $e) {
            $io->error('Claude Code query failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $output->writeln($response->getResult());

        if ($output->isVerbose()) {
            $io->newLine();
            if ($response->getSessionId() !== null) {
                $io->comment('Session ID: ' . $response->getSessionId());
            }
            $io->comment(sprintf('Elapsed: %.3fs', $response->getElapsedSeconds()));
        }

        return Command::SUCCESS;
    }
}
