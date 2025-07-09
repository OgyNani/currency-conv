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

%:
	@:
