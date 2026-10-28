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
5. **Снапшот после правок**: Сразу после внесения любых изменений в файлы (и прогона тестов) агент обязан вычитать изменённые файлы (`read_file`), чтобы зафиксировать актуальный снапшот в контексте перед возможными ручными правками пользователя в IDE.
6. **Git и Коммиты**: Запрещено делать автоматические коммиты и пуши в удаленный репозиторий. Все изменения должны оставаться в рабочей копии, чтобы пользователь видел diff. Коммиты и отправка (push) — строго по явному запросу!

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
* **Строгое разделение `Magistrale` и `App`**: В `src/Magistrale/` размещаются исключительно переиспользуемые и инфраструктурные реализации (запрещено любое упоминание неймспейса `App\`). Вся прикладная бизнес-логика проекта размещается в `src/App/`.
* **Модульные определения (`definitions/*.php`)**: Все конфигурации сервисов, агентов, CLI-команд, миксинов, хелперов, операторов, клиентов и MCP-инструментов разнесены по тематическим файлам в корневой директории `definitions/` и автоматически собираются в `config/definitions.php` через `Glob::glob('definitions/*.php')`.
* **Система миксинов (`Magistrale\Mixins` и `App\Mixins`)**: Неочевидные технические проверки и низкоуровневые преобразования выносятся в классы-миксины (`MixinInterface`) в `src/Magistrale/Mixins/*Mixin.php` и `src/App/Mixins/*Mixin.php` и подключаются к целевым классам (`MacroableInterface`) через `Magistrale\Inflectors\MixinInflector` и карту `definitions/mixins.php`. При создании новых миксинов или добавлении в них обратных обращений к целевому инстансу (`$this` / `static`) обязательно синхронно пополнять `_ide_helper.php` (`@method`-аннотациями для целевого класса).
* **Система операторов и хелперов (`OperatorInterface`)**: Как предметные операторы (`definitions/operators.php`), так и переиспользуемые хелперы (`definitions/helpers.php`, например `Magistrale\Helpers\ArrayHelper` для точечной нотации `'array.get'`) реализуют единый контракт `Magistrale\Contracts\OperatorInterface` и автоматически подключаются в `CallbackInterface` целевого объекта через `CallbackInflector` и магию `AbstractInflector::__call()`.

---

## 🗄️ Слой базы данных (PostgreSQL & Standalone Eloquent)

* **Подключение**: Настраивается лениво через `DatabaseProvider` (`PDO::class` и `Capsule\Manager`).
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
* **Диспетчеры**: `UpDispatcher`, `DownDispatcher` и `CreateDispatcher` (`src/Magistrale/Dispatchers/Migration/`), наследующие `AbstractDispatcher` с подключённым `MigrateOperator` / `MigrationMixin`.
* **Модель Migration**: История миграций хранится в БД и управляется через Eloquent модель `Magistrale\Models\Migration` с полями `id`, `migration`, `batch`.
* **Файловая система и логирование**: Используется `symfony/filesystem` и единый `ConsoleLoggerInterface` (`ConsoleLogger`).

### 2. Мониторинг внешних источников (`source:monitor`)
```bash
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --fl=agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --hh=agy
docker exec mcp-telegram-editor php bin/console.php source:monitor --openrouter=hermes
```
* **Диспетчеры источников**: Наследуют `App\Dispatchers\SourceMonitor\AbstractDispatcher` (`OpenRouterDispatcher`, `FLDispatcher`, `HHDispatcher`).
* **Защита от гонок (Redis Lock)**: Перед запуском AI-агента захватывается атомарный распределённый лок через `RedisInterface` (`Magistrale\Providers\RedisProvider` + `RedisMixin::encode()`, `SET key 1 NX EX ttl` по прямому TCP RESP-протоколу) и гарантированно освобождается в блоке `finally`.
* **Драйверы AI-агентов (`Magistrale\Agents`)**: Реализуют `AgentInterface` и настраиваются в `definitions/agents.php`:
  * `AgyAgent` — создаёт изолированный эфемерный каталог `HOME` в `/tmp/agy-home-{source}`, прокидывает симлинки конфигурации и запускает процесс `agy`, удаляя временный `HOME` по завершении.
  * `HermesAgent` — заготовка драйвера под протокол ACP (Agent Communication Protocol).

---

## 🧪 Тестирование и Среда разработки (LSP/IDE)

* Запуск всех тестов (`28 tests, 186 assertions`):
  ```bash
  docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
  ```
* Покрытие включает: `ApplicationTest` (DI-контейнер и система миксинов), `HttpTest` (CORS и стратегии ответов), `DatabaseMigrationTest` (Eloquent и все сценарии `db:migrate`, включая защиту от невалидных целей), `TelegramTest` (все 7 инструментов включая `notify`, `TelegramObserver`, откат транзакций при сетевых сбоях и экранирование спецсимволов `ILIKE`), `OpenRouterTest` (`OpenRouterDispatcher`, `ingest` через `ON CONFLICT DO NOTHING RETURNING`, NAT64 failover и живые уведомления `TelegramObserver`), `FLTest` (`FLDispatcher`, `FLOperator`, `FLMixin`, `CheckNewOrdersTool`, `MarkOrderTool`, дедупликация по `hash`, фильтрация мелких задач, RSS-фоллбек и живые уведомления `TelegramObserver` о перехваченных HTTP-сбоях), `HHTest` (`HHDispatcher`, `HHOperator`, `HHMixin`, `CheckNewVacanciesTool`, `MarkVacancyTool`, парсинг `HH-Lux-InitialState`, склейка мультирегиональных клонов в `address`, `hh_schedule[]` и RSS-фоллбек) и `SourceMonitorTest` (Redis-локи, жизненный цикл эфемерного `HOME`, уведомления об ошибках агента и коды возврата CLI для `--openrouter`, `--fl` и `--hh`).
* Для помощи LSP/IDE (Intelephense) используется файл `_ide_helper.php`:
  * **Обязательное пополнение для миксинов**: При создании новых миксинов (`src/Magistrale/Mixins/*Mixin.php` и `src/App/Mixins/*Mixin.php`) агент обязан автоматически дополнять `_ide_helper.php` соответствующими `@method`-аннотациями на целевом классе, чтобы избежать предупреждений LSP (`Undefined method`).
  * В остальных случаях (вне системы миксинов) дополнять `_ide_helper.php` заглушками можно только по прямому запросу пользователя.

---

## 🎯 Текущий статус разработки (Session State)

* **Архитектура слоёв (`Framework` / `Magistrale` / `App`)**: Завершено строгое разделение инфраструктурного ядра `Magistrale` (0 зависимостей от `App`) и прикладного слоя `App`, стандартизировано именование `[Назначение][Слой]`, унифицированы инфлекторы (`AbstractInflector`), миксины (`MixinInterface` + `MacroableInterface`), колбэки, операторы и хелперы (`OperableInterface` + `CallbackInterface` + `OperatorInterface` + `ArrayHelper` через `CallbackInflector`), наблюдатели (`ObserverInterface` + `ObservableInterface` + `ObserverInflector` + `AbstractObserver` / `TelegramObserver`) и пакетная вставка БД (`QueryMixin::ingest()` / `EloquentMixin::ingest()` через `INSERT ... ON CONFLICT DO NOTHING RETURNING`).
* **Telegram Bot API 10.3 & Personal Notifications**: Поддержана отправка и редактирование постов через `sendRichMessage` и `editMessageText` (`rich_message`), а также личные уведомления админу (`TELEGRAM_ADMIN_ID`) об ошибках пайплайнов и аналитических инсайтах через `TelegramObserver` и MCP-инструмент `notify` (`NotifyTool`).
* **OpenRouter Tracker & Source Monitor**: Реализован полный цикл мониторинга бесплатных моделей OpenRouter (`check_new_models`, `mark_published`), NAT64 IPv6 failover и оркестрация фонового запуска AI-агентов через `source:monitor --openrouter=agy` с Redis-блокировками.
* **FL.ru Tracker & Source Monitor**: Полностью реализован пайплайн мониторинга заказов FL.ru (`fl_orders` с `PRIMARY KEY (project_id)`, `FLClient`, `FLOperator`, `FLMixin`, `Source\FLDispatcher`, `SourceMonitor\FLDispatcher`, инструменты `check_new_orders` и `mark_order`, CLI-вызов `source:monitor --fl=agy`).
* **HH.ru Tracker & Source Monitor**: Полностью реализован пайплайн мониторинга вакансий HH.ru (`hh_vacancies` с `PRIMARY KEY (vacancy_id)` и `hh_schedule[]`, `HHClient`, `HHOperator`, `HHMixin`, `Source\HHDispatcher`, `SourceMonitor\HHDispatcher`, инструменты `check_new_vacancies` и `mark_vacancy`, CLI-вызов `source:monitor --hh=agy`).

---

## 📡 Скиллы агента (`.agents/skills/`)

* **`developer-profile`** — профессиональный профиль, стек технологий, портфолио проектов и коммерческие условия разработчика (приватные контакты вынесены в локальный `.agents/skills/developer-profile/.env`).
* **`source-monitor`** — универсальный pipeline мониторинга внешних источников данных (5 шагов: обнаружение → анализ → подготовка материала → доставка → фиксация). Подключённые источники: `openrouter` (бесплатные AI-модели), `fl` (дайджест заказов FL.ru с автогенерацией отклика) и `hh` (дайджест вакансий HH.ru с автогенерацией сопроводительного письма).
* **`telegram_editor_strategy`** — правила составления лонгридов для канала Strong Elephant (тональность, Rich HTML формат с таблицами, списками, заголовками и аккордеонами, объём 4000-8000 символов, методология Фундамент → Каркас → Структура → Финал).
