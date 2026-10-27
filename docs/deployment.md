# Deployment, backup and maintenance

This guide targets a Linux production host. The application was developed and verified locally on Windows/XAMPP, as documented in the README. Commands are equivalent.

## 1. Prerequisites

- PHP 8.2 or 8.3 with: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `gd` (WebP support), `zip`, `intl` recommended, `redis` (phpredis) for production.
- MySQL 8.0+ (recommended) or a supported MySQL-compatible server. Local verification used MariaDB 10.4.32; confirm the chosen database version is supported by your Laravel release.
- Composer 2.8+.
- Node.js 20+ and npm for building assets.
- Redis server for cache/queue/session.
- Nginx or Apache, HTTPS certificate.
- Process manager (systemd or Supervisor) for queue workers and scheduler.

## 2. Install

```bash
git clone <repository> /var/www/tajira
cd /var/www/tajira

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

npm ci
npm run build

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Create the database and a least-privilege database user:

```sql
CREATE DATABASE tajira CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tajira'@'localhost' IDENTIFIED BY 'a-long-random-password';
GRANT ALL PRIVILEGES ON tajira.* TO 'tajira'@'localhost';
FLUSH PRIVILEGES;
```

## 3. Production environment

Start from `.env.example` and replace every placeholder. Critical values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.example.com
APP_TIMEZONE=Africa/Nairobi

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tajira
DB_USERNAME=tajira
DB_PASSWORD=...

SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_SCHEME=tls
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="orders@your-domain"
```

Set approved business facts and policy placeholders:

- `TAJIRA_BUSINESS_NAME`, `TAJIRA_LEGAL_NAME`, `TAJIRA_PHONE`, `TAJIRA_WHATSAPP`, `TAJIRA_EMAIL`, `TAJIRA_SUPPORT_EMAIL`, `TAJIRA_ADDRESS_LINE`, `TAJIRA_CITY`, `TAJIRA_TAX_PIN`, `TAJIRA_DOMAIN`
- `TAJIRA_TAX_RATE_PERCENT`, `TAJIRA_TAX_INCLUSIVE`
- delivery/pickup/returns/warranty settings via env or the admin Settings screen
- social URLs and `TAJIRA_MAP_URL`
- `TAJIRA_REQUIRE_ADMIN_MFA=true` for production
- `TAJIRA_STAGING_PROTECT=true` only on staging, with `TAJIRA_STAGING_USER/PASSWORD`
- `TAJIRA_GLOBAL_NOINDEX=true` only while deliberately taking the site out of search

Never commit `.env` or credentials. Use the host's secret manager where available.

## 4. Database, storage, caches

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize        # config + routes + views + events
```

Do **not** run `db:seed` blindly in production. It seeds demo administrators only under explicit conditions; create administrators deliberately:

```bash
php artisan tajira:create-admin admin@example.com --name="Super Admin" --role=super_admin
```

Then sign in, enable MFA, and create additional least-privilege accounts.

## 5. Queue worker and scheduler

Queue emails, image optimisation-related work, payment reconciliation and feeds. Use Supervisor or systemd.

Supervisor program example:

```ini
[program:tajira-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tajira/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/tajira/storage/logs/worker.log
stopwaitsecs=3600
```

Scheduler cron:

```cron
* * * * * cd /var/www/tajira && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler releases expired stock reservations every minute, reconciles pending M-Pesa payments every five minutes, and publishes due scheduled products.

## 6. Web server

### Nginx

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    return 301 https://www.example.com$request_uri;
}

