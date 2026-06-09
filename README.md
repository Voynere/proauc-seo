# proauc-seo

Репозиторий исходников сайта [proauc.ru](https://proauc.ru) — WordPress с кастомной темой и SEO-настройками.

## Стек

- **CMS:** WordPress
- **Тема:** `proautospec` (на базе Picostrap / Bootstrap 5)
- **SEO:** Rank Math и связанные правки в теме
- **Хостинг:** FASTPANEL, Ubuntu 24.04, Nginx + Apache

## Что в репозитории

В Git попадает код WordPress, плагины, тема и конфигурация сайта. Не версионируются (см. `.gitignore`):

| Исключено | Причина |
|-----------|---------|
| `assets/`, `api/` | большие статические каталоги (~2.8 ГБ), синхронизируются с сервера отдельно |
| `wp-content/uploads/` | медиафайлы |
| `wp-content/cache/`, `wp-content/upgrade/` | runtime-кэш и временные файлы |
| `wp-config.php`, `.htaccess` | секреты и серверная конфигурация |
| `*.log`, `node_modules/` | логи и зависимости |

## Продакшен

| Параметр | Значение |
|----------|----------|
| Сервер | `188.120.251.205` |
| SSH | `ssh proauc` |
| Document root | `/var/www/proauc_ru_usr/data/www/proauc.ru` |
| Nginx config | `/etc/nginx/fastpanel2-available/proauc_ru_usr/proauc.ru.conf` |

## Синхронизация с сервера

На сервере нет `rsync`, используется `tar` по SSH:

```bash
mkdir -p ~/Projects/proauc-seo
ssh proauc 'cd /var/www/proauc_ru_usr/data/www/proauc.ru && tar czf - \
  --exclude="wp-content/uploads" \
  --exclude="wp-content/cache" \
  --exclude="wp-config.php" \
  --exclude=".htaccess" \
  --exclude="node_modules" \
  --exclude="*.log" \
  --exclude="wp-content/upgrade" \
  .' | tar xzf - -C ~/Projects/proauc-seo/
```

Каталоги `assets/` и `api/` при необходимости подтягиваются отдельно:

```bash
scp -r proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/assets ~/Projects/proauc-seo/
scp -r proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/api ~/Projects/proauc-seo/
```

## Структура проекта

```
├── wp-content/
│   ├── themes/proautospec/   # основная тема сайта
│   └── plugins/              # плагины (Rank Math, CF7, ACF и др.)
├── wp-admin/, wp-includes/   # ядро WordPress
├── assets/                   # локально, не в Git
├── api/                      # локально, не в Git
└── sitemap_*.xml             # карты сайта
```

## Лицензия

Ядро WordPress и большинство плагинов распространяются под GPL. См. `license.txt` в корне и лицензии отдельных компонентов.

## Память агента (RAG)

Локальный поиск по знаниям проекта (без внешних зависимостей, SQLite FTS5):

```bash
# Поиск
python3 .cursor/rag/rag.py query "деплой на proauc.ru"

# Сохранить решение для следующих сессий
python3 .cursor/rag/rag.py remember "Текст" --tag decision

# Пересобрать индекс
python3 .cursor/rag/rag.py index
```

- `.cursor/memory/` — факты и решения
- `.cursor/knowledge/` — документация (SEO, сервер, тема)
- `.cursor/rules/agent-memory.mdc` — правило для Cursor Agent
