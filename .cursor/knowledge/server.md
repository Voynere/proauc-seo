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

## Deploy workflow

1. Edit locally, commit to `main`
2. Test if possible on staging (none documented — production-only today)
3. Upload changed files to document root via `scp` or `tar` pipe
4. Clear Autoptimize cache if CSS/JS changed
5. Verify Rank Math sitemap / robots if SEO files changed
