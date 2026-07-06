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
| `seov/` | локальные отчёты и планы, не для prod/git |

## Продакшен

| Параметр | Значение |
|----------|----------|
| Сервер | `188.120.251.205` |
| SSH | `ssh proauc` |
| Document root | `/var/www/proauc_ru_usr/data/www/proauc.ru` |
| Nginx config | `/etc/nginx/fastpanel2-available/proauc_ru_usr/proauc.ru.conf` |

## Зоны ответственности

| Зона | Кто | Файлы (примеры) |
|------|-----|-----------------|
| **Каталог / API** | другой разработчик | `functions.php` (slug, transients, `parseData`), `page-48.php`, `js/api/cars-catalog.js` |
| **SEO и техфичи темы** | мы | `rank-math.php`, `inc/blog-seo.php`, `inc/blog-articles.php`, `category.php`, шаблоны блога |

**Прод — source of truth для каталога.** При расхождении тянем с прода, не правим чужой код:

```bash
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/functions.php \
    wp-content/themes/proautospec/functions.php
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/page-48.php \
    wp-content/themes/proautospec/page-48.php
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/js/api/cars-catalog.js \
    wp-content/themes/proautospec/js/api/cars-catalog.js
```

Деплой SEO — точечный `scp` **только наших** файлов; три файла каталога на прод не перезаписываем.

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

## Деплой SEO на прод

```bash
THEME=wp-content/themes/proautospec
ROOT=/var/www/proauc_ru_usr/data/www/proauc.ru

for f in rank-math.php header.php footer.php home.php single.php category.php \
         loops/cards.php css/app.css spec.csv \
         inc/blog-seo.php inc/blog-articles.php; do
  scp "$THEME/$f" "proauc:$ROOT/$THEME/$f"
done
```

После CSS/JS — сбросить кэш Autoptimize в админке WP.

## Структура проекта

```
├── wp-content/
│   ├── themes/proautospec/   # основная тема сайта
│   │   ├── rank-math.php     # SEO-фильтры Rank Math
│   │   ├── inc/blog-seo.php  # блог: schema, CTA, FAQ
│   │   └── inc/blog-articles.php  # seed-статьи P3
│   └── plugins/              # плагины (Rank Math, CF7, ACF и др.)
├── wp-admin/, wp-includes/   # ядро WordPress
├── assets/                   # локально, не в Git
├── api/                      # локально, не в Git
├── seov/                     # локальные отчёты, не в Git
└── sitemap_*.xml             # карты сайта
```

## Статус (06.07.2026)

- P3 блог: **21 статья** в seed (волны 1–5), публикация по расписанию
- Волна 4: BYD Seal, кейс Sorento, PC200, доставка ДВ (06–15.07)
- Волна 5: Alphard, X-Trail, Vezel, Carnival, BYD vs Zeekr (18–31.07)
- Гео-сниппеты: `/avto-iz-yaponii/`, `/avto-iz-korei/`, `/spectehnika/`, `/kontaktyi/`
- sitemap лотов: лимит 250/страна → 500+ URL
- Клиентский отчёт: `seov/SEO-отчет-прогресс.md` (+ PDF)
- Каталог на проде — не трогаем (зона другого разработчика)

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
