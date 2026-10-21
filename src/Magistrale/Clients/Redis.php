<?php namespace Magistrale\Clients;

use Illuminate\Support\Traits\Macroable;
use League\Container\DefinitionContainerInterface;
use Magistrale\Mixins\MacroableInterface;
use RuntimeException;
use Throwable;

class Redis implements MacroableInterface
{
    use Macroable;

    private array $configs;

    public function __construct(DefinitionContainerInterface $container)
    {
        $this->configs = $container->get('redis');
    }

    public function isLocked(string $key, ?int $ttl = null): bool
    {
        return $this->command(['SET', $key, '1', 'NX', 'EX', (string) ($ttl ?? $this->configs['lock_ttl'] ?? 660)]) !== 'OK';
    }

    public function releaseLock(string $key): bool
    {
        try
        {
            return (int) $this->command(['DEL', $key]) > 0;
        }
        catch(Throwable)
        {
            return false;
        }
    }

    public function command(array $args): mixed
    {
        if(!$socket = @fsockopen($this->configs['host'], (int) $this->configs['port'], $errno, $errstr, (float) ($this->configs['timeout'] ?? 2.0)))
        {
            throw new RuntimeException("Redis connection failed: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, (int) ($this->configs['timeout'] ?? 2));

        try
        {
            fwrite($socket, $this->encodeResp($args)); return $this->readResponse($socket);
        }
        finally
        {
            fclose($socket);
        }
    }

    private function readResponse($socket): mixed
    {
        $line = fgets($socket); if($line === false) throw new RuntimeException('Redis empty response'); $payload = substr($line, 1, -2);

        return match($line[0])
        {
            '+' => $payload,
            ':' => (int) $payload,
            '$' => (int) $payload === -1 ? null : substr((string) stream_get_contents($socket, (int) $payload + 2), 0, -2),
            '-' => throw new RuntimeException("Redis error: {$payload}"),
            default => $payload,
        };
    }
}
