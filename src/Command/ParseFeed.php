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

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:parse-feed')]
class ParseFeed extends Command
{
    private $container;
    private $articlesRepository;
    private $outputInterface;
    private $messageGenerator;

    public function __construct(ContainerInterface $container, ArticlesRepository $articlesRepository)
    {
        parent::__construct();
        $this->container = $container;
        $this->articlesRepository = $articlesRepository;
    }

    public function runImport($msg): int
    {
        $this->outputInterface->writeln([$msg]);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {   
        $manager = $this->container->get('doctrine')->getManager();

        $connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'], 
            $_ENV['RABBITMQ_PORT'], 
            $_ENV['RABBITMQ_USER'], 
            $_ENV['RABBITMQ_PASSWORD']
        );
        $channel = $connection->channel();

        // declare perisistant queue
        $channel->queue_declare('parse_queue', false, true, false, false);

        $channel->basic_consume('parse_queue', '', false, false, false, false, function($msg) use ($output, $manager) {
            $stream = json_decode($msg->body, true);
            $client = new Client([
                'base_uri' => 'https://www.wired.com'
            ]);
            
            try {
                $response = $client->request('GET', $stream["url"]);
                $body = $response->getBody()->getContents();
            
                // remove the media: substring to comform to array key naming rules
                $body = str_replace("media:", "", $body);
            
                $feed = $array = json_decode(json_encode((array)simplexml_load_string($body)),true);
                $feed = $feed["channel"]["item"];
            
                foreach ($feed as $item) {
    
                    $article = $this->articlesRepository->findOneBy([
                        'title' => $item["title"]
                    ]);;
                    
                    // create the article if it doesn't exist in the db
                    if(!$article){
                        $article = new Articles();
                        $article->setTitle($item["title"]);
                        $article->setShortDescription($item["description"]);
                        $article->setPicture($item["thumbnail"]['@attributes']['url']);
                
                        $date_added = DateTime::createFromFormat("D, d M Y H:i:s T", $item['pubDate']);
                        $article->setDateAdded($date_added);
                
                        $manager->persist($article);
                        $manager->flush();
                    }   
            
                }
    
                $output->writeln(["Fetched from ".$stream['category'].' stream']);
                
                // acknowledge feed parsed
                $msg->ack();
                
            } catch (ClientException $e) {
                $output->writeln(['Unable to parse '.$stream['category'].' stream']);
            }
            catch (ConnectException $e) {
                $output->writeln(["Connection error, unable to retrieve articles ".$stream['category'].' stream']);
            };
        });

        while ($channel->is_open()) {
            $channel->wait();
        }
        
        return Command::SUCCESS;
    }
}