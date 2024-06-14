# Payment Tests

This repository is purposed for tests only.

## Installation

- Clone this repository to your local directory: `git@github.com:yugo412/konnco-payment.git`.
- Change directory to application directory and copy default `.env.example` to `.env`.
- Please change database config. It recommended to use driver other than SQLite.
- Install required packages by running `composer install`.
- Generate a new key by running command `php artisan key:generate`.
- Migrate and seed database with command `php artisan migrate --seed`. Please be patient while running this command, it takes some times to finish the job.
- Lastly, install Laravel Passport by running command `php artisan passport:install`.

## Accessing the Endpoint
Before accessing any endpoint from payment service, we need to create Passport client. Please run this command to add a new one:
```bash
php artisan passport:client --password
```

Follow the wizard until a new client created successfully.

Now, you can use created client to access the payment endpoint with user credentials. For example, to fetch a token from user, we can pick a random email from database and use `password` as its password.

```bash
curl --location '{domain.com}/oauth/token' \
--header 'Accept: application/json' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'grant_type=password' \
--data-urlencode 'client_id={client_id}' \
--data-urlencode 'client_secret={client_secret}' \
--data-urlencode 'username={user_email}' \
--data-urlencode 'password=password' \
--data-urlencode 'scope='
```

After the token successfully created, it can be used to authorize any payment endpoints. For example make a payment:

```bash
curl --location '{domain.com}/api/v1/payment' \
--header 'Accept: application/json' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--header 'Authorization: Bearer {token}' \
--data-urlencode 'amount=12300'
```

Access transaction list (for authenticated user):

```bash
curl --location '{domain.com}/api/v1/payment' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer {token}'
```

And, payment summary:

```bash
curl --location '{domain.com}/api/v1/payment/summary' \
--header 'Accept: application/json' \
--header 'Authorization: Bearer {token}'
```

## Tests

```bash
php artisan test
```

> [!WARNING]  
> When running test, it forces to use SQLite instead of driver from configured file (`.env`). So, if you're using SQLite as default driver for database configuration, the data will be wiped out every test ran.
