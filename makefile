list:
	@echo "fix"
	@echo "phpcs"

phpcs:
	phpcs --standard=PSR2 --extensions=php --ignore=vendor/*,tests/*,cphalcon/* --warning-severity=0 ./

fix:
	phpcbf --standard=PSR2 --extensions=php --ignore=vendor/*,tests/*,cphalcon/* --warning-severity=0 ./

test-report:
	phpunit -v --coverage-html ./tests/report/ --colors

test:
	phpunit -v --colors