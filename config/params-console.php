<?php

declare(strict_types=1);

use Codechap\Yii3ClaudeCode\Command\ClaudeCodeCommand;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'claude:query' => ClaudeCodeCommand::class,
        ],
    ],
];
