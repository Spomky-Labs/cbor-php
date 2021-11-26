.PHONY: code-coverage
code-coverage: vendor ## Show test coverage rates (console)
	vendor/bin/phpunit --coverage-text

.PHONY: coverage
coverage: vendor ## Show test coverage rates (HTML)
	vendor/bin/phpunit --coverage-html ./build

.PHONY: fix-cs
fix-cs: vendor ## Fix all files using defined PHP-CS-FIXER rules
	 vendor/bin/ecs --fix

.PHONY: coding-standards
coding-standards: vendor ## Check all files using ECS rules
	vendor/bin/ecs

.PHONY: tests
tests: vendor ## Run all tests
	vendor/bin/phpunit  --color

vendor: composer.json composer.lock
	composer validate
	composer install

.PHONY: tu
tu: vendor ## Run only unit tests
	vendor/bin/phpunit --color --group Unit

.PHONY: tf
tf: vendor ## Run only functional tests
	vendor/bin/phpunit --color --group Functional

.PHONY: static-analyse
static-analyse: vendor ## Run static analyse
	vendor/bin/phpstan analyse

.PHONY: rector
rector: vendor ## Check all files using Rector
	vendor/bin/rector process --ansi --dry-run  -v

.PHONY: mutation-tests
mutation-tests: vendor ## Mutation tests
	vendor/bin/infection -s --threads=$(nproc) --min-msi=60 --min-covered-msi=70

.PHONY: mutation-tests-github
mutation-tests-github: vendor ## Mutation tests (for Github only)
	vendor/bin/infection --logger-github --git-diff-filter=AM -s --threads=$(nproc) --min-msi=17 --min-covered-msi=31


.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help
