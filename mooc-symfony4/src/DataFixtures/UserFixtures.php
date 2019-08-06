<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\User;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Les noms d'utilisateurs à créer
	    $listNames = array('Alexandre', 'Marine', 'Anna','Divine','Mbougeh','Arthur','Morel','Stella');

	    foreach ($listNames as $name) {
	      // On crée l'utilisateur
	      $user = new User;

	      // Le nom d'utilisateur et le mot de passe sont identiques pour l'instant
	      $user->setUsername($name);
	      $user->setPassword($name);

	      // On ne se sert pas du sel pour l'instant
	      $user->setSalt('');
	      // On définit uniquement le role ROLE_USER qui est le role de base
	      $user->setRoles(array('ROLE_AUTEUR'));

	      // On le persiste
	      $manager->persist($user);
	    }

	    // On déclenche l'enregistrement

	    $manager->flush();
    }
}
