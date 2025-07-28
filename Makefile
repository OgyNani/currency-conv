run:
	docker-compose -f docker-compose.yml up -d --build

stop:
	docker-compose -f docker-compose.yml down

logs:
	docker-compose -f docker-compose.yml logs -f

install:
	docker-compose exec app composer install

migrate:
	docker-compose exec app php bin/console doctrine:migrations:migrate

bash:
	docker-compose exec app bash

psql:
	docker-compose exec db psql -U symfony -d symfony

redis-cli:
	docker-compose exec redis redis-cli

# Testing commands
# Test service classes
test-commands:
	cd backend && ./vendor/bin/phpunit --testsuite=Unit --filter="Service"

test-commands-docker:
	docker-compose exec app php bin/phpunit --testsuite=Unit --filter="Service"

# Test API integration
test-api:
	cd backend && ./vendor/bin/phpunit --testsuite=Integration --filter="Api"

# Test API integration in Docker
test-api-docker:
	docker-compose exec app php bin/phpunit --testsuite=Integration --filter="Api"

# Usage: make fetch-currencies EUR USD JPY
# This will fetch only the specified currencies
# If no currencies specified, fetches all currencies
fetch-currencies:
	docker-compose exec app php bin/console app:fetch-currencies $(filter-out $@,$(MAKECMDGOALS))

# Usage: make create-pair EUR USD
# This will create a currency pair from EUR to USD
create-pair:
	docker-compose exec app php bin/console app:create-pair $(word 2,$(MAKECMDGOALS)) $(word 3,$(MAKECMDGOALS))

# Usage: make list-pairs
# Optional: make list-pairs EUR (to filter by currency code)
list-pairs:
	docker-compose exec app php bin/console app:list-pairs $(if $(filter-out $@,$(MAKECMDGOALS)),--code=$(filter-out $@,$(MAKECMDGOALS)),)

# Usage: make list-currencies
# Optional: make list-currencies EUR (to filter by currency code)
list-currencies:
	docker-compose exec app php bin/console app:list-currencies $(if $(filter-out $@,$(MAKECMDGOALS)),--code=$(filter-out $@,$(MAKECMDGOALS)),)

# Usage: make pair-observe-status 1 true
# This will change the observe status of currency pair with ID 1 to true
pair-observe-status:
	docker-compose exec app php bin/console app:pair-observe-status $(word 2,$(MAKECMDGOALS)) $(word 3,$(MAKECMDGOALS))

# Usage: make fetch-exchange-rate 1
# This will fetch and store the exchange rate for currency pair with ID 1
fetch-exchange-rate:
	docker-compose exec app php bin/console app:fetch-exchange-rate $(word 2,$(MAKECMDGOALS))

# Usage: make get-pair-rate 1 (returns latest)
# Optional: make get-pair-rate 1 all (get all rates for this pair)
# Optional: make get-pair-rate 1 2025-07-10
# Optional: make get-pair-rate 1 2025-07-01 2025-07-10
# For timestamps, use underscore instead of space:
# Optional: make get-pair-rate 1 2025-07-01_10:00 2025-07-10_15:30
# Optional: make get-pair-rate 1 2025-07-01_10:00:00 2025-07-10_15:30:00
get-pair-rate:
	docker-compose exec app php bin/console app:get-pair-rate $(word 2,$(MAKECMDGOALS)) $(if $(word 3,$(MAKECMDGOALS)),"$(word 3,$(MAKECMDGOALS))",) $(if $(word 4,$(MAKECMDGOALS)),"$(word 4,$(MAKECMDGOALS))",)

# Usage: make auto-fetch-rates on [interval]
# Examples:
#   make auto-fetch-rates on 10    - Start worker with 10 second interval
#   make auto-fetch-rates on       - Start worker with default interval (60 seconds)
#   make auto-fetch-rates off      - Stop the running worker
# This command controls the exchange rate worker which automatically fetches
# rates for all currency pairs with observe=true flag
auto-fetch-rates:
	docker-compose exec app php bin/console app:worker exchange_rate $(word 2,$(MAKECMDGOALS)) $(if $(word 3,$(MAKECMDGOALS)),--interval=$(word 3,$(MAKECMDGOALS)),)

%:
	@:
