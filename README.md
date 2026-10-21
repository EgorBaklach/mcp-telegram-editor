# MCP Telegram Editor 🐘

ИИ-ориентированный MCP-сервер (Model Context Protocol) на базе PHP-фреймворка **Microbe**, предназначенный для автоматизации управления Telegram-каналами и автономного мониторинга внешних источников данных через AI-агентов.

Проект спроектирован с учётом жёстких лимитов памяти (до `128M`), принципов SOLID, лаконичности кода и 100% покрытия бизнес-логики тестами (`38 tests, 164 assertions`).

---

## 🧬 Архитектура проекта

Проект построен на модульной архитектуре с использованием современного PHP 8.3:

* **DI-Контейнер**: `League\Container` с полной поддержкой auto-wiring (автоматического разрешения зависимостей), сервис-провайдеров и инфлекторов.
* **Модульные определения (`definitions/*.php`)**: Все конфигурации (агенты, CLI-команды, БД, зависимости, MCP-инструменты, миксины, OpenRouter, Redis, Telegram) разнесены по отдельным файлам в директории `definitions/` и автоматически загружаются в `config/definitions.php`.
* **Система миксинов (`Magistrale\Mixins`)**: Расширение базовых классов (`AbstractCommand`, диспетчеров, клиентов) через `MacroableInterface` (`Illuminate\Support\Traits\Macroable`) и `MixinInflector` на основе декларативной карты `definitions/mixins.php`.
* **База данных**: Автономный Eloquent (`Capsule\Manager` из Laravel) для ленивой и оптимизированной работы с PostgreSQL.
* **Сетевые клиенты (`Magistrale\Clients`)**:
  * `Telegram` — обёртка над `GuzzleHttp\Client` с поддержкой Telegram Bot API 10.3 (`sendRichMessage` и `editMessageText` с `rich_message`).
  * `OpenRouter` — HTTP-клиент с автоматическим переключением (failover) по публичным NAT64 IPv6-шлюзам.
  * `Redis` — легковесный клиент поверх нативного TCP-сокета (протокол RESP) для атомарных распределённых блокировок (`SET NX EX` / `DEL`).
* **Драйверы AI-агентов (`Magistrale\Agents`)**: Абстракция запуска внешних AI-агентов (`AgyAgent`, `HermesAgent`) с изоляцией окружения (`HOME`) и автоочисткой по завершении.
* **Паттерн Dispatcher**: Разделение бизнес-логики по диспетчерам (Telegram, OpenRouter, миграции БД, мониторинг источников), реализующим единый `DispatcherInterface`.
* **MCP-сервер**: Динамическая регистрация инструментов (Tools) на базе `mcp/sdk`.

---

## 🛠️ Доступные MCP-инструменты

| Инструмент | Параметр | Тип | Описание |
|---|---|---|---|
| `ping` | `message` | `string` | Проверка связи с MCP-сервером. Возвращает `"pong: {message}"`. |
| `publish` | `post` | `string` | Публикует Rich HTML-пост в Telegram-канал (`sendRichMessage`). Сохраняет `message_id` и текст в БД. |
| `delete` | `messageId` | `integer` | Удаляет сообщение из Telegram-канала по его идентификатору и очищает запись в БД. |
| `delete_by_text` | `text` | `string` | Находит пост в БД по подстроке текста (регистронезависимый `ILIKE`) и удаляет его из Telegram-канала и БД. |
| `edit` | `messageId`, `text` | `integer`, `string` | Редактирует текст опубликованного сообщения в Telegram (`editMessageText` с `rich_message`) и синхронизирует его в локальной БД (`updateOrCreate`). |
| `search_posts` | `query` | `string` | Ищет опубликованные посты в локальной БД по ключевому слову/подстроке и возвращает JSON со списком совпадений (`message_id`, `text`). |
| `check_new_models` | — | — | Синхронизирует бесплатные модели OpenRouter с БД и возвращает JSON с данными новых неопубликованных моделей. |
| `mark_published` | `modelId` | `string` | Помечает модель OpenRouter как опубликованную (`published = true`) в БД по её `model_id` после выхода поста. |

---

## 🔧 Как реализованы Tools

