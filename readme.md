# News Parser

A web app for parsing RSS news feeds


Getting started
------------
1. create database
```php bin/console doctrine:database:create```

2. create database tables
```php bin/console doctrine:schema:update```

3. create users
```php bin/console app:create-user <username> <password> <role>```

4. Start service containers
```docker-compose up -d```

5. Creating the cron job to parse articles

Run: ```bin/console cron:create```
Set the cron job name e.g. parse-rss
Set the command to:  ```app:start-parsing```
Set the crontab syntax to every 5 minutes: ```5 * * * *```
Set the cron job description

Start the cron job processing with: ```bin/console cron:start```

Parsing RSS feeds
------------
The RSS feed streams are fetched from [Wired.com](https://www.wired.com) and loaded into RabbitMQ by the ```src/Command/ParseFeed.php``` console command run every 5 minutes as a cron job. The ```src/Command/ParseFeed.php``` console command fetches the articles, parses them and loads them into our database. 

The cron job to start processing the articles is initiated by the  [Cron Bundle](https://github.com/Cron/Symfony-Bundle) library every 5 minutes and can be initiated manually from the CLI using:

```php bin/console app:start-parsing```
