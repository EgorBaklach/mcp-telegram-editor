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

            $parts[] = match($comp['currencyCode'] ?? 'RUR')
            {
                'RUR', 'RUB' => '₽', 'USD' => '$', 'EUR' => '€', default => $comp['currencyCode']
            };

            $parts[] = isset($comp['gross']) && $comp['gross'] ? 'до вычета' : 'на руки';

            return implode(' ', $parts);
        };
    }

    public function catalog(): Closure
    {
        return function(ResponseInterface $response): ?true
        {
            /** @var HHDispatcher $this */ if(!$lux = $this->lux($response)) return null; $result = $lux['vacancySearchResult'] ?? [];

            foreach(array_slice($result['vacancies'] ?? [], 0, (int) ($result['totalResults'] ?? 0)) as $item)
            {
                if(!$vacancy_id = (int) ($item['vacancyId'] ?? 0)) continue;

                $title = $this->clean($item['name'] ?? null);
                $company = $this->clean($this->callback->array('get', $item, 'company.name'));
                $salary = $this->salary($item['compensation'] ?? []);

                $formats = $this->callback->array('get', $item, 'workFormats.0.workFormatsElement') ?? (isset($item['@workSchedule']) ? [$item['@workSchedule']] : []);
                $snippet = $item['snippet'] ?? [];

                $this->merge([
                    'vacancy_id' => $vacancy_id,
                    'hash' => $this->hash($title, $company, $salary),
                    'title' => $title,
                    'company' => $company,
                    'address' => $this->clean($this->callback->array('get', $item, 'address.displayName') ?? $this->callback->array('get', $item, 'area.name')),
                    'schedule' => $formats ? '{'.implode(',', $formats).'}' : null,
                    'link' => $this->callback->array('get', $item, 'links.desktop', 'https://ekaterinburg.hh.ru/vacancy/'.$vacancy_id),
                    'salary' => $salary,
                    'description' => $this->clean(implode(' ', array_filter([$snippet['req'] ?? null, $snippet['resp'] ?? null, $snippet['cond'] ?? null, $snippet['skill'] ?? null]))) ?: '',
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
                $link = trim($item->link); if(!preg_match('/\/vacancy\/(\d+)/', $link, $m)) continue;

                $title = $this->clean($item->title); $raw = (string) $item->description;
                $company = preg_match('/Вакансия компании:\s*([^<]+)/iu', $raw, $cm) ? $this->clean($cm[1]) : null;
                $salary = preg_match('/дохода:\s*([^<]+)/iu', $raw, $sm) && !str_contains($sm[1], 'не указан') ? $this->clean($sm[1]) : null;

                $this->merge([
                    'vacancy_id' => (int) $m[1],
                    'hash' => $this->hash($title, $company, $salary),
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

    public function merge(): Closure
    {
        return function(array $vacancy): void
        {
            /** @var HHDispatcher $this */

            foreach($this->upsert as $id => $item)
            {
                if($item['hash'] !== $vacancy['hash']) continue;

                if($vacancy['address'] && !str_contains((string) $item['address'], $vacancy['address'])) 
                {
                    $this->upsert[$id]['address'] = $item['address'] ? $item['address'].', '.$vacancy['address'] : $vacancy['address'];
                }

                return;
            }

            $this->upsert[$vacancy['vacancy_id']] = $vacancy;
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

            if(!$html) return null; $skills = !empty($vacancy['keySkills']) ? PHP_EOL.'Навыки: '.implode(', ', $vacancy['keySkills']) : '';

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