server {
    listen 443 ssl http2;
    server_name www.example.com;
    root /var/www/tajira/public;

    index index.php;
    charset utf-8;
    client_max_body_size 12m;

    ssl_certificate     /etc/letsencrypt/live/www.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/www.example.com/privkey.pem;

    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Enable PHP OPcache with production-appropriate memory and revalidation settings. Set `expose_php=Off`.

## 7. Staging protection and indexing

For staging:

```dotenv
APP_ENV=staging
TAJIRA_STAGING_PROTECT=true
TAJIRA_STAGING_USER=staging-user
TAJIRA_STAGING_PASSWORD=a-long-random-password
TAJIRA_GLOBAL_NOINDEX=true
```

The middleware requires HTTP Basic Auth and sends `X-Robots-Tag: noindex, nofollow`; the SEO service also emits noindex metadata and `robots.txt` disallows crawling. Verify with curl that staging returns 401 without credentials. Production must not carry these staging settings.

## 8. Payments

### M-Pesa Daraja

1. Obtain approved credentials from Safaricom: consumer key/secret, shortcode, passkey.
2. Set `MPESA_ENABLED=true`, `MPESA_ENV=sandbox` first, and the HTTPS callback URL.
3. Test STK push, successful callback, failed callback, timeout, duplicate callback, amount mismatch, and reconciliation.
4. Only after verification switch to `MPESA_ENV=production`.
5. Restrict/verify callback source IPs at the edge if Safaricom provides stable ranges. You may also set `MPESA_CALLBACK_IPS` (comma-separated) after confirming the correct ranges; the app already queries the STK status before committing a successful callback and queues reconciliation when uncertain.
6. Never mark an order paid from a browser redirect.

### Bank transfer

Enable only after entering approved bank details in admin Settings. The order stays unpaid until an authorised administrator verifies the credit and records a manual payment. Manual confirmation commits stock and writes an audit entry.

### Cards

`CardGateway` is deliberately disabled. It throws a clear error until an approved provider adapter is implemented, tested and enabled. Do not advertise card payments before then.

### Tax / eTIMS

No eTIMS integration is present. Do not claim eTIMS or tax compliance. Configure tax treatment with an adviser and implement a verified integration before making compliance claims.

## 9. Catalogue and content launch

1. Replace demo/placeholder content with approved Tajira content.
2. Configure delivery zones/charges, pickup instructions, coupons and settings.
3. Run catalogue discovery/import only with approval. Review every staging record; do not publish competitor descriptions, images or prices.
4. Verify manufacturer specifications and secure image rights.
5. Set approved Tajira prices/stock/warranty.
6. Publish products deliberately; unpublished starters remain drafts.
7. Build categories/navigation/guides and approved collections.
8. Validate SEO metadata, sitemaps, JSON-LD, robots and internal links.
9. Configure GA4/Search Console/Merchant Center IDs and consent.
10. Perform a full catalogue reconciliation. Do not claim completeness until every discovered URL has a documented disposition.

## 10. Backup and restore

### Database

```bash
mkdir -p /var/backups/tajira
mysqldump --single-transaction --routines --triggers \
  -u tajira -p tajira | gzip > /var/backups/tajira/db-$(date +%F-%H%M).sql.gz
```

### Files

Back up:

- `storage/app/public` (product images/documents/media)
- `storage/app` private reports (e.g. SEO link audit)
- `.env` via secure secret backup, not a public repository
- any generated feeds/sitemaps if persisted (sitemaps are cached and can be regenerated)

```bash
tar -czf /var/backups/tajira/files-$(date +%F).tar.gz \
  /var/www/tajira/storage/app \
  /var/www/tajira/.env
```

Encrypt off-site copies and set retention. Test restoration regularly:

```bash
# On a disposable database:
gunzip -c db-YYYY-MM-DD.sql.gz | mysql -u root -p tajira_restore_test
# Restore files to a temporary path, verify permissions, then point a staging copy at them.
```

A restore is not verified until the application boots, admin login works, product images load, an order can be viewed, and checkout can complete in the restored staging copy.

## 11. Monitoring and maintenance

- Monitor application errors (`storage/logs`), failed jobs, queue depth, scheduler last run, database slow queries, disk usage, TLS expiry and backup success.
- `php artisan about` and `php artisan queue:failed` are useful first checks.
- Run `composer audit` and update dependencies on a maintenance schedule; test on staging first.
- Rotate logs; keep debug off in production.
- Rebuild caches after deployment:
  ```bash
  php artisan migrate --force
  php artisan optimize:clear
  php artisan optimize
  php artisan queue:restart
  ```
- `php artisan payments:reconcile` is scheduled; also verify its output and failed jobs.
- `php artisan seo:audit --links` produces diagnostic reports.
- `php artisan customers:erase email@example.com` anonymises an approved deletion request. Review retention obligations first.

## 12. Rollback

Keep the previous release directory and database backup. On failure:

1. Put the site into maintenance mode (`php artisan down`).
2. Restore the previous code release.
3. Restore or roll forward the database only with a tested migration plan.
4. Rebuild caches and restart queue workers.
5. Bring the site up and verify checkout/order/payment flows.

Do not roll back a production payment callback blindly; reconcile payments after any restore.
