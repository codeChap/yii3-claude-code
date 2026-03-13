<?php

declare(strict_types=1);

return [
    'codechap/yii3-claude-code' => [
        'binaryPath' => '',
        'model' => 'sonnet',
        'systemPrompt' => '',
        'maxTurns' => null,
        'allowedTools' => [],
        'timeout' => 300,
        'envUnset' => ['CLAUDECODE', 'ANTHROPIC_API_KEY'],
        'apiKey' => null,
        'envSet' => [],
    ],
];
