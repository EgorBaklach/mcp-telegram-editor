<?php namespace App\Tools;

final class PingTool
{
    public function ping(string $message = 'hello'): string
    {
        return "pong: {$message}";
    }
}