# Simple Forum

Если запускаешь первый раз:

```bash
composer install
```

Создай `.env.local` и пропиши туда свою базу. Пример:

```bash
DATABASE_URL="postgresql://postgres:password@127.0.0.1:5432/forum?serverVersion=16&charset=utf8"
```

Если базы `forum` еще нет, создай ее:

```bash
createdb -h 127.0.0.1 -U postgres forum
```

Потом накати миграции:

```bash
php bin/console doctrine:migrations:migrate
```

Запуск:

```bash
symfony server:start
```

Если нет Symfony:

```bash
php -S 127.0.0.1:8000 -t public
```

Первого админа можно выдать так:

```bash
php bin/console app:make-admin user@example.com
```

Картинки постов лежат в `public/uploads/post-images`, аватары в `public/uploads/avatars`.
