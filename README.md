# MCP Telegram Editor 🐘

ИИ-ориентированный MCP-сервер (Model Context Protocol) на базе PHP-фреймворка **Microbe**, предназначенный для автоматизации управления Telegram-каналами.

Проект спроектирован с учетом жестких лимитов памяти (до `128M`), принципов SOLID/SOLID, лаконичности кода и 100% покрытия бизнес-логики тестами.

---

## 🧬 Архитектура проекта

Проект построен на модульной архитектуре с использованием современного PHP 8.3:
* **DI-Контейнер**: `League\Container` с полной поддержкой auto-wiring (автоматического разрешения зависимостей).
* **База данных**: Автономный Eloquent (`Capsule\Manager` из Laravel) для ленивой и оптимизированной работы с PostgreSQL.
* **Паттерн Dispatcher**: Разделение бизнес-логики по диспетчерам (миграции, интеграции с Telegram), реализующим единый `DispatcherInterface`.
* **MCP Сервер**: Автоматическая и динамическая регистрация инструментов (Tools) на базе `mcp/sdk`.

---

## 🛠️ Доступные MCP Инструменты

1. **`ping`** — проверка связи с MCP-сервером. Возвращает `"pong: {message}"`.
2. **`publish`** — публикация нового текстового поста в Telegram-канал. Принимает параметр `post` (string).
3. **`delete`** — удаление поста из Telegram-канала. Принимает параметр `messageId` (integer).

---

## ⚙️ Установка и запуск

### 1. Переменные окружения (`.env`)
Создайте файл `.env` в корне проекта (по образу `.env.example`) и заполните настройки:
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
Запуск базы данных PostgreSQL и самого MCP-сервера в фоновом режиме:
```bash
docker compose up -d
```
Сервер запускает HTTP SSE-транспорт на внутреннем порту `9000` (проброшен на хост как `9001`).

### 3. Подключение MCP к клиенту (например, Antigravity CLI / Zed / Claude Desktop)
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

## 🚀 Командный интерфейс (CLI)

### Работа с миграциями базы данных:
* Накатить все миграции:
  ```bash
  docker exec mcp-telegram-editor php bin/console.php db:migrate --up
  ```
* Откатить миграции:
  ```bash
  docker exec mcp-telegram-editor php bin/console.php db:migrate --down
  ```
* Создать шаблон новой миграции:
  ```bash
  docker exec mcp-telegram-editor php bin/console.php db:migrate --new=TableName
  ```

---

## 🧪 Тестирование

Все тесты запускаются внутри контейнера через PHPUnit:
```bash
docker exec mcp-telegram-editor vendor/bin/phpunit --testdox
```
Тесты Telegram-диспетчеров используют изолированные мок-объекты HTTP-клиента, поэтому они не требуют подключения к интернету и не засоряют реальный Telegram-канал.
