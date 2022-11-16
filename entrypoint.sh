#!/usr/bin/env bash
 
composer install
bin/console doc:mig:mig --no-interaction
bin/console doc:fix:load --no-interaction

# start cron job processing
bin/console cron:start
 
exec "$@"