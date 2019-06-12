#!/bin/bash

# install dependencies
composer install

# Create database + update schema
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Load fixtures
php bin/console hautelook:fixtures:load

# Import translations
php bin/console lexik:translations:import
php bin/console lexik:translations:import -p translations

# install CKEditor
php bin/console ckeditor:install
php bin/console assets:install public

# Update var directory permissions
chown -R www-data: var/
