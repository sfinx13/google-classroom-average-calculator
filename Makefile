.PHONY: help build run stop test ci

.DEFAULT_GOAL := help

help: ## Affiche la liste des commandes disponibles
	@echo "Affichage de l'aide..."
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'


build: ## Build le projet, installe les dépendances et lance les migrations
	@echo "Construction du projet..."
	docker compose up -d --build
	docker compose exec php composer install
	docker compose exec php php bin/console doctrine:database:create --if-not-exists
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

run: ## Démarre l'application avec FrankenPHP via Docker
	@echo "Démarrage de l'application avec FrankenPHP (Docker)..."
	docker compose up -d

down: ## Stoppe les conteneurs Docker
	@echo "Arrêt des conteneurs..."
	docker compose down

test: ## Lance les tests PHPUnit
	@echo "Exécution des tests PHPUnit..."
	vendor/bin/phpunit --display-phpunit-notices

ci:  ## Lance toutes les steps de la CI
	@echo "Lancement de la CI..."
	composer ci

