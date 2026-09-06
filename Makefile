# Raccourcis. Sous Windows sans make, utilisez les scripts composer equivalents :
#   composer test / composer test:domain / composer deptrac

.PHONY: install test test-domain test-usecase deptrac clean

install:
	composer install

test:
	vendor/bin/phpunit

test-domain:
	vendor/bin/phpunit --testsuite domain

test-usecase:
	vendor/bin/phpunit --testsuite usecase

deptrac:
	vendor/bin/deptrac analyse --config-file=deptrac.yaml

clean:
	rm -rf .phpunit.cache deptrac.cache
