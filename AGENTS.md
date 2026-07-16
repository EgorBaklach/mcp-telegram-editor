# AGENTS.md — Руководство по архитектуре для AI-агентов

> Этот файл является техническим руководством для AI-агентов, начинающих работу с этим репозиторием. В нем описаны архитектурные правила, жизненные циклы запросов и особенности реализации компонентов.

---

## 💻 Среда исполнения и запуск

| Параметр | Значение |
| :--- | :--- |
| **PHP** | 8.3 (alpine CLI) |
| **Порт** | `9001` (хост) -> `9000` (встроенный PHP-сервер внутри Docker) |
| **Docker** | 256 MB RAM, 50% CPU на контейнер |
| **Dotenv** | `bootstrap/machine.php` → `Symfony\Dotenv` с `usePutenv(true)`. В конфигурационных файлах используйте `getenv('KEY') ?: 'default'` |
| **Резерв** | Директория `.backup/` — для локальных копий и чувствительных данных; содержимое исключено из Git |

---

## 📐 Принцип трёх слоёв (строго соблюдать)

```
src/Framework/   ← ⚙️ Ядро Microbe       — НЕ ИЗМЕНЯТЬ без крайней необходимости
src/Magistrale/  ← 🧱 Расширения проекта — интеграции, провайдеры, логгеры
src/App/         ← 🎯 Слой приложения    — контроллеры, стратегии, Middleware, Tools
```

### 1. `src/Framework/` — Неприкосновенное ядро
- Запрещено изменять под нужды конкретных прикладных фич.
- Компоненты ядра **никогда** не зависят от `src/Magistrale/` или `src/App/`.
- Ядро знает только о PSR-интерфейсах.

### 2. `src/Magistrale/` — Расширения проекта
- Здесь размещаются специфичные для проекта интеграции: провайдеры сервисов, логгеры, менеджеры сессий.
- `McpServiceProvider` динамически регистрирует MCP-инструменты из DI-ключа `mcp.tools`.

### 3. `src/App/` — Слой приложения
- Контроллеры, Middleware, стратегии роутера, классы инструментов.
- Единственное место, где допустима бизнес-логика приложения.

---

## 🌐 Жизненный цикл HTTP-запроса (MCP Flow)

```
public/index.php
  → Application::run()
  → LeagueRouter::dispatch()
  → McpJsonStrategy (стратегия роутера)
  → [CredentialsMiddleware → ProfilerMiddleware]
  → McpController::__invoke()
  → Mcp\Server::run(StreamableHttpTransport)
  → JSON-RPC обработка (initialize / tools/list / tools/call)
  → ResponseInterface
  → McpJsonStrategy::decorateResponse() [+ CORS]
  → SapiEmitter::emit()
```

**При ошибке роутинга (404/405/500):**
```
McpJsonStrategy::buildJsonResponseMiddleware() / getThrowableHandler()
  → CorsDecoratorMiddleware::process()   ← оборачивает ответ и инжектирует CORS
  → JSON { "status_code": 4xx, "reason_phrase": "..." }
```

---

## 🧩 Добавление нового MCP-инструмента

Процесс строго декларативный — **код провайдеров не трогать**.

### Шаг 1. Класс в `src/App/Tools/`

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

### Шаг 2. Запись в `config/definitions.php` под ключ `mcp.tools`

```php
new Definition('mcp.tools', [
    [
        'handler'     => [MyTool::class, 'myAction'],
        'name'        => 'my-tool',
        'description' => 'Описание вашего инструмента.'
    ]
])
```

`McpServiceProvider` читает этот ключ при старте и вызывает `Builder::addTool()` в цикле — без какого-либо изменения кода провайдеров.

---

## ⚙️ Жизненный цикл CLI

```
bin/console.php → Application::make()->cli() → ConsoleInterface::run()
```

**Правила для команд** (реализуют `Framework\Contracts\Console\CommandInterface`):
- **Запрещено** внедрять тяжелые зависимости в `__construct()`: Symfony Console инстанцирует все команды при старте.
- Тяжелые сервисы разрешаются **лениво** в методе `construct()`, который вызывается непосредственно перед выполнением команды.

```
SymfonyConsole::add()  → setContainer($container)   ← DI-контейнер внедрен
SymfonyConsole::find() → command->construct()        ← зависимости разрешены
command->execute()                                   ← бизнес-логика
```

---

## 🔑 CORS и стратегия роутера

`App\Strategies\McpJsonStrategy` — единственное место управления CORS в проекте:

| Метод | Назначение |
| :--- | :--- |
| `addResponseDecorator()` в конструкторе | CORS для успешных ответов контроллера |
| `buildJsonResponseMiddleware()` | CORS для ошибок `404`/`405` через `CorsDecoratorMiddleware` |
| `getThrowableHandler()` | CORS для непойманных исключений `500` через `CorsDecoratorMiddleware` |
| `injectCors()` (static) | Единая точка добавления заголовков — вызывается из всех трёх путей |

**Запрещено** добавлять CORS-заголовки вручную в контроллерах или Middleware — это нарушает Single Responsibility.

---

## ⚡ Особенности League/Container v4

1. **Явные дефиниции vs Автосвязывание**:
   - Классы, зарегистрированные через `$container->add()`, **игнорируют** `ReflectionContainer`. Все аргументы конструктора должны быть переданы вручную через `.addArgument()` или через фабричное замыкание.
   - `McpJsonStrategy` инстанцирует `ResponseFactory` прямо в своём конструкторе (без параметров) — это намеренное решение для обхода ограничения League Container при явной регистрации.

2. **Инфлекторы (Inflectors)**:
   - `InflectorAggregate::inflect()` вызывается для **всех** разрешённых значений, включая массивы. Аргумент `$object` не типизирован; защита: `if (!is_object($object)) return $object;`.

3. **Сервис-провайдеры**:
   - Реализуют `BootableServiceProviderInterface`. Регистрируются в `config/providers.php`.
   - `ProviderAggregate::register()` обходит все провайдеры через `continue`, не прерывая цикл при несовпадении.

---

## 🧪 Тестирование

```bash
# Запуск всех тестов с человекочитаемым выводом (TestDox)
docker exec mcp-telegram-editor vendor/bin/phpunit --testdox

# Очистка кеша PHPUnit
rm -rf .phpunit.cache
```

- Конфигурация: `phpunit.xml`
- Структура тестов в директории `tests/`:
  - `McpControllerTest.php` / `McpJsonStrategyTest.php` — тесты HTTP-слоя (контроллер, CORS-заголовки).
  - `ApplicationTest.php` — проверяет сборку DI-контейнера и доступность служб.
- `.phpunit.cache/` добавлен в `.gitignore`.
