# Saas-Schools

Modern multi-tenant school management platform built with Laravel.

## Quick Start

1. Install dependencies:
   - `composer install`
2. Create environment file:
   - copy `.env.example` to `.env`
3. Generate app key:
   - `php artisan key:generate`
4. Configure database credentials in `.env`
5. Run migrations:
   - `php artisan migrate`
6. (Optional) Seed demo data:
   - `php artisan db:seed`
7. Start the app:
   - `php artisan serve`

## Environment Notes

- Set `APP_ENV=production` and `APP_DEBUG=false` in production.
- Ensure `APP_KEY` is set in your runtime environment.
- Clear cached config after env changes:
  - `php artisan optimize:clear`

## Queues and Scheduler

- Queue worker:
  - `php artisan queue:work`
- Scheduler:
  - `php artisan schedule:work` (or cron with `php artisan schedule:run`)

## Testing

- Run tests:
  - `php vendor/bin/phpunit`

## Security

If you discover a security issue, report it privately to the project maintainers.