Проект использует конвенцию **php-mcp-server**: любой PHP-класс становится MCP-инструментом через регистрацию в `definitions/mcp.php`. Никаких интерфейсов или базовых классов реализовывать не нужно — достаточно публичного метода с типизированными параметрами.

### Контракт (соглашение)

| Требование | Описание |
|---|---|
| Класс | `final class`, расположен в `src/App/Tools/` |
| Метод | Публичный, принимает строго типизированные параметры (`string`, `int`) |
| Возврат | `string` — `'success'` / `'failed'` или JSON-строка |
| Зависимости | Диспетчер или сервис передаётся через constructor injection (DI-контейнер разрешает его автоматически) |
| Валидация | Guard-clause в начале метода: `if(!$param) throw new InvalidArgumentException(...)` |

### Регистрация в `definitions/mcp.php`

Все инструменты регистрируются в секции `tools` файла `definitions/mcp.php` как массив дескрипторов:

```php
'tools' => [
    ['handler' => [PublishTool::class, 'publish'],               'name' => 'publish',          'description' => '...'],
    ['handler' => [PingTool::class, 'ping'],                     'name' => 'ping',             'description' => '...'],
    ['handler' => [DeleteTool::class, 'delete'],                 'name' => 'delete',           'description' => '...'],
    ['handler' => [DeleteByTextTool::class, 'deleteByText'],     'name' => 'delete_by_text',   'description' => '...'],
    ['handler' => [EditTool::class, 'edit'],                     'name' => 'edit',             'description' => '...'],
    ['handler' => [SearchPostsTool::class, 'search'],            'name' => 'search_posts',     'description' => '...'],
    ['handler' => [CheckNewModelsTool::class, 'checkNewModels'], 'name' => 'check_new_models', 'description' => '...'],
    ['handler' => [MarkPublishedTool::class, 'markPublished'],   'name' => 'mark_published',   'description' => '...'],
],
```

`McpServiceProvider` читает это определение и автоматически регистрирует каждый тул в `mcp/sdk`, разрешая зависимости класса через DI-контейнер.

---

## ⚙️ Диспетчеры и Миксины

Вся бизнес-логика разделена на диспетчеры, реализующие `Magistrale\Dispatchers\DispatcherInterface`. Низкоуровневые преобразования и технические проверки вынесены в профильные миксины (`src/Magistrale/Mixins/*Mixin.php`), которые автоматически подключаются через `MixinInflector` и карту `definitions/mixins.php`.

### 📦 Telegram-диспетчеры (`Magistrale\Dispatchers\Telegram` + `TelegramMixin`)

| Диспетчер | Описание |
|---|---|
| `AbstractDispatcher` | Базовый класс (`MacroableInterface`). Выполняет HTTP-запрос к Telegram API, проверяет статус и логирует ответ. Подключает `TelegramMixin` (`decodeResponse()`, `escapeLike()`). |
| `PublishDispatcher` | Отправляет сообщение через `sendMessage` (`sendRichMessage`). После успеха сохраняет запись (`message_id`, `text`) в таблицу `telegram_posts`. |
| `DeleteDispatcher` | Удаляет сообщение через `deleteMessage` по его `message_id` и удаляет запись из `telegram_posts`. |
| `DeleteByTextDispatcher` | Ищет последний пост в `telegram_posts` по экранированной подстроке текста (`ILIKE` с поддержкой триграммного GIN-индекса) и вызывает `DeleteDispatcher`. |
| `EditDispatcher` | Редактирует сообщение через `editMessage` (`editMessageText`) по его `message_id` и синхронизирует запись в `telegram_posts` через `updateOrCreate`. |
| `SearchPostsDispatcher` | Ищет до 10 последних постов в `telegram_posts` по подстроке (`ILIKE`) и возвращает результаты через `ResultInterface`. |

### 📦 OpenRouter-диспетчеры (`Magistrale\Dispatchers\OpenRouter` + `OpenRouterMixin`)

| Диспетчер | Описание |
|---|---|
| `AbstractDispatcher` | Базовый класс (`MacroableInterface`). Хранит зависимости (`Capsule`, `LoggerInterface`, `OpenRouter` клиент) и подключает `OpenRouterMixin` (`decodeModels()`, `isFreeModel()`, `formatReleasedAt()`). |
| `SyncDispatcher` | Запрашивает список моделей, фильтрует бесплатные (`isFreeModel`), сохраняет новые в `openrouter_models`, дозаполняет `released_at` для существующих и возвращает список новых моделей через `ResultInterface`. |

### 📡 Диспетчеры мониторинга источников (`Magistrale\Dispatchers\SourceMonitor`)

| Диспетчер | CLI-флаг | Описание |
|---|---|---|
| `AbstractDispatcher` | — | Захватывает атомарный Redis-лок (`lockKey`), разрешает драйвер AI-агента (`agy` или `hermes`), подготавливает изолированное окружение, запускает pipeline `source-monitor` и гарантированно очищает временные файлы и Redis-лок в `finally`. |
| `OpenRouterDispatcher` | `--openrouter=agent` | Запускает мониторинг бесплатных моделей OpenRouter (`lock:source:openrouter`) через указанного AI-агента (`agy` или `hermes`). |

### 🗄️ Миграционные диспетчеры (`Magistrale\Dispatchers\Migration` + `MigrationMixin`)

Реализуют паттерн Strategy для управления схемой базы данных через CLI-команду `db:migrate`. Подключают `MigrationMixin` (`formatMigrationName()`, `runMigration()`).

| Диспетчер | CLI-флаг | Описание |
|---|---|---|
| `UpDispatcher` | `--up` | Накатывает все непримененные миграции из `database/migrations/`, разбивая их на батчи. |
| `DownDispatcher` | `--down[=target]` | Откатывает миграции: `null` — последний батч, `all` — все, `{id}` — конкретная миграция по ID. |
| `CreateDispatcher` | `--new=Name` | Создаёт новый файл-шаблон миграции в `database/migrations/` по имени в `snake_case`. |

---

## 🗃️ База данных

### Модели (Eloquent)

| Модель | Таблица | Поля | Описание |
|---|---|---|---|
| `App\Models\Migration` | `migrations` | `id`, `migration`, `batch` | История выполненных миграций. Управляется автоматически системой миграций. |
| `App\Models\TelegramPost` | `telegram_posts` | `id`, `message_id`, `text`, `timestamps` | Локальная копия опубликованных постов Telegram. Позволяет реализовать поиск и удаление по тексту. |
| `App\Models\OpenRouterModel` | `openrouter_models` | `id`, `model_id`, `name`, `accessible`, `context_length`, `modality`, `published`, `released_at`, `timestamps` | Локальный реестр бесплатных моделей OpenRouter. Флаг `published` отслеживает публикацию обзора в канале. |

---

## ⚙️ Установка и запуск

### 1. Переменные окружения (`.env`)

Создайте файл `.env` в корне проекта (по образцу `.env.example`) и заполните настройки:

```env
DB_HOST=db
DB_PORT=5432
DB_DATABASE=mcp_editor
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password

REDIS_HOST=redis
REDIS_PORT=6379

TELEGRAM_BOT_TOKEN=your_telegram_bot_token
TELEGRAM_CHAT_ID=-100your_channel_id
```

### 2. Запуск Docker-контейнеров

```bash
docker compose up -d
```

Сервер запускается на HTTP Streamable-транспорте на внутреннем порту `9000` (проброшен на хост как `9001`).

### 3. Применение миграций

```bash
docker exec mcp-telegram-editor php bin/console.php db:migrate --up
```

### 4. Подключение MCP к клиенту (Antigravity CLI / Zed / Claude Desktop)

Добавьте следующую конфигурацию в файл настроек MCP (`~/.gemini/config/mcp_config.json`):

```json
{
  "mcpServers": {
    "mcp-telegram-editor": {
      "serverUrl": "http://127.0.0.1:9001/"
    }
  }
}
```

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
# Запустить мониторинг OpenRouter через агента agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=agy

