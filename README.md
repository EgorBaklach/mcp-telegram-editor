# MCP Telegram Editor 🐘

ИИ-ориентированный MCP-сервер (Model Context Protocol) на базе PHP-фреймворка **Microbe**, предназначенный для автоматизации управления Telegram-каналами.

Проект спроектирован с учётом жёстких лимитов памяти (до `128M`), принципов SOLID, лаконичности кода и 100% покрытия бизнес-логики тестами.

---

## 🧬 Архитектура проекта

Проект построен на модульной архитектуре с использованием современного PHP 8.3:

* **DI-Контейнер**: `League\Container` с полной поддержкой auto-wiring (автоматического разрешения зависимостей).
* **База данных**: Автономный Eloquent (`Capsule\Manager` из Laravel) для ленивой и оптимизированной работы с PostgreSQL.
* **HTTP-клиент**: `Magistrale\Clients\Telegram` — обёртка над `GuzzleHttp\Client` для взаимодействия с Telegram Bot API.
* **Паттерн Dispatcher**: Разделение бизнес-логики по диспетчерам (миграции, интеграции с Telegram), реализующим единый `DispatcherInterface`.
* **MCP-сервер**: Динамическая регистрация инструментов (Tools) на базе `mcp/sdk`.

---

## 🛠️ Доступные MCP-инструменты

| Инструмент | Параметр | Тип | Описание |
|---|---|---|---|
| `ping` | `message` | `string` | Проверка связи с MCP-сервером. Возвращает `"pong: {message}"`. |
| `publish` | `post` | `string` | Публикует текстовый пост в Telegram-канал. Сохраняет `message_id` и текст в БД. |
| `delete` | `messageId` | `integer` | Удаляет сообщение из Telegram-канала по его идентификатору. |
| `delete_by_text` | `text` | `string` | Находит пост в БД по подстроке текста (регистронезависимый ILIKE) и удаляет его из Telegram-канала. |
| `edit` | `messageId`, `text` | `integer`, `string` | Редактирует текст опубликованного сообщения в Telegram по его ID и обновляет его в локальной БД. |
| `search_posts` | `query` | `string` | Ищет опубликованные посты в локальной БД по ключевому слову/подстроке и возвращает список совпадений (их ID и тексты). |

---

## 🔧 Как реализованы Tools

Проект использует конвенцию **php-mcp-server**: любой PHP-класс становится MCP-инструментом через регистрацию в `config/definitions.php`. Никаких интерфейсов или базовых классов реализовывать не нужно — достаточно публичного метода с типизированными параметрами.

### Контракт (соглашение)

| Требование | Описание |
|---|---|
| Класс | `final class`, расположен в `src/App/Tools/` |
| Метод | Публичный, принимает строго типизированные параметры (`string`, `int`) |
| Возврат | `string` — `'success'` при успехе, `'failed'` при ошибке диспетчера |
| Зависимости | Диспетчер передаётся через constructor injection (DI-контейнер разрешает его автоматически) |
| Валидация | Guard-clause в начале метода: `if(!$param) throw new InvalidArgumentException(...)` |

### Регистрация в `config/definitions.php`

Все инструменты регистрируются в определении `mcp.tools` как массив дескрипторов:

```php
new Definition('mcp.tools', [
    ['handler' => [PublishTool::class, 'publish'],           'name' => 'publish',         'description' => '...'],
    ['handler' => [PingTool::class, 'ping'],                 'name' => 'ping',            'description' => '...'],
    ['handler' => [DeleteTool::class, 'delete'],             'name' => 'delete',          'description' => '...'],
    ['handler' => [DeleteByTextTool::class, 'deleteByText'], 'name' => 'delete_by_text',  'description' => '...'],
    ['handler' => [EditTool::class, 'edit'],                 'name' => 'edit',            'description' => '...'],
    ['handler' => [SearchPostsTool::class, 'search'],        'name' => 'search_posts',    'description' => '...'],
])
```

Каждый дескриптор содержит:
- **`handler`** — `[ClassName::class, 'methodName']` — указатель на метод тула
- **`name`** — имя инструмента, под которым он виден в MCP-клиенте (snake_case)
- **`description`** — описание для LLM-агента (что делает инструмент)

`McpServiceProvider` читает это определение и автоматически регистрирует каждый тул в `mcp/sdk`, разрешая зависимости класса через DI-контейнер.

### Реализации

**`PingTool`** — тул без зависимостей, только проверка связи:
```php
final class PingTool
{
    public function ping(string $message = 'hello'): string
    {
        return "pong: {$message}";
    }
}
```

**`PublishTool`** — получает диспетчер через DI, делегирует ему всю логику:
```php
final class PublishTool
{
    public function __construct(private PublishDispatcher $dispatcher) {}

    public function publish(string $post): string
    {
        if(!$post) throw new InvalidArgumentException('$post must not be empty');
        return $this->dispatcher->dispatch($post) ? 'success' : 'failed';
    }
}
```

**`DeleteTool`** — принимает `int $messageId`:
```php
final class DeleteTool
{
    public function __construct(private DeleteDispatcher $dispatcher) {}

    public function delete(int $messageId): string
    {
        if(!$messageId) throw new InvalidArgumentException('$messageId must not be empty');
        return $this->dispatcher->dispatch($messageId) ? 'success' : 'failed';
    }
}
```

**`DeleteByTextTool`** — поиск и удаление поста по подстроке:
```php
final class DeleteByTextTool
{
    public function __construct(private DeleteByTextDispatcher $dispatcher) {}

    public function deleteByText(string $text): string
    {
        if(!$text) throw new InvalidArgumentException('$text must not be empty');
        return $this->dispatcher->dispatch($text) ? 'success' : 'failed';
    }
}
```

**`EditTool`** — редактирование поста по `messageId`:
```php
final class EditTool
{
    public function __construct(private EditDispatcher $dispatcher) {}

    public function edit(int $messageId, string $text): string
    {
        if(!$messageId) throw new InvalidArgumentException('$messageId must not be empty');
        if(!$text) throw new InvalidArgumentException('$text must not be empty');
        return $this->dispatcher->dispatch(['message_id' => $messageId, 'text' => $text]) ? 'success' : 'failed';
    }
}
```

**`SearchPostsTool`** — поиск постов в БД по подстроке:
```php
final class SearchPostsTool
{
    public function search(string $query): string
    {
        if(!$query) throw new InvalidArgumentException('$query must not be empty');

        $posts = TelegramPost::where('text', 'ILIKE', '%' . $query . '%')
            ->latest()
            ->limit(10)
            ->get(['message_id', 'text']);

        return json_encode($posts->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
```


> Чтобы добавить новый инструмент: создайте класс в `src/App/Tools/`, добавьте дескриптор в `mcp.tools` в `config/definitions.php` — больше ничего не нужно.

---

## ⚙️ Диспетчеры (Dispatchers)

Вся бизнес-логика разделена на диспетчеры, реализующие `Magistrale\Dispatchers\DispatcherInterface`.

### 📦 Telegram-диспетчеры (`Magistrale\Dispatchers\Telegram`)

Все Telegram-диспетчеры наследуют `AbstractDispatcher`, который инкапсулирует общую логику: вызов Guzzle-метода, проверку HTTP-статуса, логирование ответа и обработку ошибок через `try/catch`.

| Диспетчер | Описание |
|---|---|
| `AbstractDispatcher` | Базовый класс. Выполняет HTTP-запрос к Telegram API, логирует результат и хранит последний ответ в `$response`. |
| `PublishDispatcher` | Отправляет сообщение через `sendMessage`. После успеха сохраняет запись (`message_id`, `text`) в таблицу `telegram_posts`. |
| `DeleteDispatcher` | Удаляет сообщение через `deleteMessage` по его `message_id`. |
| `DeleteByTextDispatcher` | Ищет последний пост в `telegram_posts` по подстроке текста (регистронезависимый ILIKE-запрос с поддержкой GIN-индекса), вызывает `DeleteDispatcher` и удаляет запись из БД. |
| `EditDispatcher` | Редактирует сообщение через `editMessageText` (обёртка `editMessage` в клиенте) по его `message_id`, затем обновляет текст в `telegram_posts`. |

### 🗄️ Миграционные диспетчеры (`Magistrale\Dispatchers\Migration`)

Реализуют паттерн Strategy для управления схемой базы данных через CLI-команду `db:migrate`.

