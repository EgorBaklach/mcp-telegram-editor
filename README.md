# php-mcp-server

**php-mcp-server** — это HTTP MCP-сервер (Model Context Protocol), построенный на базе легковесного PHP-фреймворка [Microbe](https://github.com/EgorBaklach/Microbe). Реализует спецификацию MCP через `StreamableHttpTransport`, предоставляя AI-клиентам (Claude, Gemini, Cursor и другим) доступ к зарегистрированным инструментам по HTTP.

---

## 🚀 Возможности

- **PHP 8.3** — строгая типизация, `readonly`-свойства, Constructor Property Promotion.
- **MCP SDK** — интеграция `mcp/sdk` с поддержкой `StreamableHttpTransport` (JSON-RPC 2.0 over HTTP).
- **JSON-first API** — все ответы (включая ошибки роутинга `404`/`405`/`500`) возвращаются в JSON через `McpJsonStrategy`.
- **CORS из коробки** — заголовки `Access-Control-Allow-*` автоматически добавляются ко **всем** ответам через `CorsDecoratorMiddleware`.
- **PSR-совместимость** — PSR-7, PSR-11, PSR-15, PSR-17 (Laminas Diactoros).
- **DI-контейнер** — `league/container` v4 с поддержкой сервис-провайдеров и инфлекторов.
- **Маршрутизация** — `league/route` v5 со стратегией `JsonStrategy`.
- **Dotenv** — конфигурация через `.env` на базе `symfony/dotenv`.
- **Docker** — готовое окружение с ограничениями ресурсов.
- **PHPUnit 11** — настроенная тестовая среда.

---

## 📁 Структура проекта

```text
├── bin/
│   └── console.php                     # Точка входа для CLI (Symfony Console)
├── bootstrap/
│   └── machine.php                     # Сборка DI-контейнера и загрузка Dotenv
├── config/
│   ├── definitions.php                 # DI-определения, mcp.settings, mcp.tools
│   ├── inflectors.php                  # Инфлекторы для автонастройки объектов
│   └── providers.php                   # Список регистрируемых сервис-провайдеров
├── public/
│   └── index.php                       # Front Controller (точка входа для веб-запросов)
├── routes/
│   └── web.php                         # Маршруты приложения (POST /, OPTIONS /)
├── src/
│   ├── App/                            # Слой приложения
│   │   ├── Controllers/
│   │   │   └── McpController.php       # HTTP-контроллер MCP-эндпоинта
│   │   ├── Middlewares/
│   │   │   ├── CorsDecoratorMiddleware.php  # Декоратор CORS-заголовков для ошибок
│   │   │   ├── CredentialsMiddleware.php    # Middleware аутентификации
│   │   │   └── ProfilerMiddleware.php       # Middleware профилировщика (X-Profiler-Time)
│   │   ├── Strategies/
│   │   │   └── McpJsonStrategy.php     # JSON-стратегия с CORS для всех ответов
│   │   └── Tools/                      # Папка для ваших MCP-инструментов (публикация, парсинг и т.д.)
│   ├── Cli/                            # Слой консольных команд
│   │   ├── Commands/
│   │   │   └── HelloWorldCommand.php   # Пример CLI-команды
│   │   ├── Console/
│   │   │   └── SymfonyConsole.php      # Обёртка Symfony Console
│   │   └── Providers/
│   │       └── ServiceProvider.php     # Провайдер регистрации CLI-команд
│   ├── Framework/                      # ⚙️ Ядро Microbe (НЕ ИЗМЕНЯТЬ)
│   │   ├── Application.php
│   │   ├── Contracts/                  # PSR-интерфейсы ядра
│   │   ├── Emitters/                   # SapiEmitter (PSR-15)
│   │   ├── Inflectors/                 # Механизм инфлекторов
│   │   ├── Providers/                  # Базовые провайдеры ядра
│   │   ├── Routers/                    # LeagueRouter (league/route)
│   │   └── Strategies/                 # ApplicationStrategy (базовая)
│   └── Magistrale/                     # 🧱 Слой расширений проекта
│       ├── Logging/
│       │   └── StderrLogger.php        # PSR-3 логгер в stderr
│       └── Providers/
│           └── McpServiceProvider.php  # Провайдер MCP-сервера и StreamFactory
├── tests/
│   ├── ApplicationTest.php             # Интеграционные тесты сборки DI-контейнера
│   ├── McpControllerTest.php           # Тесты для контроллера MCP
│   └── McpJsonStrategyTest.php         # Тесты CORS-стратегии
├── .backup/                            # Локальное резервное хранилище (в .gitignore)
├── .env.example                        # Шаблон переменных окружения
├── docker-compose.yml                  # Docker Compose конфигурация
└── phpunit.xml                         # Конфигурация PHPUnit
```

---

## 🛠️ Быстрый старт

### 1. Подготовка окружения
```bash
cp .env.example .env
```

### 2. Запуск в Docker
```bash
docker compose up -d --build
```
Сервер запустится на порту `9001`.

### 3. Проверка соединения (ping)
```bash
curl -X POST http://localhost:9001/ \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}},"id":1}'
```

### 4. Запуск CLI
```bash
docker exec mcp-telegram-editor php bin/console.php hello:world
```

### 5. Запуск тестов
```bash
docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
```

---

## 🔌 Подключение к AI-клиенту (AGY / Cursor / Claude Desktop)

Добавьте в `.agents/mcp_config.json` вашего воркспейса:

```json
{
  "mcpServers": {
    "mcp-telegram-editor": {
      "serverUrl": "http://<YOUR_SERVER_IP>:9001/"
    }
  }
}
```

Для **глобального** подключения используйте `~/.gemini/antigravity-cli/mcp_config.json`.

После добавления выполните в консоли `agy`:
```
/mcp reload
```

---

## 🧩 Добавление нового MCP-инструмента

Процесс добавления нового инструмента состоит из **двух шагов** и не требует изменения ни одного существующего файла кода.

### Шаг 1. Создайте класс инструмента в `src/App/Tools/`

```php
<?php namespace App\Tools;

use Mcp\Annotation\Tool\Param;

class MyTool
{
    public static function myAction(
        #[Param('Описание параметра')] string $input
    ): string {
        return "Результат: {$input}";
    }
}
```

### Шаг 2. Зарегистрируйте инструмент в `config/definitions.php`

```php
new Definition('mcp.tools', [
    // ... существующие инструменты ...
    [
        'handler'     => [MyTool::class, 'myAction'],
        'name'        => 'my-tool',
        'description' => 'Описание вашего инструмента.'
    ]
])
```

Готово! Инструмент сразу становится доступен через MCP без перезапуска Docker.

---

## ⚙️ Архитектура

### JSON-стратегия и CORS

`App\Strategies\McpJsonStrategy` наследует `League\Route\Strategy\JsonStrategy` и:
- Через `addResponseDecorator()` добавляет CORS-заголовки к **успешным** ответам контроллера.
- Переопределяет `buildJsonResponseMiddleware()` и `getThrowableHandler()`, оборачивая их в `CorsDecoratorMiddleware` — так CORS-заголовки гарантированно присутствуют и в ответах на ошибки `404`/`405`/`500`.

### Реестр инструментов

Инструменты объявляются декларативно в `config/definitions.php` под ключом `mcp.tools`. `McpServiceProvider` читает этот список из DI-контейнера и динамически регистрирует инструменты через `Mcp\Server\Builder::addTool()`. Это позволяет добавлять новые инструменты без изменения кода провайдеров.

### Принцип независимости ядра

Код в `src/Framework/` — неприкосновенное ядро Microbe. Он не знает о MCP, инструментах или CORS. Вся бизнес-логика сосредоточена в `src/App/`, а интеграции — в `src/Magistrale/`.
