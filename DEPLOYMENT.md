# Database deployment over SSH

Upload the complete application changes, including **all files in `database/migrations`**, before running the commands below. Some new migration files may still be untracked locally; a Git deployment only receives files included in your commit.

From the Laravel project directory on the server:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
php artisan icons:cache
php artisan optimize
```

For the current frontend changes, also build the production assets with `npm ci` followed by `npm run build`, or upload a locally built `public/build` directory if the server has no Node.js.

Back up the existing database before migrating. Use `migrate`, not `migrate:fresh`, `migrate:refresh`, or a database drop: those reset existing data. These migrations preserve current accounts, posts, media references, and UUID links.

The migration chain includes:

- Posts, comments, and post media, including Cloudinary asset metadata.
- Post coordinates and community tag descriptions.
- Profile biography, profile photo, cover photo, and profile UUIDs.
- Follow relationships.
- Post UUIDs, archive timestamps, and soft deletion.
- A UUID repair backfill that fills only missing identifiers and preserves existing shared links.

The post `visibility` field is a string and already supports `public`, `followers`, and `private`; it does not need a new table or enum migration. Upload size and duration limits are application configuration, not database schema.

Keep the server's own `.env` values for database access, `APP_URL`, and Cloudinary. Set `POST_MEDIA_DISK=cloudinary` for new remote uploads. Do not upload your local `.env` over production settings.

If Laravel reports `Nothing to migrate` but a column is missing, inspect `migrate:status` and the server schema before proceeding. Do not reset the database or delete migration-history rows to force a rerun.
