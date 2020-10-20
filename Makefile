all: csfix test
	@echo "Done."

vendor: composer.json
	composer update
	touch vendor

.PHONY: csfix
csfix: vendor
	vendor/bin/php-cs-fixer fix --verbose

.PHONY: test
test: vendor
	php -d zend.assertions=1 vendor/bin/phpunit
