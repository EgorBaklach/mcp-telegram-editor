<?php namespace Magistrale\Providers;

use Framework\Providers\AbstractProvider;
use Magistrale\Contracts\RedisInterface;
use Magistrale\Mixins\RedisMixin;
use Magistrale\Traits\MacroableTrait;
use RuntimeException;
use Throwable;

final class RedisProvider extends AbstractProvider
{
    protected array $provides = [
        RedisInterface::class,
    ];

    public function register(): void
    {
        $this->container()->add(RedisInterface::class)->addArguments(['redis', RedisMixin::class])->setConcrete(fn(...$values) => new class(...$values) implements RedisInterface
        {
            use MacroableTrait;

            public function __construct(private readonly array $configs, object $mixin)
            {
                self::mixin($mixin);
            }

            public function locked(string $key, ?int $ttl = null): bool
            {
                return $this->command(['SET', $key, '1', 'NX', 'EX', (string) ($ttl ?? $this->configs['lock_ttl'])]) !== 'OK';
            }

            public function unlock(string $key): bool
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
                if(!$socket = @fsockopen($this->configs['host'], (int) $this->configs['port'], $errno, $errstr, (float) $this->configs['timeout']))
                {
                    throw new RuntimeException('Redis connection failed: '.$errstr.' ('.$errno.')');
                }

                stream_set_timeout($socket, (int) $this->configs['timeout']);

                try
                {
                    fwrite($socket, $this->encode($args)); return $this->read($socket);
                }
                finally
                {
                    fclose($socket);
                }
            }

            private function read($socket): mixed
            {
                $line = fgets($socket); if($line === false) throw new RuntimeException('Redis empty response'); $payload = substr($line, 1, -2); $length = (int) $payload;

                return match($line[0])
                {
                    '+' => $payload,
                    ':' => $length,
                    '$' => $length === -1 ? null : substr(stream_get_contents($socket, $length + 2), 0, -2),
                    '-' => throw new RuntimeException('Redis error: '.$payload),
                    default => $payload,
                };
            }
        });
    }
}
