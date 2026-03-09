CONTAINER_PHP=referral-php
USER_ID=$(shell id -u)

.PHONY: help ps build start stop restart init fresh exec artisan composer

help: ## Print this help message
	@echo -e "\nUsage:\n  make \033[36m<target>\033[0m\n"
	@echo -e "Targets:"
	@grep -E '^[a-zA-Z_-]+:.*?##' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?##"} { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 }'

ps: ## Show containers.
	@docker compose ps

init:  ## Build & create all container
	make build
	make start

build: ## Build all containers
	@docker compose build

start: ## Start all containers
	@docker compose up -d

stop: ## Stop all containers
	@docker compose stop

restart: ## Restart all containers
	make stop 
	make start 

fresh:  ## Destroy & recreate all container
	make stop
	make build
	make start

exec: ## Enter the container's shell
	docker exec -it -u ${USER_ID} ${CONTAINER_PHP} bash

artisan: ## Run the php artisan command
	docker exec -u ${USER_ID} ${CONTAINER_PHP} php artisan $(filter-out $@,$(MAKECMDGOALS))

composer: ## Run the composer command
	docker exec -u ${USER_ID} ${CONTAINER_PHP} composer $(filter-out $@,$(MAKECMDGOALS))

%: ## Allow arguments
	@:
