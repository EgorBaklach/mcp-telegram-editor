<?php namespace App\Mixins;

use App\Dispatchers\Source\FlDispatcher;
use Closure;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

class FlMixin
{
    public function extractCsrf(): Closure
    {
        return function(ResponseInterface $response): ?string
        {
            return preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/i', (string) $response->getBody(), $m) ? $m[1] : null;
        };
    }

    public function parseCatalog(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var FlDispatcher $this */ $blocks = preg_split('/<div[^>]+id="project-item(\d+)"[^>]*>/i', (string) $response->getBody(), -1, PREG_SPLIT_DELIM_CAPTURE);

            for($i = 1, $count = count($blocks); $i < $count; $i += 2)
            {
                $project_id = (int) $blocks[$i]; $chunk = $blocks[$i + 1];

                if(!preg_match('/id="prj_name_'.$project_id.'"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $chunk, $nm)) continue;

                $title = $this->cleanText($nm[2]);
                $budget = preg_match('/b-post__price[^>]*>(.*?)<\/div>/is', $chunk, $pm) ? $this->cleanText($pm[1]) : null;
                $preview = preg_match('/b-post__txt[^>]*>(.*?)<\/div>/is', $chunk, $tm) ? $this->cleanText($tm[1]) : '';
                $link = str_starts_with($nm[1], 'http') ? $nm[1] : 'https://www.fl.ru'.$nm[1];

                $this->orders[] = compact('project_id', 'title', 'link', 'budget', 'preview') + ['hash' => $this->makeHash($title, $budget, $preview)];
            }

            return $this->orders ? true : null;
        };
    }

    public function parseRss(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var FlDispatcher $this */

            $pattern = '/('.implode('|', array_map(fn(string $k): string => preg_quote($k, '/'), $this->client->config['filter']['keywords'])).')/iu';

            $xml = @simplexml_load_string((string) $response->getBody(), SimpleXMLElement::class, LIBXML_NOCDATA);

            if(!$xml || !isset($xml->channel->item)) return null;

            foreach($xml->channel->item as $item)
            {
                if(!preg_match('/\/projects\/(\d+)\//', $link = trim((string) $item->link), $m)) continue;

                $title = $this->cleanText($item?->title); $budget = null;

                if(preg_match('/^(.*?)\s*\((?:Бюджет:\s*)?([^()]+)\)\s*$/iu', $title, $bm))
                {
                    $title = trim($bm[1]); $budget = trim($bm[2]);
                }

                $preview = $this->cleanText($item?->description);

                if($pattern && !preg_match($pattern, $title.' '.$preview)) continue;

                $this->orders[] = compact('title', 'link', 'budget', 'preview') + ['project_id' => (int) $m[1], 'hash' => $this->makeHash($title, $budget, $preview)];
            }

            return $this->orders ? true : null;
        };
    }

    public function extractDescription(): Closure
    {
        return function(ResponseInterface $response): ?string
        {
            /** @var FlDispatcher $this */ if(!preg_match('/<div[^>]+id="projectp\d+"[^>]*>(.*?)<\/div>/is', (string) $response->getBody(), $m)) return null;

            return $this->cleanText(str_replace(['<br>', '<br/>', '<br />'], "\n", $m[1]), true);
        };
    }

    public function checkPreview(): Closure
    {
        return function(array $order): ?string
        {
            /** @var FlDispatcher $this */

            if($order['budget'] && preg_match('/^(\d[\d\s]*)\s*(?:₽|руб)/iu', $order['budget'], $bm))
            {
                $amount = (int) preg_replace('/\D+/', '', $bm[1]); if($amount < $this->client->config['criteria']['min_budget']) return null;
            }

            return !$this->hasStopWords($order['title'].' '.$order['preview']) ? $order['link'] : null;
        };
    }

    public function detail(): Closure
    {
        return function(string $description): ?string
        {
            /** @var FlDispatcher $this */ return mb_strlen($description) >= $this->client->config['criteria']['min_length'] && !$this->hasStopWords($description) ? $description : null;
        };
    }

    public function hasStopWords(): Closure
    {
        return function(string $text): bool
        {
            /** @var FlDispatcher $this */ $haystack = mb_strtolower($text);

            foreach($this->client->config['criteria']['stop_words'] as $word) if(str_contains($haystack, $word)) return true;

            return false;
        };
    }

    public function makeHash(): Closure
    {
        return function(string $title, ?string $budget, string $preview): string
        {
            return md5(mb_strtolower(trim($title).'|'.trim((string) $budget).'|'.trim($preview)));
        };
    }

    public function cleanText(): Closure
    {
        return function(mixed $html = null, bool $preserveLines = false): ?string
        {
            if(!$html) return null; $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return trim(preg_replace($preserveLines ? '/[ \t]+/u' : '/\s+/u', ' ', $decoded));
        };
    }
}
