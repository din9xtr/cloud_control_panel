.PHONY: build up down install logs bash migrate key


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

key:
	@echo "Generating new APP_KEY..."
	@KEY=$$(docker compose exec -T app php -r "echo bin2hex(random_bytes(32));"); \
	if grep -q '^APP_KEY=' .env; then \
		sed -i "s/^APP_KEY=.*/APP_KEY=$$KEY/" .env; \
	else \
		echo "APP_KEY=$$KEY" >> .env; \
	fi; \
	echo "APP_KEY set to $$KEY"; \
	echo "Restarting app container to apply new key..."; \
	make down
	make up