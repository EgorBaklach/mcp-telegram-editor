<?php namespace Tests;

use App\Dispatchers\Source\HHDispatcher;
use App\Models\HHVacancy;
use App\Tools\HH\{CheckNewVacanciesTool, MarkVacancyTool};
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;
use Magistrale\Clients\HHClient;
use PHPUnit\Framework\Attributes\TestDox;
use Psr\Http\Message\ResponseInterface;

class HHTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->capsule = $this->container->get(Capsule::class);
        $this->capsule->getConnection()->beginTransaction();
        HHVacancy::query()->delete();
    }

    protected function tearDown(): void
    {
        if($this->capsule->getConnection()->transactionLevel() > 0)
        {
            $this->capsule->getConnection()->rollBack();
        }

        $this->capsule->getConnection()->disconnect();
        parent::tearDown();
    }

    #[TestDox('HHDispatcher и HHMixin: парсят HH-Lux-InitialState, отсекают похожие вакансии по totalResults, дедуплицируют по hash, сохраняют address и schedule и фильтруют короткие описания')]
    public function testHHDispatcherCatalogDeduplicationAndFiltering(): void
    {
        $existingTitle = 'Senior PHP Developer';
        $existingCompany = 'ООО ФинТех';
        $existingSalary = 'от 250 000 ₽ на руки';
        $existingHash = md5(mb_strtolower($existingTitle.'|'.$existingCompany.'|'.$existingSalary));

        HHVacancy::create([
            'vacancy_id' => 999,
            'hash' => md5('expired-hh'),
            'title' => 'Устаревшая вакансия старше 30 дней',
            'company' => 'Старая Компания',
            'address' => 'Москва, Тверская улица, 1',
            'schedule' => '{REMOTE}',
            'link' => 'https://ekaterinburg.hh.ru/vacancy/999',
            'salary' => 'от 100 000 ₽',
            'description' => 'Старое описание',
            'published' => false,
        ]);
        HHVacancy::where('vacancy_id', 999)->update(['created_at' => date('Y-m-d H:i:s', strtotime('-31 days'))]);

        HHVacancy::create([
            'vacancy_id' => 1001,
            'hash' => $existingHash,
            'title' => $existingTitle,
            'company' => $existingCompany,
            'address' => 'Москва',
            'schedule' => '{REMOTE}',
            'link' => 'https://ekaterinburg.hh.ru/vacancy/1001',
            'salary' => $existingSalary,
            'description' => 'Уже сохраненная вакансия',
            'published' => false,
        ]);

        $catalogState = [
            'vacancySearchResult' => [
                'totalResults' => 5,
                'vacancies' => [
                    [
                        'vacancyId' => 1002,
                        'name' => $existingTitle,
                        'company' => ['name' => $existingCompany],
                        'area' => ['name' => 'Санкт-Петербург'],
                        'workFormats' => [['workFormatsElement' => ['REMOTE']]],
                        'compensation' => ['from' => 250000, 'currencyCode' => 'RUR', 'gross' => false],
                        'links' => ['desktop' => 'https://ekaterinburg.hh.ru/vacancy/1002'],
                        'snippet' => ['req' => 'PHP 8.3, Laravel', 'resp' => 'Разработка ядра'],
                    ],
                    [
                        'vacancyId' => 1004,
                        'name' => 'PHP-разработчик (Bitrix)',
                        'company' => ['name' => 'ВебСтудия'],
                        'area' => ['name' => 'Новосибирск'],
                        'workFormats' => [['workFormatsElement' => ['ON_SITE']]],
                        'compensation' => ['noCompensation' => []],
                        'links' => ['desktop' => 'https://ekaterinburg.hh.ru/vacancy/1004'],
                        'snippet' => ['req' => 'Опыт с Битрикс'],
                    ],
                    [
                        'vacancyId' => 1006,
                        'name' => 'Lead Laravel Developer',
                        'company' => ['name' => 'ТехКорп'],
                        'area' => ['name' => 'Тбилиси'],
                        'workFormats' => [['workFormatsElement' => ['REMOTE']]],
                        'compensation' => ['from' => 220000, 'to' => 300000, 'currencyCode' => 'RUR', 'gross' => false],
                        'links' => ['desktop' => 'https://ekaterinburg.hh.ru/vacancy/1006'],
                        'snippet' => ['req' => 'Laravel, PostgreSQL, Redis', 'resp' => 'Проектирование микросервисов'],
                    ],
                    [
                        'vacancyId' => 1007,
                        'name' => 'Lead Laravel Developer',
                        'company' => ['name' => 'ТехКорп'],
                        'area' => ['name' => 'Екатеринбург'],
                        'workFormats' => [['workFormatsElement' => ['REMOTE']]],
                        'compensation' => ['from' => 220000, 'to' => 300000, 'currencyCode' => 'RUR', 'gross' => false],
                        'links' => ['desktop' => 'https://ekaterinburg.hh.ru/vacancy/1007'],
                        'snippet' => ['req' => 'Laravel, PostgreSQL, Redis', 'resp' => 'Проектирование микросервисов'],
                    ],
                    [
                        'vacancyId' => 1005,
                        'name' => 'Middle+ PHP / Laravel разработчик',
                        'company' => ['name' => 'УралСофт'],
                        'area' => ['name' => 'Екатеринбург'],
                        'address' => ['displayName' => 'Екатеринбург, улица Малышева, 51'],
                        'workFormats' => [['workFormatsElement' => ['ON_SITE', 'HYBRID']]],
                        'compensation' => ['from' => 180000, 'to' => 240000, 'currencyCode' => 'RUR', 'gross' => true],
                        'links' => ['desktop' => 'https://ekaterinburg.hh.ru/vacancy/1005'],
                        'snippet' => ['req' => 'PHP 8.2, Laravel, Docker', 'resp' => 'Разработка высоконагруженных сервисов'],
                    ],
                    [
                        'vacancyId' => 1099,
                        'name' => 'Менеджер по продажам (похожая вакансия сверх totalResults)',
                        'company' => ['name' => 'СпамТорг'],
                        'area' => ['name' => 'Москва'],
                    ],
                ],
            ],
        ];

        $catalogHtml = '<html><body><template id="HH-Lux-InitialState">'.htmlspecialchars(json_encode($catalogState, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8').'</template></body></html>';

        $shortDetailHtml = '<div data-qa="vacancy-description">Короткое описание менее 150 символов.</div>';
        $fullText1005 = str_repeat('Разработка высоконагруженной B2B-платформы в Екатеринбурге на стеке PHP 8.3, Laravel 11, PostgreSQL и Redis с очередями и покрытием PHPUnit. ', 3);
        $detail1005State = ['vacancyView' => ['vacancyFull' => ['vacancy' => ['description' => '<p>'.$fullText1005.'</p>', 'keySkills' => ['PHP', 'Laravel', 'PostgreSQL']]]]];
        $detail1005Html = '<html><body><template id="HH-Lux-InitialState">'.htmlspecialchars(json_encode($detail1005State, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5, 'UTF-8').'</template></body></html>';
        $detail1006Html = '<div data-qa="vacancy-description">'.str_repeat('Удаленная разработка распределенной системы на Laravel, профилирование SQL-запросов в PostgreSQL и интеграция внешних REST API сервисов. ', 3).'</div>';

        $mock = $this->createMock(HHClient::class);
        $mock->config = $this->container->get('clients')[HHClient::class];
        $mock->expects($this->once())
            ->method('vacancies')
            ->willReturn(new Response(200, [], $catalogHtml));
        $mock->expects($this->exactly(3))
            ->method('vacancy')
            ->willReturnCallback(fn(string $uri) => match(true)
            {
                str_contains($uri, '1004') => new Response(200, [], $shortDetailHtml),
                str_contains($uri, '1005') => new Response(200, [], $detail1005Html),
                default => new Response(200, [], $detail1006Html),
            });

        $this->container->add(HHClient::class, $mock);

        $dispatcher = $this->container->get(HHDispatcher::class);
        $this->assertTrue($dispatcher->dispatch());

        $results = $dispatcher->getResults();
        $this->assertCount(2, $results, 'Должны вернуться 2 целевые вакансии с полным описанием');
        $this->assertSame(1005, $results[0]['vacancy_id']);
        $this->assertSame('Екатеринбург, улица Малышева, 51', $results[0]['address']);
        $this->assertSame('{ON_SITE,HYBRID}', $results[0]['schedule']);
        $this->assertSame('от 180 000 до 240 000 ₽ до вычета', $results[0]['salary']);
        $this->assertStringContainsString('Навыки: PHP, Laravel, PostgreSQL', $results[0]['description']);

        $this->assertSame(1006, $results[1]['vacancy_id']);
        $this->assertSame('Тбилиси, Екатеринбург', $results[1]['address']);
        $this->assertSame('от 220 000 до 300 000 ₽ на руки', $results[1]['salary']);

        $this->assertNull(HHVacancy::where('vacancy_id', 999)->first(), 'Вакансия старше 30 дней должна удаляться по TTL');
        $this->assertNull(HHVacancy::where('vacancy_id', 1002)->first(), 'Клон 1002 из другого города должен отсекаться по совпадению hash');
        $this->assertNull(HHVacancy::where('vacancy_id', 1099)->first(), 'Похожая вакансия за пределами totalResults не должна попадать в БД');
        $this->assertFalse(HHVacancy::where('vacancy_id', 1004)->first()->published, 'Вакансия с коротким описанием (<150 символов) должна остаться с published=false');
        $this->assertNull(HHVacancy::where('vacancy_id', 1005)->first()->published);
        $this->assertNull(HHVacancy::where('vacancy_id', 1006)->first()->published);
    }

    #[TestDox('CheckNewVacanciesTool, фоллбек на RSS и MarkVacancyTool: при сбое каталога переключается на RSS и обновляет статус')]
    public function testCheckNewVacanciesToolRssFallbackAndMarkVacancyTool(): void
    {
        $rssXml = '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0"><channel>
                <item>
                    <title><![CDATA[Senior PHP / Laravel Developer]]></title>
                    <link>https://ekaterinburg.hh.ru/vacancy/2001</link>
                    <description><![CDATA[<p>Вакансия компании: Контур</p> <p>Создана: 27.10.2026</p> <p>Регион: Екатеринбург</p> <p>Предполагаемый уровень месячного дохода: от 200 000 до 280 000 ₽</p>]]></description>
                </item>
                <item>
                    <title><![CDATA[PHP-разработчик (пустое описание)]]></title>
                    <link>https://ekaterinburg.hh.ru/vacancy/2002</link>
                    <description><![CDATA[<p>Вакансия компании: АртСтудия</p> <p>Создана: 27.10.2026</p> <p>Регион: Екатеринбург</p> <p>Предполагаемый уровень месячного дохода: не указан</p>]]></description>
                </item>
            </channel></rss>';

        $detail2001 = '<div data-qa="vacancy-description">'.str_repeat('Разработка микросервисов на PHP 8.3 и Laravel, интеграция с высоконагруженными хранилищами PostgreSQL и брокерами сообщений RabbitMQ. ', 3).'</div>';

        $client = new class($this->container->get('clients')[HHClient::class], $rssXml, $detail2001) extends HHClient
        {
            private int $rssCalls = 0;

            public function __construct(array $config, private string $rssXml, private string $detailHtml)
            {
                parent::__construct($config);
            }

            public function get($uri, array $options = []): ResponseInterface
            {
                if($uri === 'search/vacancy') return new Response(503);

                if($uri === 'search/vacancy/rss') return ++$this->rssCalls === 1 ? new Response(200, [], $this->rssXml) : new Response(502);

                return str_contains($uri, '2001') ? new Response(200, [], $this->detailHtml) : new Response(200, [], '<div data-qa="vacancy-description">Коротко</div>');
            }
        };

        $this->container->add(HHClient::class, $client);

        $tool = $this->container->get(CheckNewVacanciesTool::class);
        $ok = json_decode($tool->check(), true);
        $this->assertSame('ok', $ok['status']);
        $this->assertCount(1, $ok['new_vacancies']);
        $this->assertSame(2001, $ok['new_vacancies'][0]['vacancy_id']);
        $this->assertSame('Контур', $ok['new_vacancies'][0]['company']);
        $this->assertSame('Екатеринбург', $ok['new_vacancies'][0]['address']);
        $this->assertSame('от 200 000 до 280 000 ₽', $ok['new_vacancies'][0]['salary']);

        $err = json_decode($tool->check(), true);
        $this->assertSame('error', $err['status']);

        $markTool = $this->container->get(MarkVacancyTool::class);
        $this->assertSame('success', $markTool->mark(2001, true));
        $this->assertTrue(HHVacancy::where('vacancy_id', 2001)->first()->published);
        $this->assertSame('success', $markTool->mark(2001, false));
        $this->assertFalse(HHVacancy::where('vacancy_id', 2001)->first()->published);
        $this->assertSame('not found', $markTool->mark(999999, true));

        $this->expectException(InvalidArgumentException::class);
        $markTool->mark(0, true);
    }
}
