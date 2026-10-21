<?php

use App\Tools\{CheckNewModelsTool, DeleteByTextTool, DeleteTool, EditTool, MarkPublishedTool, PingTool, PublishTool, SearchPostsTool};

return [
    'server_name' => 'mcp-telegram-editor',
    'server_ver' => '1.0.0',
    'session_dir' => sys_get_temp_dir().'/mcp-sessions',
    'logging' => [
        'stream' => 'php://stderr',
    ],
    'tools' => [
        ['handler' => [PublishTool::class, 'publish'], 'name' => 'publish', 'description' => 'Publishes on the Telegram channel by post'],
        ['handler' => [PingTool::class, 'ping'], 'name' => 'ping', 'description' => 'Returns "pong: {message}". Useful for connectivity tests.'],
        ['handler' => [DeleteTool::class, 'delete'], 'name' => 'delete', 'description' => 'Deletes a message from the Telegram channel by message ID'],
        ['handler' => [DeleteByTextTool::class, 'deleteByText'], 'name' => 'delete_by_text', 'description' => 'Deletes a message from the Telegram channel by searching its text content in DB'],
        ['handler' => [EditTool::class, 'edit'], 'name' => 'edit', 'description' => 'Edits an existing Telegram channel post by its message ID'],
        ['handler' => [SearchPostsTool::class, 'search'], 'name' => 'search_posts', 'description' => 'Searches published Telegram channel posts in DB by keyword/substring and returns their message IDs and text'],
        ['handler' => [CheckNewModelsTool::class, 'checkNewModels'], 'name' => 'check_new_models', 'description' => 'Syncs free OpenRouter models with DB and returns JSON with newly discovered models data (does not publish, agent handles publishing)'],
        ['handler' => [MarkPublishedTool::class, 'markPublished'], 'name' => 'mark_published', 'description' => 'Marks an OpenRouter model as published in DB by its model_id after agent publishes the post'],
    ],
];
