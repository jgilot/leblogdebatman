<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{

    /**
     * Stockage du service d'necodage des mots de passe de symfony
     */
    private $encoder;
    private $slugger;

    /**
     * On utilise le constructeur pour demander à symfony de recupérer le service d'encodage des mdp pour ensuite le stocker dans  $this->encoder
     */
    public function __construct(UserPasswordHasherInterface $encoder, SluggerInterface $slugger){
        $this->encoder = $encoder;
        $this->slugger = $slugger;

    }

    public function load(ObjectManager $manager): void
    {

        // Instanciation du faker en langue française
        $faker = Faker\Factory::create('fr_FR');



        // Création du compte admin de batman
        $admin = new User();

        $admin
            ->setEmail('admin@a.a')
            ->setRegistationDate( $faker->dateTimeBetween('-1 year', 'now') )
            ->setPseudonym('Batman')
            ->setRoles( ["ROLE_ADMIN"] )
            ->setPassword(
                $this->encoder->hashPassword($admin, 'Azerty7/')
            )
        ;

        $manager->persist($admin);

        for ($i = 0; $i < 10; $i++ ){

            $user = new User();

            $user
                ->setEmail($faker->email)
                ->setRegistationDate($faker->dateTimeBetween('-1 year', 'now') )
                ->setPseudonym($faker->userName)
                ->setPassword(
                    $this->encoder->hashPassword($admin, 'Azerty7/')
                )
            ;

            $manager->persist($user);

        }

        // Création de 200 articles (avec une boucle)
        for ($i = 0; $i < 200; $i++){
            $article = new Article();

            $article
                ->setTitle( $faker->sentence(10) )
                ->setContent( $faker->paragraph(15) )
                ->setPublicationDate($faker->dateTimeBetween('-1 year', 'now'))
                ->setAuthor($admin)
                ->setSlug($this->slugger->slug( $article->getTitle() )->lower())
            ;
            $manager->persist($article);
        }



        $manager->flush();
    }
}
