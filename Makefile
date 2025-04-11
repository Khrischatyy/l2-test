.PHONY: build start stop restart logs test test-unit test-load api-logs api-logs-errors api-logs-full api-request-data clear-cache postman-setup load-test help

# Colors for pretty output
YELLOW := \033[1;33m
GREEN := \033[1;32m
RED := \033[1;31m
BLUE := \033[1;34m
NC := \033[0m # No Color

help: ## Show this help
	@echo "$(YELLOW)Available commands:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

build: ## Build and start containers, run migrations, create test user
	docker compose up -d --build
	@echo "$(YELLOW)Waiting for containers to be ready...$(NC)"
	sleep 5
	docker compose exec php composer install
	@echo "$(YELLOW)Setting up environment...$(NC)"
	docker compose exec php cp .env.dist .env
	@echo "$(YELLOW)Generating JWT keys...$(NC)"
	docker compose exec php rm -f config/jwt/*.pem
	docker compose exec php mkdir -p config/jwt
	docker compose exec php openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:123456
	docker compose exec php openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:123456
	docker compose exec php chmod 644 config/jwt/public.pem
	docker compose exec php chmod 600 config/jwt/private.pem
	docker compose exec php bin/console doctrine:database:create --if-not-exists
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
	-docker compose exec php bin/console app:create-user test@example.com password123 || true
	@echo "$(GREEN)Build complete! Use these credentials:$(NC)"
	@echo "Email: test@example.com"
	@echo "Password: password123"

start: ## Start containers
	docker compose up -d

stop: ## Stop containers
	docker compose down

clean:
	docker compose down -v
	rm -rf vendor var

restart: stop start ## Restart containers

logs: ## Show logs from all containers
	docker compose logs -f

api-logs: ## Show API logs from database (last 10 requests)
	@echo "$(YELLOW)Last 10 API requests:$(NC)"
	@docker compose exec database mysql -uapp -p'!ChangeMe!' app -e "\
		SELECT \
			id, \
			created_at, \
			CONCAT('$(BLUE)', method, '$(NC)') as method, \
			endpoint, \
			CASE \
				WHEN status_code >= 500 THEN CONCAT('$(RED)', status_code, '$(NC)') \
				WHEN status_code >= 400 THEN CONCAT('$(YELLOW)', status_code, '$(NC)') \
				ELSE CONCAT('$(GREEN)', status_code, '$(NC)') \
			END as status, \
			ROUND(processing_time * 1000, 2) as 'time_ms' \
		FROM api_logs \
		ORDER BY created_at DESC \
		LIMIT 10;"

api-logs-errors: ## Show API error logs (last 10 errors)
	@echo "$(YELLOW)Last 10 API errors:$(NC)"
	@docker compose exec database mysql -uapp -p'!ChangeMe!' app -e "\
		SELECT \
			id, \
			created_at, \
			CONCAT('$(BLUE)', method, '$(NC)') as method, \
			endpoint, \
			CONCAT('$(RED)', status_code, '$(NC)') as status, \
			SUBSTRING(response_data, 1, 100) as error_message \
		FROM api_logs \
		WHERE status_code >= 400 \
		ORDER BY created_at DESC \
		LIMIT 10;"

api-logs-full: ## Show full API log details by ID
	@echo "$(YELLOW)Enter log ID to view full details:$(NC)"
	@read log_id; \
	docker compose exec database mysql -uapp -p'!ChangeMe!' app -e "\
		SELECT \
			id, \
			created_at, \
			method, \
			endpoint, \
			status_code, \
			processing_time, \
			ip_address, \
			user_agent \
		FROM api_logs \
		WHERE id = $$log_id;"

api-request-data: ## Show detailed request/response data by ID
	@echo "$(YELLOW)Enter log ID to view request/response data:$(NC)"
	@read log_id; \
	echo "$(BLUE)Request Data:$(NC)"; \
	docker compose exec database mysql -uapp -p'!ChangeMe!' app -e "SELECT request_data FROM api_logs WHERE id = $$log_id;" | sed 's/\\\//\//g' | python3 -m json.tool || echo "Failed to format JSON"; \
	echo "\n$(GREEN)Response Data:$(NC)"; \
	docker compose exec database mysql -uapp -p'!ChangeMe!' app -e "SELECT response_data FROM api_logs WHERE id = $$log_id;" | sed 's/\\\//\//g' | python3 -m json.tool || echo "Failed to format JSON"

clear-cache: ## Clear application cache
	docker compose exec php bin/console cache:clear

postman-setup: ## Update Postman collection with correct environment
	@echo "$(YELLOW)Updating Postman collection...$(NC)"
	cp postman/Lead\ Management\ API.postman_collection.json postman/Lead\ Management\ API.postman_collection.backup.json
	@echo "$(GREEN)Backup of original collection created.$(NC)"

# Load Testing
load-test:
	@echo "$(YELLOW)Running load tests...$(NC)"
	@k6 run tests/k6/load-test.js

load-test-sustained:
	@echo "$(YELLOW)Running sustained load test (1000 requests/minute)...$(NC)"
	@k6 run --tag testType=sustained tests/k6/load-test.js -e SCENARIO=sustained_load

load-test-spike:
	@echo "$(YELLOW)Running spike test...$(NC)"
	@k6 run --tag testType=spike tests/k6/load-test.js -e SCENARIO=spike_test

test: test-unit test-load ## Run all tests (unit and load)

test-unit: ## Run unit tests
	@echo "$(YELLOW)Running unit tests...$(NC)"
	docker compose exec php vendor/bin/phpunit -c phpunit.xml.dist

test-load: ## Run load tests
	@echo "$(YELLOW)Running load tests...$(NC)"
	k6 run tests/k6/load-test.js
