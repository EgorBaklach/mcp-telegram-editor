# MCP Telegram Editor 🐘

ИИ-ориентированный MCP-сервер (Model Context Protocol) на базе PHP-фреймворка **Microbe**, предназначенный для автоматизации управления Telegram-каналами и автономного мониторинга внешних источников данных (OpenRouter, FL.ru) через AI-агентов.

Проект спроектирован с учётом жёстких лимитов памяти (до `128M`), принципов SOLID, лаконичности кода и 100% покрытия бизнес-логики тестами (`26 tests, 152 assertions`).

---

## 🧬 Архитектура проекта

Проект построен на трёхслойной модульной архитектуре с использованием PHP 8.3:

* **Слои (`Framework` / `Magistrale` / `App`)**:
  * `src/Framework/` — базовое ядро фреймворка Microbe (HTTP/CLI-рантайм, роутинг, провайдеры, инфлекторы).
  * `src/Magistrale/` — переиспользуемый инфраструктурный слой (клиенты API, драйверы AI-агентов, система миграций, Redis-локи, миксины, колбэки, наблюдатели и операторы; **0 зависимостей от `App\`**).
  * `src/App/` — прикладная бизнес-логика проекта (диспетчеры Telegram и источников, наблюдатели `TelegramObserver`, модели Eloquent, прикладные миксины/операторы, MCP-инструменты).
* **DI-Контейнер (`League\Container`)**: Полная поддержка auto-wiring, ленивых сервис-провайдеров и инфлекторов (`MixinInflector`, `CallbackInflector`, `ObserverInflector`, `SourceInflector`).
* **Модульные определения (`definitions/*.php`)**: Все конфигурации (`agents`, `clients`, `commands`, `database`, `dependencies`, `mcp`, `mixins`, `observers`, `operators`, `redis`, `sources`) разнесены по отдельным файлам в директории `definitions/` и автоматически собираются в `config/definitions.php`.
* **Трёхуровневая формула декомпозиции (`MacroableInterface` → `OperableInterface` → `Abstract*`)**:
  1. **Миксины (`*Mixin.php`)** — работа с «сырыми» данными (HTML/XML/JSON, регулярные выражения, экранирование, `ingest()`, `prepare()`).
  2. **Колбэки и Операторы (`CallbackInterface` / `*Operator.php`)** — операционные конвейеры (`pipe`), связывающие трансформации миксинов с сетевыми вызовами клиентов.
  3. **Абстракции и Классы (`Abstract*`, `*Dispatcher`, `*Observer`)** — общий конструктор и `setCallback()` вынесены в `Abstract*`, а дочерние классы исполняют конвейер в едином контуре `try / catch(Throwable $e)` с уведомлением наблюдателя (`$this->observer->update($e)`).
* **Система наблюдателей (`ObservableInterface` & `ObserverInterface`)**: Инфлектор `ObserverInflector` автоматически подключает `TelegramObserver` к клиентам источников (`FlClient`, `OpenRouterClient`), диспетчерам синхронизации/мониторинга и инструменту `NotifyTool`.
* **База данных**: Автономный Eloquent (`Capsule\Manager`) поверх PostgreSQL с пакетной идемпотентной вставкой `QueryMixin::ingest()` / `EloquentMixin::ingest()` (`INSERT ... ON CONFLICT DO NOTHING RETURNING`).
* **Сетевые клиенты (`Magistrale\Clients`)**:
  * `AbstractClient` — базовый клиент поверх `GuzzleHttp\Client` (`ObservableInterface`), автоматически перехватывающий сетевые сбои и не-200 ответы в `resolve()` с отправкой уведомления в `TelegramObserver` и возвратом `null` для продолжения работы пайплайна (переключения на фоллбек).
  * `TelegramClient` — поддержка Telegram Bot API 10.3 (`sendRichMessage` и `editMessageText` с `rich_message`), личных уведомлений админу (`notify()`) и SOCKS5-проксирования.
  * `OpenRouterClient` — HTTP-клиент с автоматическим переключением (failover) по публичным NAT64 IPv6-шлюзам.
  * `FlClient` — HTTP-клиент для работы с биржей FL.ru (сессионный AJAX-фильтр проектов с cookie-jar и автоматическим фоллбэком на RSS).
  * `RedisProvider` + `RedisMixin` — легковесный клиент поверх нативного TCP-сокета (протокол RESP) для атомарных распределённых блокировок (`SET NX EX` / `DEL`).
* **Драйверы AI-агентов (`Magistrale\Agents`)**: Абстракция запуска внешних AI-агентов (`AgyAgent`, `HermesAgent`) с изоляцией окружения (`/tmp/agy-home-{source}`) и гарантированной автоочисткой в блоке `finally`.

---

## 🛠️ Доступные MCP-инструменты

| Инструмент | Параметры | Тип | Описание |
|---|---|---|---|
| `ping` | `message` | `string` | Проверка связи с MCP-сервером. Возвращает `'pong: {message}'`. |
| `publish` | `post` | `string` | Публикует Rich HTML-пост в Telegram-канал (`sendRichMessage`). Сохраняет `message_id` и текст в БД. |
| `notify` | `message` | `string` | Отправляет личное Rich HTML-уведомление администратору (`TELEGRAM_ADMIN_ID`) через `TelegramObserver`. |
| `delete` | `messageId` | `integer` | Удаляет сообщение из Telegram-канала по его идентификатору и очищает запись в БД. |
| `delete_by_text` | `text` | `string` | Находит пост в БД по подстроке текста (регистронезависимый `ILIKE`) и удаляет его из Telegram-канала и БД. |
| `edit` | `messageId`, `text` | `integer`, `string` | Редактирует текст опубликованного сообщения в Telegram (`editMessageText` с `rich_message`) и синхронизирует его в локальной БД (`updateOrCreate`). |
| `search_posts` | `query` | `string` | Ищет опубликованные посты в локальной БД по ключевому слову/подстроке и возвращает JSON со списком совпадений (`message_id`, `text`). |
| `check_new_models` | — | — | Синхронизирует бесплатные модели OpenRouter с БД и возвращает JSON с данными новых неопубликованных моделей. |
| `mark_published` | `modelId` | `string` | Помечает модель OpenRouter как опубликованную (`published = true`) в БД по её `model_id` после выхода поста. |
| `check_new_orders` | — | — | Синхронизирует заказы FL.ru с БД, отсеивает мелкие задачи/стоп-слова и возвращает JSON с новыми крупными заказами (`published IS NULL`). |
| `mark_order` | `projectId`, `published` | `integer`, `bool` | Помечает заказ FL.ru в БД по его `project_id` как опубликованный (`true`) или отклонённый (`false`). |

---

## 🔧 Как реализованы Tools

Любой PHP-класс в `src/App/Tools/` становится MCP-инструментом через регистрацию в [`definitions/mcp.php`](definitions/mcp.php):

```php
'tools' => [
    ['handler' => [PublishTool::class, 'publish'],       'name' => 'publish',          'description' => '...'],
    ['handler' => [NotifyTool::class, 'notify'],         'name' => 'notify',           'description' => '...'],
    ['handler' => [PingTool::class, 'ping'],             'name' => 'ping',             'description' => '...'],
    ['handler' => [DeleteTool::class, 'delete'],         'name' => 'delete',           'description' => '...'],
    ['handler' => [DeleteByTextTool::class, 'delete'],   'name' => 'delete_by_text',   'description' => '...'],
    ['handler' => [EditTool::class, 'edit'],             'name' => 'edit',             'description' => '...'],
    ['handler' => [SearchPostsTool::class, 'search'],    'name' => 'search_posts',     'description' => '...'],
    ['handler' => [CheckNewModelsTool::class, 'check'],  'name' => 'check_new_models', 'description' => '...'],
    ['handler' => [MarkPublishedTool::class, 'mark'],    'name' => 'mark_published',   'description' => '...'],
    ['handler' => [CheckNewOrdersTool::class, 'check'],  'name' => 'check_new_orders', 'description' => '...'],
    ['handler' => [MarkOrderTool::class, 'mark'],        'name' => 'mark_order',       'description' => '...'],
],
```

`McpServiceProvider` читает это определение и автоматически регистрирует каждый тул в `mcp/sdk`, разрешая зависимости класса через DI-контейнер.

---

## ⚙️ Диспетчеры, Наблюдатели, Операторы и Миксины

Вся бизнес-логика разделена на диспетчеры (`DispatcherInterface`) и наблюдатели (`ObserverInterface`).

### 🔔 Наблюдатели (`App\Observers` + `TelegramMixin::prepare()`)

| Класс | Описание |
|---|---|
| `AbstractObserver` | Базовый класс наблюдателя (`ObserverInterface`, `MacroableInterface`, `OperableInterface`). Регистрирует конвейер `observer.notify` (`prepare` → `client->notify`). |
| `TelegramObserver` | Отправляет карточки исключений по шаблонам из `definitions/clients.php` (`⚠️ GuzzleException` с пометкой о продолжении работы пайпа, `❌ Throwable` при фатальном сбое) и прямые уведомления (`NotifyTool`) в личный чат администратора (`TELEGRAM_ADMIN_ID`). |

### 📦 Telegram-диспетчеры (`App\Dispatchers\Telegram` + `TelegramMixin`)

| Диспетчер | Описание |
|---|---|
| `AbstractDispatcher` | Базовый класс (`MacroableInterface`). Выполняет HTTP-запрос к Telegram API, проверяет статус и логирует ответ. Подключает `TelegramMixin` (`decode()`, `escape()`, `messageId()`). |
| `PublishDispatcher` | Отправляет сообщение через `publish()` (`sendRichMessage`). После успеха сохраняет запись (`message_id`, `text`) в таблицу `telegram_posts`. |
| `DeleteDispatcher` | Удаляет сообщение через `remove()` по его `message_id` и удаляет запись из `telegram_posts`. |
| `DeleteByTextDispatcher` | Ищет последний пост в `telegram_posts` по экранированной подстроке текста (`ILIKE` с триграммным GIN-индексом) и вызывает `DeleteDispatcher`. |
| `EditDispatcher` | Редактирует сообщение через `edit()` (`editMessageText`) по его `message_id` и синхронизирует запись в `telegram_posts` через `updateOrCreate`. |
| `SearchPostsDispatcher` | Ищет до 10 последних постов в `telegram_posts` по подстроке (`ILIKE`) и возвращает результаты через `ResultInterface`. |

### 📦 Диспетчеры синхронизации источников (`App\Dispatchers\Source`)

| Диспетчер | Миксин / Оператор | Описание |
|---|---|---|
| `OpenRouterDispatcher` | `EloquentMixin::ingest()` | Запрашивает список моделей OpenRouter (`nat64()` с фоллбеком на `models()`), фильтрует бесплатные через колбэк `openrouter.free` и атомарно вставляет новые модели через `ON CONFLICT DO NOTHING RETURNING`. |
| `FlDispatcher` | `FlMixin`, `FlOperator` | Получает список заказов с FL.ru через AJAX-фильтр (`fetch.native`) или RSS-фоллбэк (`fetch.rss`), дедуплицирует через `FlOrder::ingest()`, отсеивает мелкие задачи и стоп-слова (`fetch.description`), догружает полное ТЗ и обновляет кандидатов (`published IS NULL`). |

### 📡 Диспетчеры мониторинга источников (`App\Dispatchers\SourceMonitor`)

| Диспетчер | CLI-флаг | Описание |
|---|---|---|
| `AbstractDispatcher` | — | Захватывает атомарный Redis-лок (`static::lock`), разрешает драйвер AI-агента (`agy` или `hermes`), запускает pipeline `source-monitor`, уведомляет `TelegramObserver` при сбое и гарантированно очищает временные файлы и Redis-лок в `finally`. |
| `OpenRouterDispatcher` | `--openrouter=agent` | Запускает мониторинг бесплатных моделей OpenRouter (`lock:source:openrouter`) через указанного AI-агента. |
| `FlDispatcher` | `--fl=agent` | Запускает мониторинг и отбор заказов FL.ru (`lock:source:fl`) через указанного AI-агента. |

### 🗄️ Миграционные диспетчеры (`Magistrale\Dispatchers\Migration` + `MigrateOperator`)

| Диспетчер | CLI-флаг | Описание |
|---|---|---|
| `UpDispatcher` | `--up` | Накатывает все неприменённые миграции из `database/migrations/`, разбивая их на батчи. |
| `DownDispatcher` | `--down[=target]` | Откатывает миграции: `null` — последний батч, `all` — все, `{id}` — конкретная миграция по ID. |
| `CreateDispatcher` | `--new=Name` | Создаёт новый файл-шаблон миграции в `database/migrations/` по имени в `snake_case`. |

---

## 🗃️ База данных

### Модели (Eloquent)

| Модель | Таблица | Первичный ключ | Поля | Описание |
|---|---|---|---|---|
| `Magistrale\Models\Migration` | `migrations` | `id` | `id`, `migration`, `batch` | История выполненных миграций БД. |
| `App\Models\TelegramPost` | `telegram_posts` | `id` | `id`, `message_id`, `text`, `timestamps` | Локальная копия опубликованных постов Telegram-канала. |
| `App\Models\OpenRouterModel` | `openrouter_models` | `id` | `id`, `model_id`, `name`, `accessible`, `context_length`, `modality`, `published`, `released_at`, `timestamps` | Реестр бесплатных моделей OpenRouter. |
| `App\Models\FlOrder` | `fl_orders` | `project_id` | `project_id`, `hash`, `title`, `link`, `budget`, `description`, `published`, `timestamps` | Реестр заказов FL.ru (`published`: `null` — ожидает оценки агента, `true` — опубликован в канал, `false` — отсеян). |

---

## 🚀 CLI — Управление миграциями и мониторинг источников

### Миграции БД (`db:migrate`)
```bash
# Накатить все ожидающие миграции
docker exec mcp-telegram-editor php bin/console.php db:migrate --up

# Откатить миграции последнего батча
docker exec mcp-telegram-editor php bin/console.php db:migrate --down

# Откатить все миграции
docker exec mcp-telegram-editor php bin/console.php db:migrate --down=all

# Откатить конкретную миграцию по ID
docker exec mcp-telegram-editor php bin/console.php db:migrate --down=3

# Создать новый шаблон миграции
docker exec mcp-telegram-editor php bin/console.php db:migrate --new=TableName
```

### Мониторинг внешних источников (`source:monitor`)
```bash
# Запустить мониторинг бесплатных моделей OpenRouter через агента agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=agy

# Запустить мониторинг заказов FL.ru через агента agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --fl=agy

# Запустить мониторинг через агента hermes
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=hermes
```

---

## 🧪 Тестирование и верификация Наблюдателя (`TelegramObserver`)

Все тесты запускаются внутри контейнера через PHPUnit (`26 tests, 152 assertions`):

```bash
docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
```

| Группа тестов | Что проверяется |
|---|---|
| `Application` | Инициализация DI-контейнера, инфлекторов миксинов (`MixinInflector`) и разрешение всех сервисов. |
| `Http` | Обработка CORS preflight (`McpController::options`) и установка заголовков в `McpJsonStrategy`, `CorsDecoratorMiddleware`, `CredentialsMiddleware` и `ProfilerMiddleware`. |
| `DatabaseMigration` | Подключение к PostgreSQL, `UpDispatcher`, `DownDispatcher` (по батчам, по ID, `all`, защита от невалидных целей) и `CreateDispatcher` (`MigrateOperator`). |
| `Telegram` | Все диспетчеры и MCP-инструменты Telegram (`Publish`, `Notify`, `Edit`, `Delete`, `DeleteByText`, `SearchPosts`), откат транзакций при сбое API и экранирование `%`/`_` в `ILIKE` (`TelegramMixin`). |
| `OpenRouter` | `OpenRouterDispatcher` (`EloquentMixin::ingest()`), `CheckNewModelsTool`, `MarkPublishedTool` и автоматическое переключение `OpenRouterClient` на резервный NAT64-шлюз. |
| `Fl` | `FlDispatcher`, `FlOperator`, `FlMixin`, дедупликация по `project_id` и `hash`, отсев мелких задач/стоп-слов, фоллбэк на RSS, `CheckNewOrdersTool` и `MarkOrderTool`. |
| `SourceMonitor` | Атомарные блокировки `Redis` (`SET NX EX` / `DEL`), жизненный цикл эфемерного `HOME` в `AgyAgent`, пропуск дублей по локу, очистка в `finally` и коды возврата `SourceMonitorCommand` (`--openrouter` и `--fl`). |

### Порядок проверки `TelegramObserver` в личном чате администратора

При запуске `vendor/bin/phpunit --testdox` публичный канал (`TELEGRAM_CHAT_ID`) полностью изолирован моками, а `TelegramObserver` и `NotifyTool` доставляют в личный чат с ботом (`TELEGRAM_ADMIN_ID`) полный набор из **7 боевых уведомлений**, покрывающих все сценарии работы наблюдателя:

1. **`⚠️ GuzzleHttp\Exception\TransferException [AbstractClient.php:26]`** (`HTTP 503: GET projects/`) — перехват сбоя каталога FL.ru с продолжением работы пайпа (`fetch.native` → `fetch.rss`).
2. **`⚠️ GuzzleHttp\Exception\TransferException [AbstractClient.php:26]`** (`HTTP 503: GET projects/`) — перехват сбоя каталога FL.ru на сценарии полного отказа.
3. **`⚠️ GuzzleHttp\Exception\TransferException [AbstractClient.php:26]`** (`HTTP 502: GET rss/all.xml`) — перехват сбоя RSS-ленты FL.ru.
4. **`❌ RuntimeException [FlDispatcher.php:17]`** (`FL.ru catalog and RSS fetch failed`) — фатальная остановка пайплайна `FlDispatcher` при отказе обоих источников.
5. **`❌ RuntimeException [OpenRouterDispatcher.php:21]`** (`OpenRouter API request failed`) — фатальная остановка пайплайна `OpenRouterDispatcher`.
6. **`⚠️ GuzzleHttp\Exception\ConnectException [OpenRouterTest.php:164]`** (`cURL error 28: Operation timed out...`) — перехват таймаута на первом шлюзе NAT64 с автоматическим переключением на резервный IPv6-шлюз.
7. **`❌ Symfony\Component\Process\Exception\ProcessFailedException [AgyAgent.php:29]`** — фатальная ошибка дочернего процесса AI-агента в `SourceMonitor\OpenRouterDispatcher`.
8. **`✅ NotifyTool & TelegramObserver`** — прямое аналитическое Rich HTML-уведомление, отправленное через MCP-инструмент `notify`.

---

## 📁 Структура проекта

```
├── .agents/
│   └── skills/                 # Скиллы AI-агента (developer-profile, source-monitor, telegram_editor_strategy)
├── bin/                        # Точки входа (console.php, server.php)
├── bootstrap/                  # Сборка контейнера (machine.php)
├── config/                     # Агрегаторы определений, провайдеров и инфлекторов
├── database/
│   └── migrations/             # Файлы миграций БД
├── definitions/                # Модульные конфигурации (agents, clients, commands, database, dependencies, mcp, mixins, observers, operators, redis, sources)
├── docs/
│   └── CODE_STYLE.md           # Правила архитектуры и форматирования кода
├── routes/                     # Маршруты HTTP
├── src/
│   ├── App/
│   │   ├── Controllers/        # McpController
│   │   ├── Dispatchers/
│   │   │   ├── Source/         # AbstractDispatcher, OpenRouterDispatcher, FlDispatcher
│   │   │   ├── SourceMonitor/  # AbstractDispatcher, OpenRouterDispatcher, FlDispatcher
│   │   │   └── Telegram/       # AbstractDispatcher, PublishDispatcher, DeleteDispatcher, DeleteByTextDispatcher, EditDispatcher, SearchPostsDispatcher
│   │   ├── Inflectors/         # SourceInflector
│   │   ├── Middlewares/        # CorsDecoratorMiddleware, CredentialsMiddleware, ProfilerMiddleware
│   │   ├── Mixins/             # FlMixin, TelegramMixin
│   │   ├── Models/             # TelegramPost, OpenRouterModel, FlOrder, TestRecord
│   │   ├── Observers/          # AbstractObserver, TelegramObserver
│   │   ├── Operators/          # FlOperator
│   │   ├── Strategies/         # McpJsonStrategy
│   │   └── Tools/              # MCP-инструменты (Mcp/, Telegram/, OpenRouter/, Fl/)
│   ├── Cli/
│   │   ├── Commands/           # AbstractCommand, HelloWorldCommand, MigrateCommand, SourceMonitorCommand
│   │   ├── Console/            # SymfonyConsole
│   │   └── Providers/          # ServiceProvider
│   ├── Framework/              # Ядро фреймворка Microbe
│   └── Magistrale/
│       ├── Agents/             # Драйверы AI-агентов (AbstractAgent, AgyAgent, HermesAgent)
│       ├── Clients/            # AbstractClient, TelegramClient, OpenRouterClient, FlClient
│       ├── Contracts/          # Интерфейсы инфраструктуры
│       ├── Dispatchers/
│       │   └── Migration/      # AbstractDispatcher, UpDispatcher, DownDispatcher, CreateDispatcher
│       ├── Inflectors/         # AbstractInflector, CallbackInflector, MixinInflector, ObserverInflector
│       ├── Logging/            # ConsoleLogger, StderrLogger
│       ├── Mixins/             # CommandMixin, EloquentMixin, QueryMixin, RedisMixin
│       ├── Models/             # Migration
│       ├── Operators/          # AbstractOperator, MigrateOperator
│       ├── Providers/          # CallbackProvider, ClientProvider, DatabaseServiceProvider, McpServiceProvider, RedisProvider
│       └── Traits/             # MacroableTrait, ObservableTrait, OperableTrait
├── tests/                      # Тесты PHPUnit (26 тестов, 152 проверки)
├── _ide_helper.php             # Стабы для IDE (Intelephense)
├── docker-compose.yml
└── .env.example
```
