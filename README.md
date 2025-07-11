# Currency Converter API (Symfony + Docker)

A production-ready, developer-friendly backend for tracking and querying currency exchange rates. Built with Symfony, PostgreSQL, Docker, and Nginx.

## Project Structure

```
currency-conv/
├── backend/                # Symfony app
├── docker/                 # Docker configs (nginx, php)
├── docker-compose.yml      # Orchestration
├── .env                    # Local port configuration
├── Makefile                # Common developer commands
└── README.md
```

### Example `.env` variables
```
DB_HOST=your-db-host-or-ip
DB_PORT=5432
DB_NAME=symfony
DB_USER=symfony
DB_PASSWORD=symfony
REDIS_HOST=your-redis-host-or-ip
REDIS_PORT=6379
NGINX_PORT=18080
```

## Quick Start (Local Dev)

1. **Clone the repository:**
   ```sh
   git clone https://github.com/OgyNani/currency-conv.git
   cd currency-conv
   ```

2. **Start the Docker containers:**
   ```sh
   make run
   ```

3. **Install dependencies (if needed):**
   ```sh
   make install
   ```

4. **Run database migrations:**
   ```sh
   make migrate
   ```

5. **Fetch currency data:**
   ```sh
   make fetch-currencies
   ```

---

## Makefile Commands

### Container Management
- `make run`      — Build and start all containers in the background
- `make stop`     — Stop and remove all containers
- `make logs`     — Tail logs from all containers
- `make install`  — Install Composer dependencies inside the app container
- `make migrate`  — Run Symfony Doctrine migrations

### Shell Access
- `make bash`     — Open a bash shell in the app container
- `make psql`     — Open a psql shell in the db container (user/db: symfony)
- `make redis-cli` — Open a Redis CLI shell in the redis container

### Currency Management
- `make fetch-currencies` — Fetch all currencies from the API
- `make fetch-currencies EUR USD JPY` — Fetch only specific currencies
- `make list-currencies` — List all available currencies
- `make list-currencies EU` — List currencies with codes starting with EU

### Currency Pair Management
- `make create-pair EUR USD` — Create a currency pair from EUR to USD
- `make list-pairs` — List all currency pairs
- `make list-pairs EUR` — List currency pairs involving EUR
- `make pair-observe-status 1 true` — Change observe status of pair with ID 1 to true
- `make pair-observe-status 1 false` — Change observe status of pair with ID 1 to false

### Exchange Rate Management
- `make fetch-exchange-rate 1` — Fetch and store current exchange rate for pair with ID 1
- `make get-pair-rate 1` — Get latest exchange rate for currency pair with ID 1
- `make get-pair-rate 1 all` — Get all historical rates for currency pair with ID 1
- `make get-pair-rate 1 "2025-07-10"` — Get rate for specific date
- `make get-pair-rate 1 "2025-07-01" "2025-07-10"` — Get rates between two dates
- `make get-pair-rate 1 2025-07-01_10:00 2025-07-10_15:30` — Get rates between two timestamps (use underscore for spaces)

## Redis Service
- **Host:** `localhost`
- **Port:** as set in `.env` (`REDIS_PORT`, default: 16379)
- **Purpose:** Used for caching the latest exchange rates for fast access.
- **Makefile command:** `make redis-cli` to access the Redis CLI inside the container.

## Database Connection (DBeaver, etc)
- **Host:** `localhost`
- **Port:** as set in `.env` (`DB_PORT`, default: 5432)
- **User:** `symfony`
- **Password:** `symfony`
- **Database:** `symfony`

## Currency API Integration

### Features
- Fetch and store currency data from FreeCurrencyAPI
- Store currency details including code, name, symbol, and type
- Filter currencies by specific codes (EUR, USD, etc.)

### Configuration
- API key is configured in the Docker environment
- Data is stored in the `currency_data` table
- Each currency includes: code, name, symbol, symbol_native, decimal_digits, rounding, name_plural and type

---