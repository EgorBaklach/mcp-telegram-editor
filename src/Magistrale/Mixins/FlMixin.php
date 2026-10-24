<?php namespace Magistrale\Mixins;

use Closure;
use Magistrale\Dispatchers\Source\FlDispatcher;
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
        return function(ResponseInterface $response): array
        {
            /** @var FlDispatcher $this */ $blocks = preg_split('/<div[^>]+id="project-item(\d+)"[^>]*>/i', (string) $response->getBody(), -1, PREG_SPLIT_DELIM_CAPTURE);
            $orders = [];

            for($i = 1, $count = count($blocks); $i < $count; $i += 2)
            {
                $id = (int) $blocks[$i]; $chunk = $blocks[$i + 1];
                if(!preg_match('/id="prj_name_'.$id.'"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $chunk, $nm)) continue;

                $title = $this->cleanText($nm[2]);
                $budget = preg_match('/b-post__price[^>]*>(.*?)<\/div>/is', $chunk, $pm) ? ($this->cleanText($pm[1]) ?: null) : null;
                $preview = preg_match('/b-post__txt[^>]*>(.*?)<\/div>/is', $chunk, $tm) ? $this->cleanText($tm[1]) : '';
                $link = str_starts_with($nm[1], 'http') ? $nm[1] : 'https://www.fl.ru'.$nm[1];

                $orders[] = [
                    'project_id' => $id,
                    'hash' => $this->makeHash($title, $budget, $preview),
                    'title' => $title,
                    'link' => $link,
                    'budget' => $budget,
                    'preview' => $preview,
                ];
            }

            return $orders;
        };
    }

    public function parseRss(): Closure
    {
        return function(ResponseInterface $response, array $keywords = []): array
        {
            /** @var FlDispatcher $this */ $xml = @simplexml_load_string((string) $response->getBody(), SimpleXMLElement::class, LIBXML_NOCDATA); if(!$xml || !isset($xml->channel->item)) return [];
            $pattern = $keywords ? '/('.implode('|', array_map(fn(string $k): string => preg_quote($k, '/'), $keywords)).')/iu' : null;
            $orders = [];

            foreach($xml->channel->item as $item)
            {
                $link = trim((string) $item->link); if(!preg_match('/\/projects\/(\d+)\//', $link, $m)) continue;
                $title = $this->cleanText((string) $item->title); $budget = null;
                if(preg_match('/^(.*?)\s*\((?:Бюджет:\s*)?([^()]+)\)\s*$/iu', $title, $bm))
                {
                    $title = trim($bm[1]); $budget = trim($bm[2]);
                }

                $preview = $this->cleanText((string) $item->description);
                if($pattern && !preg_match($pattern, $title.' '.$preview)) continue;

                $orders[] = [
                    'project_id' => (int) $m[1],
                    'hash' => $this->makeHash($title, $budget, $preview),
                    'title' => $title,
                    'link' => $link,
                    'budget' => $budget,
                    'preview' => $preview,
                ];
            }

            return $orders;
        };
    }

    public function extractDescription(): Closure
    {
        return function(ResponseInterface $response, int $projectId): ?string
        {
            /** @var FlDispatcher $this */ if(!preg_match('/<div[^>]+id="projectp'.$projectId.'"[^>]*>(.*?)<\/div>/is', (string) $response->getBody(), $m)) return null;

            return $this->cleanText(str_replace(['<br>', '<br/>', '<br />'], "\n", $m[1]), true);
        };
    }

    public function isExisting(): Closure
    {
        return function(array $order, array $existing): bool
        {
            return isset($existing[$order['project_id']]) || in_array($order['hash'], $existing, true);
        };
    }

    public function isSmallTask(): Closure
    {
        return function(array $order, string $description, bool $checkLength = true): bool
        {
            if($order['budget'] && preg_match('/^(\d[\d\s]*)\s*(?:₽|руб)/iu', $order['budget'], $bm))
            {
                $amount = (int) preg_replace('/\D+/', '', $bm[1]); if($amount > 0 && $amount < 3000) return true;
            }

            $haystack = mb_strtolower($order['title'].' '.$description);
            foreach(['бпла', 'медкомисс', 'списание долгов', 'участок земли', 'судимос', 'куплю исходный код', 'привлечения клиентов', 'всплывающие окна', 'всплывающее окно', 'объединение дублирующих карточек', 'долго грузится', 'adguard'] as $stopWord)
            {
                if(str_contains($haystack, $stopWord)) return true;
            }

            return $checkLength && mb_strlen($description) < 150;
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
        return function(string $html, bool $preserveLines = false): string
        {
            $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return trim(preg_replace($preserveLines ? '/[ \t]+/u' : '/\s+/u', ' ', $decoded));
        };
    }
}
