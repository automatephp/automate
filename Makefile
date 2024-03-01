# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) run --rm php

# Executables
PHP = $(PHP_CONT) php

# Misc
.DEFAULT_GOAL = help
.PHONY        = help build up start down logs sh composer vendor sf cc

## —— Help 🐳 🎵 ———————————————————————————————————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

php: ## Connect to the PHP FPM container
	@$(PHP_CONT) sh

## —— Project 🐝 ———————————————————————————————————————————————————————————————
install: ## Install project
	@$(PHP_CONT) composer install

## —— CI ✨ ————————————————————————————————————————————————————————————————————
ci: test

test: ## Run tests
	$(PHP) vendor/bin/phpunit