# Запустить мониторинг OpenRouter через агента hermes
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=hermes
```

---

## 🧪 Тестирование

Все тесты запускаются внутри контейнера через PHPUnit (`23 tests, 123 assertions`):

```bash
docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
```

Покрытие тестами:

| Группа тестов | Что проверяется |
|---|---|
| `Application` | Инициализация DI-контейнера, инфлекторов миксинов (`MixinInflector`) и разрешение всех сервисов. |
| `Http` | Обработка CORS preflight (`McpController::options`) и установка CORS-заголовков в `McpJsonStrategy`. |
| `DatabaseMigration` | Подключение к PostgreSQL, `UpDispatcher`, `DownDispatcher` (по батчам, по ID, `all`, защита от нечисловых строк) и `CreateDispatcher` (`MigrationMixin`). |
| `Telegram` | Все диспетчеры и MCP-инструменты Telegram (`Publish`, `Edit`, `Delete`, `DeleteByText`, `SearchPosts`), откат транзакций при сбое API и экранирование `%`/`_` в `ILIKE` (`TelegramMixin`). |
| `OpenRouter` | `SyncDispatcher` (`OpenRouterMixin`, включая корректную обработку `NULL` в `released_at`), `CheckNewModelsTool`, `MarkPublishedTool` и автоматическое переключение `OpenRouter` клиента на резервный NAT64-шлюз. |
| `SourceMonitor` | Атомарные блокировки `Redis` (`SET NX EX` / `DEL`), жизненный цикл эфемерного `HOME` в `AgyAgent`, пропуск дублей по локу, очистка в `finally` и коды возврата `SourceMonitorCommand` (`CommandMixin`). |

---

## 📁 Структура проекта

```
├── bin/                        # Точки входа (console.php, server.php)
├── bootstrap/                  # Сборка конфигурации контейнера (machine.php)
├── config/                     # Агрегаторы определений, провайдеров и инфлекторов
├── database/
│   └── migrations/             # Файлы миграций БД
├── definitions/                # Модульные конфигурации (agents, commands, database, mcp, mixins, openrouter, redis, telegram)
├── docs/
│   └── CODE_STYLE.md           # Правила архитектуры и форматирования кода
├── routes/                     # Маршруты HTTP
├── src/
│   ├── App/
│   │   ├── Controllers/        # McpController
│   │   ├── Middlewares/        # CORS, Credentials, Profiler
│   │   ├── Models/             # Eloquent-модели (Migration, TelegramPost, OpenRouterModel)
│   │   ├── Strategies/         # McpJsonStrategy
│   │   └── Tools/              # MCP-инструменты (Ping, Publish, Delete, DeleteByText, Edit, SearchPosts, CheckNewModels, MarkPublished)
│   ├── Cli/
│   │   ├── Commands/           # AbstractCommand, HelloWorldCommand, MigrateCommand, SourceMonitorCommand
│   │   ├── Console/            # SymfonyConsole
│   │   └── Providers/          # CLI ServiceProvider
│   ├── Framework/              # Ядро фреймворка (Application, Router, Emitter, Inflectors, Providers)
│   └── Magistrale/
│       ├── Agents/             # Драйверы AI-агентов (AgentInterface, AbstractAgent, AgyAgent, HermesAgent)
│       ├── Clients/            # Клиенты (Telegram, OpenRouter, Redis)
│       ├── Dispatchers/
│       │   ├── Migration/      # AbstractDispatcher, UpDispatcher, DownDispatcher, CreateDispatcher
│       │   ├── OpenRouter/     # AbstractDispatcher, SyncDispatcher
│       │   ├── SourceMonitor/  # AbstractDispatcher, OpenRouterDispatcher
│       │   └── Telegram/       # AbstractDispatcher, PublishDispatcher, DeleteDispatcher, DeleteByTextDispatcher, EditDispatcher, SearchPostsDispatcher
│       ├── Inflectors/         # MixinInflector
│       ├── Logging/            # ConsoleLogger, StderrLogger
│       ├── Mixins/             # MacroableInterface, CommandMixin, MigrationMixin, OpenRouterMixin, RedisMixin, TelegramMixin
│       └── Providers/          # DatabaseServiceProvider, McpServiceProvider
├── tests/                      # Тесты PHPUnit (23 теста)
├── _ide_helper.php             # Стабы для IDE (Intelephense)
├── docker-compose.yml
└── .env.example
```
