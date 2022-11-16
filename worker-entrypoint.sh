#!/usr/bin/env bash
 
composer install
bin/console doc:mig:mig --no-interaction
bin/console doc:fix:load --no-interaction

# start rss parser
bin/console app:parse-feed
 
exec "$@"