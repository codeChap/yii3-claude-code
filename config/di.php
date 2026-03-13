<?php

declare(strict_types=1);

use Codechap\Yii3ClaudeCode\ClaudeCode;
use Codechap\Yii3ClaudeCode\ClaudeCodeInterface;

/** @var array $params */

return [
    ClaudeCodeInterface::class => [
        'class' => ClaudeCode::class,
        '__construct()' => [
            'binaryPath' => $params['codechap/yii3-claude-code']['binaryPath'],
            'modelName' => $params['codechap/yii3-claude-code']['model'],
            'systemPrompt' => $params['codechap/yii3-claude-code']['systemPrompt'],
            'maxTurns' => $params['codechap/yii3-claude-code']['maxTurns'],
            'allowedTools' => $params['codechap/yii3-claude-code']['allowedTools'],
            'timeout' => $params['codechap/yii3-claude-code']['timeout'],
            'envUnset' => $params['codechap/yii3-claude-code']['envUnset'],
            'apiKey' => $params['codechap/yii3-claude-code']['apiKey'],
            'envSet' => $params['codechap/yii3-claude-code']['envSet'],
        ],
    ],
];
