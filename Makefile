.PHONY: it
it: coding-standards tests static-analyse performance-tests mutation-tests

.PHONY: code-coverage
code-coverage: vendor ## Show test coverage rates (console)
	vendor/bin/phpunit --coverage-text

.PHONY: code-coverage-html
code-coverage-html: vendor ## Show test coverage rates (HTML)
	vendor/bin/phpunit --coverage-html ./build

.PHONY: fix-coding-standards
fix-coding-standards: vendor ## Fix all files using defined PHP-CS-FIXER rules
	vendor/bin/php-cs-fixer fix

.PHONY: coding-standards
coding-standards: vendor ## Check all files using defined PHP-CS-FIXER rules
	vendor/bin/php-cs-fixer fix --dry-run --stop-on-violation --using-cache=no

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
	vendor/bin/rector process --ansi --dry-run


.DEFAULT_GOAL := help
help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help