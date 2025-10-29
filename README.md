# Balance Management API

#### Простое REST API приложение для управления балансом пользователей, разработанное на Laravel с использованием PostgreSQL.

# Требования
- [Docker](https://docs.docker.com/engine/install/)
- [Docker Compose](https://docs.docker.com/compose/install/#install-compose)

# Как запустить

### Первый запуск
- `git clone https://github.com/Kinasai/test-balance-management.git`
- `cd test-balance-management`
- `docker compose up -d --build`
- `docker compose exec php bash`
- `composer update`
- `cp .env.example .env && php artisan key:generate && php artisan storage:link`
- `php artisan migrate && php artisan db:seed`

### Последующий запуск
- `docker compose up -d`

# API Endpoints
### 1. Начисление средств

#### POST /api/deposit

- `{
"user_id": 1,
"amount": 500.00,
"comment": "Пополнение через карту"
}`

### 2. Списание средств

#### POST /api/withdraw

- `{
"user_id": 1,
"amount": 200.00,
"comment": "Покупка подписки"
}`

### 3. Перевод между пользователями

#### POST /api/transfer

- `{
"from_user_id": 1,
"to_user_id": 2,
"amount": 150.00,
"comment": "Перевод другу"
}`

### 4. Получение баланса

#### GET /api/balance/{user_id}

Response:

- `{
"user_id": 1,
"balance": 350.00
}`

Коды ответов

    200 - Успешный запрос
    400 / 422 - Ошибки валидации
    404 - Пользователь не найден
    409 - Конфликт (недостаточно средств)

Особенности реализации

    Все денежные операции выполняются в транзакциях
    Баланс не может быть отрицательным
    Автоматическое создание записи о балансе при первом пополнении
    Типы транзакций: deposit, withdraw, transfer_in, transfer_out

Структура базы данных

    users - таблица пользователей
    balances - таблица балансов пользователей
    transactions - таблица транзакций с историей операций
