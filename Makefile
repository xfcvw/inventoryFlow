up:
	docker compose up --build

down:
	docker compose down

reset:
	docker compose down -v
	docker compose up --build

test:
	docker compose exec app php artisan test

logs:
	docker compose logs -f

shell:
	docker compose exec app bash
