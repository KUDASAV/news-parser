# News Parser

A web app for parsing RSS news feeds


Getting started
------------
1. create the database
```php bin/console doctrine:database:create```

2. create the database tables
```php bin/console doctrine:schema:update```

3. create users
```php bin/console app:create-user <username> <password> <role>```

4. Start the service containers
```docker-compose up -d```

### Creating the cron job

Run: ```bin/console cron:create```

Set the cron job name e.g. parse-rss

Set the command to:  ```app:start-parsing```

Set the crontab syntax to every 5 minutes: ```5 * * * *```

Set the cron job description

Start the cron job processing with: ```bin/console cron:start```

Parsing RSS feeds
------------
The RSS feed urls from [Wired.com](https://www.wired.com) are loaded into RabbitMQ by the ```src/Command/StartParser.php``` class every 5 minutes.

The ```src/Command/ParseFeed.php``` class fetches the feed urls, parses them and then loads them into our database. 

The cron job to start processing the articles is initiated by the [Cron Bundle](https://github.com/Cron/Symfony-Bundle) library and can be initiated manually from the CLI using:

```php bin/console app:start-parsing```
