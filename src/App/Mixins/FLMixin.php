<?php namespace App\Mixins;

use App\Dispatchers\Source\FLDispatcher;
use Closure;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

class FLMixin extends AbstractMixin
{
    public function csrf(): Closure
    {
        return function(ResponseInterface $response): ?string
        {
            return preg_match('/<meta\s+name=\x22csrf-token\x22\s+content=\x22([^\x22]+)\x22/i', $response->getBody(), $m) ? $m[1] : null;
        };
    }

    public function catalog(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var FLDispatcher $this */ $blocks = preg_split('/<div[^>]+id=\x22project-item(\d+)\x22[^>]*>/i', $response->getBody(), -1, PREG_SPLIT_DELIM_CAPTURE);

            for($i = 1, $count = count($blocks); $i < $count; $i += 2)
            {
                $project_id = (int) $blocks[$i]; $chunk = $blocks[$i + 1];

                if(!preg_match('/id=\x22prj_name_'.$project_id.'\x22[^>]*href=\x22([^\x22]+)\x22[^>]*>(.*?)<\/a>/is', $chunk, $nm)) continue;

                $title = $this->clean($nm[2]);
                $budget = preg_match('/b-post__price[^>]*>(.*?)<\/div>/is', $chunk, $pm) ? $this->clean($pm[1]) : null;
                $description = preg_match('/b-post__txt[^>]*>(.*?)<\/div>/is', $chunk, $tm) ? $this->clean($tm[1]) : '';

                $this->upsert[$project_id] = [
                    'project_id' => $project_id,
                    'hash' => $this->hash($title, $budget, $description),
                    'title' => $title,
                    'link' => str_starts_with($nm[1], 'http') ? $nm[1] : 'https://www.fl.ru'.$nm[1],
                    'budget' => $budget,
                    'description' => $description,
                    'published' => false,
                ];
            }

            return $this->upsert ? true : null;
        };
    }

    public function rss(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var FLDispatcher $this */

            $pattern = '/('.implode('|', array_map(fn(string $k): string => preg_quote($k, '/'), $this->client->config['filter']['keywords'])).')/iu';

            $xml = simplexml_load_string($response->getBody(), SimpleXMLElement::class, LIBXML_NOCDATA);

            foreach($xml->channel->item as $item)
            {
                $link = trim($item->link); if(!preg_match('/\/projects\/(\d+)\//', $link, $m)) continue; $project_id = (int) $m[1];

                $title = $this->clean($item->title); $budget = null;

                if(preg_match('/^(.*?)\s*\(\s*(?:Бюджет:\s*)?([^()]+)\)\s*$/iu', $title, $bm))
                {
                    $title = trim($bm[1]); $budget = trim($bm[2]);
                }

                $description = $this->clean($item->description);

                if(!preg_match($pattern, $title.' '.$description)) continue;

                $this->upsert[$project_id] = [
                    'project_id' => $project_id,
                    'hash' => $this->hash($title, $budget, $description),
                    'title' => $title,
                    'link' => $link,
                    'budget' => $budget,
                    'description' => $description,
                    'published' => false,
                ];
            }

            return $this->upsert ? true : null;
        };
    }

    public function description(): Closure
    {
        return function(ResponseInterface $response): ?string
        {
            /** @var FLDispatcher $this */ if(!preg_match('/<div[^>]+id=\x22projectp\d+\x22[^>]*>(.*?)<\/div>/is', $response->getBody(), $m)) return null;

            return $this->clean(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $m[1]), true);
        };
    }

    public function preview(): Closure
    {
        return function(array $order): ?string
        {
            /** @var FLDispatcher $this */

            if($order['budget'] && preg_match('/^(\d[\d\s]*)\s*(?:₽|руб)/iu', $order['budget'], $bm))
            {
                $amount = (int) preg_replace('/\D+/', '', $bm[1]); if($amount < $this->client->config['criteria']['min_budget']) return null;
            }

            return !$this->stopWords($order['title'].' '.$order['description']) ? $order['link'] : null;
        };
    }
}
