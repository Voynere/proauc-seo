# Server & deployment

## Production

| Key | Value |
|-----|-------|
| Host | 188.120.251.205 |
| Panel | FASTPANEL, Ubuntu 24.04 |
| SSH alias | `proauc` |
| Document root | `/var/www/proauc_ru_usr/data/www/proauc.ru` |
| Nginx vhost | `/etc/nginx/fastpanel2-available/proauc_ru_usr/proauc.ru.conf` |
| Apache | `/etc/apache2/fastpanel2-available/proauc_ru_usr/proauc.ru.conf` |
| Site user | `proauc_ru_usr` |

## Sync from production

```bash
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

Large dirs separately:

```bash
scp -r proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/assets ~/Projects/proauc-seo/
scp -r proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/api ~/Projects/proauc-seo/
```

## Secrets (never commit)

- `wp-config.php` — DB credentials, salts
- `.htaccess` — server rewrite rules
- SSL certs under `/var/www/httpd-cert/` on server

## Code ownership

| Zone | Owner | Do not overwrite on deploy |
|------|-------|---------------------------|
| Catalog / API | other dev | `functions.php`, `page-48.php`, `js/api/cars-catalog.js` |
| SEO & theme tweaks | us | `rank-math.php`, `inc/blog-*.php`, `inc/seo-settings.php`, `category.php` |

**Production is source of truth for catalog.** Pull their files before merging our work:

```bash
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/functions.php \
    wp-content/themes/proautospec/functions.php
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/page-48.php \
    wp-content/themes/proautospec/page-48.php
scp proauc:/var/www/proauc_ru_usr/data/www/proauc.ru/wp-content/themes/proautospec/js/api/cars-catalog.js \
    wp-content/themes/proautospec/js/api/cars-catalog.js
```

## Deploy workflow

Репозиторий — **custom-code only** (как atk-ved / ferma-dv). GitHub Actions при push в `main` rsync-ит тему `proautospec`, **кроме** каталожных файлов (`functions.php`, `page-48.php`, `js/api/cars-catalog.js`).

1. Edit locally, commit to `main`
2. If catalog files diverged — `scp` from prod first (see above), adapt SEO on top
3. Push → GitHub Actions deploy, или точечный `scp` (см. README)
4. Clear Autoptimize cache if CSS/JS changed
5. Verify Rank Math sitemap / robots if SEO files changed

Secrets: `SERVER_HOST`, `SERVER_USER`, `SERVER_PORT`, `SERVER_SSH_KEY`, `SERVER_PATH`.

SEO deploy example:

```bash
THEME=wp-content/themes/proautospec
ROOT=/var/www/proauc_ru_usr/data/www/proauc.ru
for f in rank-math.php header.php footer.php home.php single.php category.php \
         loops/cards.php css/app.css spec.csv inc/blog-seo.php inc/blog-articles.php \
         inc/seo-settings.php inc/metrika-reports.php inc/seo-indexing.php; do
  scp "$THEME/$f" "proauc:$ROOT/$THEME/$f"
done
```

Пересборка sitemap лотов на проде: `https://proauc.ru/?sitemap-lots-create=1` (~1 мин, 517 URL при лимите 340/страна).

## Local-only: `seov/`

Клиентский отчёт и Дзен-черновики — только локально в `seov/`:

Каталог `seov/` в корне репозитория — отчёты, планы, runbooks. **Не в Git** (`.gitignore`: `/seov/`). На production **не деплоить**.

Деплой сегодня — точечный `scp` изменённых файлов в document root, не заливка всего репо; `seov/` на сервер не попадает, если явно не копировать. При полном `tar` с локальной машины на сервер — **исключать** `seov/`.

Клиентский SEO-отчёт о прогрессе: `seov/SEO-отчет-прогресс.md`, PDF: `seov/SEO-отчет-прогресс.pdf`. Пересборка PDF: `seov/.venv-pdf/bin/python seov/md_to_pdf.py seov/SEO-отчет-прогресс.md seov/SEO-отчет-прогресс.pdf`.
