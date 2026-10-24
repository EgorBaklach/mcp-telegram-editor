<?php namespace Tests;

use App\Models\FlOrder;
use App\Tools\Fl\CheckNewOrdersTool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Clients\Fl;
use Magistrale\Dispatchers\Source\FlDispatcher;
use PHPUnit\Framework\Attributes\TestDox;

class FlTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        $this->bootContainer();

        $this->capsule = $this->container->get(Capsule::class);
        $this->capsule->getConnection()->beginTransaction();
        FlOrder::query()->delete();
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

    #[TestDox('SyncDispatcher и FlMixin: парсят HTML-каталог, дедуплицируют по project_id и hash, отсеивают мелкие задачи в published=false и возвращают только published IS NULL')]
    public function testSyncDispatcherHtmlCatalogDeduplicationAndSmallTaskFiltering(): void
    {
        $existingTitle = 'Разработка CRM на Laravel';
        $existingBudget = '150 000 ₽';
        $existingPreview = 'Краткое превью существующего заказа';
        $existingHash = md5(mb_strtolower($existingTitle.'|'.$existingBudget.'|'.$existingPreview));

        FlOrder::create([
            'project_id' => 1001,
            'hash' => $existingHash,
            'title' => $existingTitle,
            'link' => 'https://www.fl.ru/projects/1001/crm.html',
            'budget' => $existingBudget,
            'description' => 'Полное описание существующего заказа',
            'published' => false,
        ]);

        $initialHtml = '<html><head><meta name="csrf-token" content="test-csrf-123"></head><body></body></html>';
        $catalogHtml = '
            <div id="project-item1001">
                <a id="prj_name_1001" href="/projects/1001/crm.html">Разработка CRM на Laravel</a>
                <div class="b-post__price">150 000 ₽</div>
                <div class="b-post__txt">Краткое превью существующего заказа</div>
            </div>
            <div id="project-item1002">
                <a id="prj_name_1002" href="/projects/1002/crm-clone.html">Разработка CRM на Laravel</a>
                <div class="b-post__price">150 000 ₽</div>
                <div class="b-post__txt">Краткое превью существующего заказа</div>
            </div>
            <div id="project-item1003">
                <a id="prj_name_1003" href="/projects/1003/tiny-budget.html">Поправить верстку Битрикс</a>
                <div class="b-post__price">500 ₽</div>
                <div class="b-post__txt">Мелкая правка кнопки в шаблоне</div>
            </div>
            <div id="project-item1004">
                <a id="prj_name_1004" href="/projects/1004/short-desc.html">Доработка сайта на PHP</a>
                <div class="b-post__price">по договоренности</div>
                <div class="b-post__txt">Нужно быстро поправить баг</div>
            </div>
            <div id="project-item1005">
                <a id="prj_name_1005" href="/projects/1005/highload-laravel.html">Разработка B2B портала на Laravel и React</a>
                <div class="b-post__price">250 000 ₽</div>
                <div class="b-post__txt">Требуется опытный Full-Stack разработчик для создания B2B-платформы</div>
            </div>
        ';

        $shortDetailHtml = '<div id="projectp1004">Устранить мелкий баг в форме обратной связи на сайте.</div>';
        $fullDescription = str_repeat('Требуется спроектировать архитектуру базы данных PostgreSQL, реализовать личный кабинет дилера на Laravel 11 и React, настроить обмен с 1С и очередей в Redis. ', 3);
        $validDetailHtml = '<div id="projectp1005">'.$fullDescription.'</div>';

        $mock = $this->createMock(Fl::class);
        $mock->expects($this->exactly(2))
            ->method('getProjects')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], $initialHtml),
                new Response(200, [], $catalogHtml)
            );
        $mock->expects($this->once())
            ->method('applyFilter')
            ->with('test-csrf-123')
            ->willReturn(new Response(200, [], '{"result":true}'));
        $mock->expects($this->exactly(2))
            ->method('getProject')
            ->willReturnCallback(fn(string $uri) => str_contains($uri, '1004') ? new Response(200, [], $shortDetailHtml) : new Response(200, [], $validDetailHtml));

        $this->container->add(Fl::class, $mock);

        $dispatcher = $this->container->get(FlDispatcher::class);
        $this->assertTrue($dispatcher->dispatch());

        $results = $dispatcher->getResults();
        $this->assertCount(1, $results, 'Агенту должен возвращаться только 1 крупный новый оффер с published IS NULL');
        $this->assertSame(1005, $results[0]['project_id']);
        $this->assertStringContainsString('PostgreSQL', $results[0]['description']);

        $this->assertNull(FlOrder::where('project_id', 1002)->first(), 'Клон 1002 должен быть отсечен по совпадению hash без записи дубля');
        $this->assertFalse(FlOrder::where('project_id', 1003)->first()->published, 'Мелкий заказ за 500 ₽ должен сохраниться с published=false');
        $this->assertFalse(FlOrder::where('project_id', 1004)->first()->published, 'Заказ с коротким описанием (<150 символов) должен сохраниться с published=false');
        $this->assertNull(FlOrder::where('project_id', 1005)->first()->published, 'Целевой крупный заказ должен иметь published=null');
    }

    #[TestDox('CheckNewOrdersTool и фоллбек на RSS: при сбое сессионного фильтра переключается на RSS, фильтрует по ключевым словам и отдает JSON')]
    public function testCheckNewOrdersToolRssFallbackAndErrorHandling(): void
    {
        $rssXml = '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0"><channel>
                <item>
                    <title><![CDATA[Интеграция 1С и Битрикс24 (Бюджет: 120 000 ₽)]]></title>
                    <link>https://www.fl.ru/projects/2001/integratsiya-bitrix.html</link>
                    <description><![CDATA[Разработка двустороннего обмена заказами и контрагентами по REST API]]></description>
                </item>
                <item>
                    <title><![CDATA[Дизайн логотипа для кофейни (Бюджет: 15 000 ₽)]]></title>
                    <link>https://www.fl.ru/projects/2002/logo.html</link>
                    <description><![CDATA[Нарисовать векторный логотип в Illustrator]]></description>
                </item>
            </channel></rss>';

        $detail2001 = '<div id="projectp2001">'.str_repeat('Полное техническое задание на разработку нестандартного модуля обмена 1С:УТ и коробочного Битрикс24 с очередями и обработкой конфликтов ИНН. ', 3).'</div>';

        $mock = $this->createMock(Fl::class);
        $mock->method('getProjects')->willReturnOnConsecutiveCalls(
            null,
            null
        );
        $mock->method('getRss')->willReturnOnConsecutiveCalls(
            new Response(200, [], $rssXml),
            null
        );
        $mock->method('getKeywords')->willReturn(['битрикс', 'laravel', 'php']);
        $mock->method('getProject')->willReturn(new Response(200, [], $detail2001));

        $this->container->add(Fl::class, $mock);

        $tool = $this->container->get(CheckNewOrdersTool::class);
        $ok = json_decode($tool->checkNewOrders(), true);
        $this->assertSame('ok', $ok['status']);
        $this->assertCount(1, $ok['new_orders']);
        $this->assertSame(2001, $ok['new_orders'][0]['project_id']);
        $this->assertSame('120 000 ₽', $ok['new_orders'][0]['budget']);

        $err = json_decode($tool->checkNewOrders(), true);
        $this->assertSame('error', $err['status']);
    }
}
