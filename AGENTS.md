# AGENTS.md — Руководство по архитектуре и протокол разработки

> Данный файл является глобальной инструкцией по разработке для ИИ-агентов в этом репозитории. Здесь зафиксированы правила взаимодействия, жесткие технические ограничения сервера и архитектурные решения.

---

## 🧬 Архитектурный фундамент (Microbe Framework & php-mcp-server)

Этот проект построен на базе фреймворка **Microbe** и является наследником проекта **php-mcp-server**. 
**КРИТИЧЕСКОЕ ТРЕБОВАНИЕ ДЛЯ АГЕНТОВ:** Перед началом любой работы вы обязаны изучить и строго соблюдать правила этих родительских проектов:
1. 👉 **[Правила Microbe Framework](https://github.com/EgorBaklach/Microbe/blob/main/AGENTS.md)**
2. 👉 **[Правила php-mcp-server](https://github.com/EgorBaklach/php-mcp-server/blob/main/AGENTS.md)**

---

## 🤝 Протокол работы (Step-by-Step & Discussion-First)

Перед внесением любых изменений в код:
1. **Анализ идеи**: Изучить шаг, предложенный пользователем.
2. **Оценка ресурсов**: Оценить риски потребления RAM/CPU, OOM (Out of Memory) и параллельной нагрузки.
3. **Обсуждение**: Предложить оптимизированное решение и обсудить его с пользователем.
4. **Интеграция**: Приступать к коду и тестам только после явного одобрения.
5. **Git и Коммиты**: Запрещено делать автоматические коммиты и пуши в удаленный репозиторий. Все изменения должны оставаться в рабочей копии, чтобы пользователь видел diff. Коммиты и отправка (push) — строго по явному запросу!

> [!IMPORTANT]
> **РЕЖИМ ОБСУЖДЕНИЯ И ПЛАНИРОВАНИЯ:**
> Если пользователь **не написал** слово подтверждения, агент обязан оставаться исключительно в фазе планирования и аргументации. Запрещено модифицировать файлы до согласования.
>
> *Исключение*: Если пользователь пишет слово **"подтверждаю"** (или **"confirm"**), агент может пропустить шаги 1–3 и сразу перейти к реализации.

---

## ⚡ Технические лимиты сервера (VPS ~1GB RAM, 1 vCPU)

* **Ограничение памяти**: Лимит PHP в Docker-контейнере жестко задан в `128M`.
* **Потоковая обработка**: Запрещено загружать большие выборки из БД в память целиком. Всегда использовать PHP-генераторы (`yield`), курсоры БД (`cursor()`) и постраничную обработку (`chunk()`).
* **Скрапинг и сеть**: Все сетевые запросы должны иметь жесткие тайм-ауты (timeout <= 10s).
* **Однопоточность**: Запрещено использовать многопоточность или запускать тяжелые процессы без лимитов памяти.

---

## 🎨 Архитектура и Стиль кода (OOP & SOLID)

Полные правила проектирования, принципы написания чистого кода (отказ от лишних переменных, вынос нетривиальных проверок в миксины, лаконичность) и строгие стандарты форматирования (переносы скобок, отсутствие пробелов после управляющих конструкций, порядок и двойные отступы атрибутов класса, однострочники до 220 символов) вынесены в отдельный документ.

👉 **[Ознакомиться со стилем кода (docs/CODE_STYLE.md)](docs/CODE_STYLE.md)**

* **Окружение**: Для получения переменных окружения запрещено использовать глобальный массив `$_ENV`. Всегда используйте функцию `getenv()`.
* **Модульные определения (`definitions/*.php`)**: Все конфигурации сервисов, агентов, CLI-команд, миксинов, клиентов и MCP-инструментов разнесены по тематическим файлам в корневой директории `definitions/` и автоматически собираются в `config/definitions.php` через `Glob::glob('definitions/*.php')`.
* **Система миксинов (`Magistrale\Mixins`)**: Неочевидные технические проверки и низкоуровневые преобразования выносятся в классы-миксины в `src/Magistrale/Mixins/*Mixin.php` и подключаются к целевым классам (`MacroableInterface`) через `Magistrale\Inflectors\MixinInflector` и карту `definitions/mixins.php`. При создании новых миксинов или добавлении в них обратных обращений к целевому инстансу (`$this` / `static`) обязательно синхронно пополнять `_ide_helper.php` (`@method`-аннотациями для целевого класса и стабами свойств/констант для самого класса миксина).

---

## 🗄️ Слой базы данных (PostgreSQL & Standalone Eloquent)

* **Подключение**: Настраивается лениво через `DatabaseServiceProvider` (`PDO::class` и `Capsule\Manager`).
* **Ограничения Eloquent**: При выборках обязательно использовать `select()` для ограничения полей, а методы `get()` заменять на `cursor()` или `chunk()` для экономии оперативной памяти (лимит `128M`).

---

## 🚀 CLI-команды: Миграции и Мониторинг источников

Все консольные команды наследуют `Cli\Commands\AbstractCommand`, который централизованно разрешает диспетчеры из `definitions/commands.php` и работает с опциями через `CommandMixin` (`hasOption()`, `getOption()`).

### 1. Система миграций (`db:migrate`)
```bash
docker exec mcp-telegram-editor php bin/console.php db:migrate --up
docker exec mcp-telegram-editor php bin/console.php db:migrate --down
docker exec mcp-telegram-editor php bin/console.php db:migrate --new=TableName
```
* **Диспетчеры**: `UpDispatcher`, `DownDispatcher` и `CreateDispatcher` (`src/Magistrale/Dispatchers/Migration/`), наследующие `AbstractDispatcher` с подключённым `MigrationMixin` (`formatMigrationName()`, `runMigration()`).
* **Модель Migration**: История миграций хранится в БД и управляется через Eloquent модель `App\Models\Migration` с полями `id`, `migration`, `batch`.
* **Файловая система и логирование**: Используется `symfony/filesystem` и единый `ConsoleLoggerInterface` (`ConsoleLogger`).

### 2. Мониторинг внешних источников (`source:monitor`)
```bash
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=hermes
```
* **Диспетчеры источников**: Наследуют `Magistrale\Dispatchers\SourceMonitor\AbstractDispatcher` (например, `OpenRouterDispatcher`).
* **Защита от гонок (Redis Lock)**: Перед запуском AI-агента захватывается атомарный распределённый лок через `Magistrale\Clients\Redis` (`SET key 1 NX EX ttl` по прямому TCP RESP-протоколу с `RedisMixin::encodeResp()`) и гарантированно освобождается в блоке `finally`.
* **Драйверы AI-агентов (`Magistrale\Agents`)**: Реализуют `AgentInterface` и настраиваются в `definitions/agents.php`:
  * `AgyAgent` — создаёт изолированный эфемерный каталог `HOME` в `/tmp/agy-home-{source}`, прокидывает симлинки конфигурации и запускает процесс `agy`, удаляя временный `HOME` по завершении.
  * `HermesAgent` — заготовка драйвера под протокол ACP (Agent Communication Protocol).

---

## 🧪 Тестирование и Среда разработки (LSP/IDE)

* Запуск всех тестов (`38 tests, 164 assertions`):
  ```bash
  docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
  ```
* Покрытие включает: `ApplicationTest`, `DatabaseTest`, `DispatchersTest` (миграции), `McpControllerTest`, `McpJsonStrategyTest`, `OpenRouterTest` (11 тестов, включая NAT64 failover), `SourceMonitorTest` (5 тестов: Redis-локи, жизненный цикл эфемерного `HOME`, выбор AI-агентов) и `TelegramTest` (12 тестов).
* Для помощи LSP/IDE (Intelephense) используется файл `_ide_helper.php`:
  * **Обязательное пополнение для миксинов**: При создании новых миксинов (`src/Magistrale/Mixins/*Mixin.php`) или добавлении внутри замыканий миксина обратных обращений к свойствам/константам/методам целевого инстанса (`$this` или `static::`), агент обязан автоматически дополнять `_ide_helper.php` соответствующими `@method`-аннотациями на целевом классе и стабами констант/свойств на классе миксина, чтобы избежать предупреждений LSP (`Undefined method` / `Undefined class constant`).
  * В остальных случаях (вне системы миксинов) дополнять `_ide_helper.php` заглушками можно только по прямому запросу пользователя.

---

## 🎯 Текущий статус разработки (Session State)

* **База Данных и Миграции**: Настроен `DatabaseServiceProvider`, движок миграций (`db:migrate`) работает через `AbstractCommand` и `MigrationMixin`.
* **Модульные определения и Миксины**: Конфигурации вынесены в `definitions/*.php`; внедрена система миксинов (`MacroableInterface` + `MixinInflector` + `definitions/mixins.php`).
* **Telegram Bot API 10.3**: Поддержана отправка и редактирование постов через `sendRichMessage` и `editMessageText` (`rich_message`).
* **OpenRouter Tracker & Source Monitor**: Реализован полный цикл мониторинга бесплатных моделей OpenRouter (`check_new_models`, `mark_published`), NAT64 IPv6 failover и оркестрация фонового запуска AI-агентов через `source:monitor` с Redis-блокировками.

---

## 📡 Скиллы агента (`.agents/skills/`)

* **`source-monitor`** — универсальный pipeline мониторинга внешних источников данных. Описывает 5-шаговый процесс: обнаружение → исследование → составление поста → публикация → фиксация. Подключённый источник: OpenRouter (бесплатные AI-модели).
* **`telegram_editor_strategy`** — правила составления постов для канала Strong Elephant (тональность, Rich HTML формат с таблицами, списками, заголовками и аккордеонами, объём 4000-8000 символов, методология Фундамент → Каркас → Структура → Финал).
