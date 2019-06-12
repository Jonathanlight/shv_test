php bin/console doctrine:database:drop --env=test --if-exists --force
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:update --force --env=test --complete
php bin/console hautelook:fixtures:load --env=test --no-interaction

if [ -z "$1" ]
then
./bin/phpunit
else
./bin/phpunit $1
fi
