<?php namespace App\Mixins;

use App\Dispatchers\Source\AbstractDispatcher;
use Closure;
use Magistrale\Contracts\MixinInterface;

abstract class AbstractMixin implements MixinInterface
{
    public function detail(): Closure
    {
        return function(string $description): ?string
        {
            /** @var AbstractDispatcher $this */ return mb_strlen($description) >= $this->client->config['criteria']['min_length'] && !$this->stopWords($description) ? $description : null;
        };
    }

    public function stopWords(): Closure
    {
        return function(string $text): bool
        {
            /** @var AbstractDispatcher $this */ $haystack = mb_strtolower($text);

            foreach($this->client->config['criteria']['stop_words'] as $word) if(str_contains($haystack, $word)) return true;

            return false;
        };
    }

    public function hash(): Closure
    {
        return function(?string ...$parts): string
        {
            return md5(mb_strtolower(implode('|', array_map(fn(?string $p): string => trim((string) $p), $parts))));
        };
    }

    public function clean(): Closure
    {
        return function(mixed $html = null, bool $multiline = false): ?string
        {
            if(!$html) return null; $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return trim(preg_replace($multiline ? '/[ \t]+/u' : '/\s+/u', ' ', $decoded));
        };
    }
}
