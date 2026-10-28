<?php namespace App\Mixins;

use App\Dispatchers\Source\HHDispatcher;
use Closure;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

class HHMixin extends AbstractMixin
{
    public function lux(): Closure
    {
        return function(ResponseInterface $response): ?array
        {
            if(!preg_match('/<template[^>]*id=\x22HH-Lux-InitialState\x22[^>]*>([^<]+)<\/template>/i', $response->getBody(), $m)) return null;

            return json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
        };
    }

    public function salary(): Closure
    {
        return function(array $comp = []): ?string
        {
            $parts = [];

            if(isset($comp['from'])) $parts[] = 'от '.number_format((int) $comp['from'], 0, '', ' ');

            if(isset($comp['to'])) $parts[] = 'до '.number_format((int) $comp['to'], 0, '', ' ');

            if(!$parts) return null;

            $parts[] = match($comp['currencyCode'])
            {
                'RUR', 'RUB' => '₽', 'USD' => '$', 'EUR' => '€', default => $comp['currencyCode']
            };

            $parts[] = $comp['gross'] ? 'до вычета' : 'на руки';

            return implode(' ', $parts);
        };
    }

    public function catalog(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var HHDispatcher $this */ if(!$lux = $this->lux($response)) return null;

            foreach(array_slice($lux['vacancySearchResult']['vacancies'], 0, (int) $lux['vacancySearchResult']['totalResults']) as $item)
            {
                $hash = $this->hash(
                    $title = $this->clean($item['name']),
                    $company = $this->clean($item['company']['name']),
                    $salary = $this->salary($item['compensation'])
                );

                $formats = $this->callback->array(fn(callable $get) => $get($item, 'workFormats.0.workFormatsElement') ?? $get($item, '@workSchedule'));

                $this->extend([
                    'vacancy_id' => (int) $item['vacancyId'],
                    'hash' => $hash,
                    'title' => $title,
                    'company' => $company,
                    'address' => $this->callback->array(fn(callable $get) => $this->clean($get($item, 'address.displayName') ?? $item['area']['name'])),
                    'schedule' => $formats ? '{'.implode(',', (array) $formats).'}' : null,
                    'link' => $item['links']['desktop'],
                    'salary' => $salary,
                    'description' => $this->clean(implode(' ', array_filter($item['snippet']))) ?: '',
                    'published' => false,
                ]);
            }

            return $this->upsert ? true : null;
        };
    }

    public function rss(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var HHDispatcher $this */ $xml = simplexml_load_string($response->getBody(), SimpleXMLElement::class, LIBXML_NOCDATA);

            foreach($xml->channel->item as $item)
            {
                if(!preg_match('/\/vacancy\/(\d+)/', $link = trim($item->link), $m)) continue; $raw = (string) $item->description;

                $hash = $this->hash(
                    $title = $this->clean($item->title),
                    $company = preg_match('/Вакансия компании:\s*([^<]+)/iu', $raw, $cm) ? $this->clean($cm[1]) : null,
                    $salary = preg_match('/дохода:\s*([^<]+)/iu', $raw, $sm) && !str_contains($sm[1], 'не указан') ? $this->clean($sm[1]) : null
                );

                $this->extend([
                    'vacancy_id' => (int) $m[1],
                    'hash' => $hash,
                    'title' => $title,
                    'company' => $company,
                    'address' => preg_match('/Регион:\s*([^<]+)/iu', $raw, $am) ? $this->clean($am[1]) : null,
                    'schedule' => null,
                    'link' => $link,
                    'salary' => $salary,
                    'description' => $this->clean($raw) ?: '',
                    'published' => false,
                ]);
            }

            return $this->upsert ? true : null;
        };
    }

    public function extend(): Closure
    {
        return function(array $vacancy): void
        {
            /** @var HHDispatcher $this */ $item = $this->upsert[$vacancy['hash']] ??= $vacancy;

            if($vacancy['address'] && !str_contains((string) $item['address'], $vacancy['address']))
            {
                $this->upsert[$item['hash']]['address'] = implode(', ', array_filter([$item['address'], $vacancy['address']]));
            }
        };
    }

    public function description(): Closure
    {
        return function(ResponseInterface $response): ?string
        {
            /** @var HHDispatcher $this */ $vacancy = $this->callback->array('get', $this->lux($response), 'vacancyView.vacancyFull.vacancy', []);

            if(!$html = $vacancy['description'] ?? $vacancy['brandedDescription'] ?? null)
            {
                if(preg_match('/<div[^>]+data-qa=\x22vacancy-description\x22[^>]*>(.*?)<\/div>/is', $response->getBody(), $m))
                {
                    $html = $m[1];
                }
            }

            if(!$html) return null; $skills = isset($vacancy['keySkills'][0]) ? PHP_EOL.'Навыки: '.implode(', ', $vacancy['keySkills']) : '';

            return $this->clean(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], PHP_EOL, $html), true).$skills;
        };
    }

    public function preview(): Closure
    {
        return function(array $vacancy): ?string
        {
            /** @var HHDispatcher $this */ return !$this->stopWords($vacancy['title'].' '.$vacancy['description']) ? $vacancy['link'] : null;
        };
    }
}
