<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:create-user')]
class CreateUser extends Command
{

    public function __construct(ContainerInterface $container, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();
        $this->container = $container;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure()
    {
        $this->setName('app:create-user')
            ->setDescription('Creates a user account')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user.')
            ->addArgument('password', InputArgument::REQUIRED, "The user's password.")
            ->addArgument('role', InputArgument::REQUIRED, "The user's role. one of admin/moderator");
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        if (!in_array($role, array("admin", "moderator"))){
            $output->writeln('Invalid user role. Must be either admin or moderator');
            return Command::FAILURE;
        }

        $manager = $this->container->get('doctrine')->getManager();
        $user = new User();

        $user->setUsername($username);
        $user->setRoles([$role]);
        $user->setPassword($this->passwordEncoder->encodePassword($user, $password));

        $manager->persist($user);
        $manager->flush();

        $output->writeln('');
        $output->writeln(sprintf('<info>User '.$username.' created.</info>'));

        return Command::SUCCESS;
    }
}
?>