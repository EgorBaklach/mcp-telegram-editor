<?php namespace Magistrale\Mixins;

use Ahc\Cron\Expression;
use Closure;
use Magistrale\Contracts\MixinInterface;
use Magistrale\Dispatchers\Queue\AbstractDispatcher;

class QueueMixin implements MixinInterface
{
    public function cron(): Closure
    {
        return function(string $expression, mixed $timestamp = null): ?AbstractDispatcher
        {
            return Expression::isDue($expression, $timestamp) ? $this : null;
        };
    }

    public function pending(): Closure
    {
        return function(): array
        {
            /** @var AbstractDispatcher $this */ return $this->redis->command(['LRANGE', $this->config['keys']['pending'], '0', '-1']) ?: [];
        };
    }

    public function push(): Closure
    {
        return function(string $job): bool
        {
            /** @var AbstractDispatcher $this */

            $pending = array_flip($this->pending()); if(isset($pending[$job])) return false;

            return (int) $this->redis->command(['RPUSH', $this->config['keys']['pending'], $job]) > 0;
        };
    }

    public function state(): Closure
    {
        return function(): bool
        {
            /** @var AbstractDispatcher $this */ return $this->redis->command(['GET', $this->config['keys']['paused']]) !== '1';
        };
    }
}