| Диспетчер | CLI-флаг | Описание |
|---|---|---|
| `UpDispatcher` | `--up` | Накатывает все непримененные миграции из `database/migrations/`, разбивая их на батчи. |
| `DownDispatcher` | `--down[=target]` | Откатывает миграции. `null` — последний батч, `all` — все, `{id}` — конкретная миграция по ID. |
| `CreateDispatcher` | `--new=Name` | Создаёт новый файл-шаблон миграции в `database/migrations/` по имени в snake_case. |

---

## 🗃️ База данных

### Модели (Eloquent)

| Модель | Таблица | Поля | Описание |
|---|---|---|---|
| `App\Models\Migration` | `migrations` | `id`, `migration`, `batch` | История выполненных миграций. Управляется автоматически системой миграций. |
| `App\Models\TelegramPost` | `telegram_posts` | `id`, `message_id`, `text`, `timestamps` | Локальная копия опубликованных постов Telegram. Позволяет реализовать поиск и удаление по тексту. |

### Миграции (`database/migrations/`)

| Файл | Таблица | Описание |
|---|---|---|
| `2026_07_16_000001_create_test_records_table.php` | `test_records` | Тестовая таблица для верификации подключения к БД. |
| `2026_07_19_183914_create_telegram_posts_table.php` | `telegram_posts` | Таблица локального маппинга опубликованных Telegram-постов. |
| `2026_07_20_121721_add_trgm_index_to_telegram_posts.php` | - | Включает расширение `pg_trgm` и создает триграммный GIN-индекс для быстрого поиска подстрок (`ILIKE`). |

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

## 🚀 CLI — Управление миграциями

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

---

## 🧪 Тестирование

Все тесты запускаются внутри контейнера через PHPUnit:

```bash
docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
```

Покрытие тестами:

| Группа тестов | Что проверяется |
|---|---|
| `Application` | Инициализация DI-контейнера и разрешение всех MCP-сервисов. |
| `Database` | Подключение к PostgreSQL, запись и чтение через Eloquent. |
| `Dispatchers` | Накатывание/откат миграций (по батчам, по ID, все сразу), создание файлов миграций. |
| `McpController` | Корректный ответ 204 на OPTIONS-запросы (CORS preflight). |
| `McpJsonStrategy` | Установка CORS-заголовков. |
| `Telegram` | Publish, Delete, DeleteByText диспетчеры с мок-объектами HTTP-клиента. Запись в БД при публикации. Поиск и удаление по подстроке текста. |

> Тесты Telegram-диспетчеров используют изолированные мок-объекты HTTP-клиента (PHPUnit MockBuilder). Реальные запросы к Telegram API не выполняются.

---

## 📁 Структура проекта

```
├── bin/                      # Точки входа (console.php, server.php)
├── bootstrap/                # Конфигурация контейнера (machine.php)
├── config/                   # Определения сервисов (definitions.php)
├── database/
│   └── migrations/           # Файлы миграций БД
├── docs/
│   └── CODE_STYLE.md         # Правила написания кода
├── routes/                   # Маршруты HTTP
├── src/
│   ├── App/
│   │   ├── Controllers/      # McpController
│   │   ├── Middlewares/      # CORS, Credentials, Profiler
│   │   ├── Models/           # Eloquent-модели (Migration, TelegramPost)
│   │   ├── Strategies/       # McpJsonStrategy
│   │   └── Tools/            # MCP-инструменты (Ping, Publish, Delete, DeleteByText)
│   ├── Cli/
│   │   ├── Commands/         # CLI-команды (HelloWorld, Migrate)
│   │   ├── Console/          # SymfonyConsole
│   │   └── Providers/        # CLI ServiceProvider
│   ├── Framework/            # Ядро фреймворка (Application, Router, Emitter, DI)
│   └── Magistrale/
│       ├── Clients/          # HTTP-клиенты (Telegram)
│       ├── Dispatchers/
│       │   ├── Migration/    # UpDispatcher, DownDispatcher, CreateDispatcher
│       │   └── Telegram/     # PublishDispatcher, DeleteDispatcher, DeleteByTextDispatcher
│       ├── Logging/          # MigrationLogger
│       └── Providers/        # DatabaseServiceProvider, McpServiceProvider
├── tests/                    # Тесты PHPUnit
├── _ide_helper.php           # Стабы для IDE (Intelephense)
├── docker-compose.yml
└── .env.example
```
