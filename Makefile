.PHONY: build up down install logs bash migrate


build:
	docker compose build --no-cache

up:
	docker compose up -d

down:
	docker compose down

install:
	docker compose run --rm \
		-v $(PWD):/var/www \
		app composer install

logs:
	docker compose logs -f

bash:
	docker compose exec app sh

migrate:
	docker compose exec app php /var/www/db/migrate.php