<?php namespace App\Tools\Mcp;

final class PingTool
{
    public function ping(string $message = 'hello'): string
    {
        return 'pong: '.$message;
    }
}
