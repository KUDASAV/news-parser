<?php 
// src/Command/CreateUserCommand.php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use App\Entity\Articles;
use App\Repository\ArticlesRepository;
use DateTime;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:start-parsing')]
class StartParser extends Command
{
    private $container;
    private $articlesRepository;

    public function __construct(ContainerInterface $container, ArticlesRepository $articlesRepository)
    {
        parent::__construct();
        $this->container = $container;
        $this->articlesRepository = $articlesRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'], 
            $_ENV['RABBITMQ_PORT'], 
            $_ENV['RABBITMQ_USER'], 
            $_ENV['RABBITMQ_PASSWORD']
        );

        $channel = $connection->channel();

        $channel->queue_declare('parse_queue', false, true, false, false);

        $news_streams = array(
            array('category'=>'bussiness', 'url'=>'/feed/category/business/latest/rss'),
            array('category'=>'ai', 'url'=>'/feed/tag/ai/latest/rss'),
            array('category'=>'culture', 'url'=>'/feed/category/culture/latest/rss'),
            array('category'=>'gear', 'url'=>'/feed/category/gear/latest/rss'),
            array('category'=>'ideas', 'url'=>'/feed/category/ideas/latest/rss'),
            array('category'=>'science', 'url'=>'/feed/category/science/latest/rss'),
            array('category'=>'security', 'url'=>'/feed/category/security/latest/rss'),
            array('category'=>'backchannel', 'url'=>'/feed/category/backchannel/latest/rss'),
            array('category'=>'guides', 'url'=>'/feed/tag/wired-guide/latest/rss')
        );
        
        foreach ($news_streams as $stream) {
            $output->writeln([json_encode($stream)]);
            $msg = new AMQPMessage(
                json_encode($stream),
                array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT) // make message persistant
            );
            $channel->basic_publish($msg, '', 'parse_queue');
        }

        $output->writeln(["sent"]);
        $channel->close();
        $connection->close();
        
        return Command::SUCCESS;
    }
}