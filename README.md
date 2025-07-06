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

1. **Spin up DB/Redis for local dev:**
   ```sh
   docker-compose -f docker-compose.db-redis.yml up -d
   ```
2. **Edit `.env` to use `localhost` for DB/Redis.**
3. **Start the app stack:**
   ```sh
   make run
   ```
4. **Install Composer dependencies (if needed):**
   ```sh
   make install
   ```
5. **Access the API:**
   - Symfony: [http://localhost:18080](http://localhost:18080)
   - PostgreSQL: `localhost:5432` (user/pass/db: symfony)
   - Redis: `localhost:6379`

## Scaling
- Run as many app containers as needed (on any host), all pointing to the same DB/Redis.
- DB and Redis are singletons, shared by all app instances.

---
For questions or improvements, open an issue or contact the maintainer.

## Makefile Commands

- `make run`      — Build and start all containers in the background
- `make stop`     — Stop and remove all containers
- `make logs`     — Tail logs from all containers
- `make install`  — Install Composer dependencies inside the app container
- `make migrate`  — Run Symfony Doctrine migrations
- `make bash`     — Open a bash shell in the app container
- `make psql`     — Open a psql shell in the db container (user/db: symfony)
- `make redis-cli` — Open a Redis CLI shell in the redis container
- `make fetch-currencies` — Fetch currencies from the API

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

---

For questions or improvements, open an issue or contact the maintainer.
