<?php
// src/Controller/AdvertController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Advert;
use App\Entity\Image;
use App\Entity\Application;
use App\Entity\Category;
use App\Entity\Skill;
use App\Entity\AdvertSkill;
use App\Repository\AdvertRepository;
use App\Form\AdvertType;
use App\Form\AdvertEditType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


/**
 * @Route("/advert")
 */
class AdvertController extends Controller
{
  /**
   * @Route("/{page}", name="oc_advert_index", requirements={"page" = "\d+"}, defaults={"page" = 1})
   */
  public function index($page)
  {
    // On ne sait pas combien de pages il y a
    // Mais on sait qu'une page doit être supérieure ou égale à 1
    if ($page < 1) {
      // On déclenche une exception NotFoundHttpException, cela va afficher
      // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
      throw $this->createNotFoundException("Page '".$page."' inexistante.");
    }

    // Ici, on récupérera la liste des annonces, puis on la passera au template

    $em = $this->getDoctrine()->getManager();

    $repositorie = $em->getRepository(Advert::class);

    $nbPerPage = 3;

    $listAdverts = $repositorie->getAdverts($page,$nbPerPage);

    // On calcule le nombre total de pages grâce au count($listAdverts) qui retourne le nombre total d'annonces
    $nbPages = ceil(count($listAdverts) / $nbPerPage);

    // Si la page n'existe pas, on retourne une 404
    if ($page > $nbPages) {
      throw $this->createNotFoundException("La page ".$page." n'existe pas.");
    }

    return $this->render('Advert/index.html.twig', array(
      'listAdverts' => $listAdverts,
      'nbPages'     => $nbPages,
      'page'        => $page 
    ));
  }

  /**
   *@Route("/view/{id}", name="oc_advert_view", requirements={"id" = "\d+"})
   */
  public function view($id)
  {
    $em = $this->getDoctrine()->getManager();

    $repositorie = $em->getRepository(Advert::class);
      
   // On récupère l'entité correspondante à l'id $id
   $advert = $repositorie->find($id);
   
   // $advert est donc une instance de App\Entity\Advert
   // ou null si l'id $id  n'existe pas, d'où ce if :
   if (!$advert) {
        throw $this->createNotFoundException(
            "L'annonce d'id ".$id." n'existe pas."
        );
    }

    // On récupère la liste des candidatures de cette annonce
    $listApplications = $em->getRepository(Application::class)
      ->findBy(array('advert' => $advert));


    // On récupère maintenant la liste des AdvertSkill
    $listAdvertSkills = $em
      ->getRepository(AdvertSkill::class)
      ->findBy(array('advert' => $advert));  

    return $this->render('Advert/view.html.twig', array(
      'advert' => $advert,
      'listApplications' => $listApplications,
      'listAdvertSkills' => $listAdvertSkills
    ));
  }
  
  /**
   * @Route("/add", name="oc_advert_add")
   * @Security("has_role('ROLE_AUTEUR')")
   */
  public function add(Request $request)
  {
    // Création de l'entité
    $advert = new Advert();

    $form = $this->createForm(AdvertType::class, $advert);

    $advert->setTitle('Recherche développeur Symfony.');
    $advert->setAuthor('Alexandre');
    $advert->setContent("Nous recherchons un développeur Symfony débutant sur Lyon. Blabla…");
    // On peut ne pas définir ni la date ni la publication,
    // car ces attributs sont définis automatiquement dans le constructeur
    // La gestion d'un formulaire est particulière, mais l'idée est la suivante :

    // Création de l'entité Image
    $image = new Image();
    $image->setUrl('http://sdz-upload.s3.amazonaws.com/prod/upload/job-de-reve.jpg');
    $image->setAlt('Job de rêve');

    // On lie l'image à l'annonce
    $advert->setImage($image);

    // Création d'une première candidature
    $application1 = new Application();
    $application1->setAuthor('Marine');
    $application1->setContent("J'ai toutes les qualités requises.");

    // Création d'une deuxième candidature par exemple
    $application2 = new Application();
    $application2->setAuthor('Pierre');
    $application2->setContent("Je suis très motivé.");

    // On lie les candidatures à l'annonce
    $application1->setAdvert($advert);
    $application2->setAdvert($advert);


    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();

    // On récupère toutes les compétences possibles
    $listSkills = $em->getRepository(Skill::class)->findAll();


    // Pour chaque compétence
    foreach ($listSkills as $skill) {
      // On crée une nouvelle « relation entre 1 annonce et 1 compétence »
      $advertSkill = new AdvertSkill();

      // On la lie à l'annonce, qui est ici toujours la même
      $advertSkill->setAdvert($advert);
      // On la lie à la compétence, qui change ici dans la boucle foreach
      $advertSkill->setSkill($skill);

      // Arbitrairement, on dit que chaque compétence est requise au niveau 'Expert'
      $advertSkill->setLevel('Expert');

      // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
      $em->persist($advertSkill);
    }

    // Doctrine ne connait pas encore l'entité $advert. Si vous n'avez pas défini la relation AdvertSkill
    // avec un cascade persist (ce qui est le cas si vous avez utilisé mon code), alors on doit persister $advert


    // Étape 1 : On « persiste » l'entité
    $em->persist($advert);

    // Étape 1 bis : si on n'avait pas défini le cascade={"persist"},
    // on devrait persister à la main l'entité $image
    // $em->persist($image);

    // Étape 1 ter : pour cette relation pas de cascade lorsqu'on persiste Advert, car la relation est
    // définie dans l'entité Application et non Advert. On doit donc tout persister à la main ici.
    $em->persist($application1);
    $em->persist($application2);

    // Étape 2 : On « flush » tout ce qui a été persisté avant
    $em->flush();


    // Si la requête est en POST, c'est que le visiteur a soumis le formulaire
    if ($request->isMethod('POST')) {
      // On fait le lien Requête <-> Formulaire
      // À partir de maintenant, la variable $advert contient les valeurs entrées dans le formulaire par le visiteur
      $form->handleRequest($request);

      // On vérifie que les valeurs entrées sont correctes
      // (Nous verrons la validation des objets en détail dans le prochain chapitre)
      if ($form->isValid()) {
        
        // On enregistre notre objet $advert dans la base de données, par exemple
        $em = $this->getDoctrine()->getManager();
        $em->persist($advert);
        $em->flush();

        $this->addFlash('notice', 'Annonce bien enregistrée.');

        // On redirige vers la page de visualisation de l'annonce nouvellement créée
        return $this->redirectToRoute('oc_advert_view', array('id' => $advert->getId()));
      }
    }

    // Si on n'est pas en POST, alors on affiche le formulaire

    // On récupère le service
    /*$antispam = $this->container->get('App\Services\Antispam\OCAntispam');

    // Je pars du principe que $text contient le texte d'un message quelconque
    $text = '...';
    if ($antispam->isSpam($text)) {
      throw new \Exception('Votre message a été détecté comme spam !');
    }*/
    
    // Ici le message n'est pas un spam

    // On passe la méthode createView() du formulaire à la vue
    // afin qu'elle puisse afficher le formulaire toute seule
    return $this->render('Advert/add.html.twig',array(
      'advert' => $advert,
      'form' => $form->createView()
    ));
  }

  /**
   * @Route("/edit/{id}", name="oc_advert_edit", requirements={"id" = "\d+"})
   */
  public function edit($id, Request $request)
  {
     // Ici, on récupérera l'annonce correspondante à $id
     $em = $this->getDoctrine()->getManager();
     $advert = $em->getRepository(Advert::class)->find($id); 
     $form = $this->createForm(AdvertEditType::class, $advert);     

    // Même mécanisme que pour l'ajout
    if ($request->isMethod('POST')) {

      $form->handleRequest($request);
      if ($form->isValid()) {

        $advert->updateDate();
        $em->persist($advert);
        $em->flush();

        $this->addFlash('notice', 'Annonce bien modifiée.');

        return $this->redirectToRoute('oc_advert_view', array('id' => $advert->getId()));
      }
      
    }
    
    // si l'id $id  n'existe pas, d'où ce if :
    if (!$advert) {
        throw $this->createNotFoundException(
            "L'annonce d'id ".$id." n'existe pas."
        );
    }

    // La méthode findAll retourne toutes les catégories de la base de données
    $listCategories = $em->getRepository(Category::class)->findAll();
   
 
    // On boucle sur les catégories pour les lier à l'annonce
    foreach ($listCategories as $category) {
      $advert->addCategory($category);
    }

    // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
    // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

    // Étape 2 : On déclenche l'enregistrement
    $em->flush();

    return $this->render('Advert/edit.html.twig', array(
      'advert' => $advert,
      'form' => $form->createView()
    ));
  }

  /**
   * @Route("/delete/{id}", name="oc_advert_delete", requirements={"id" = "\d+"})
   */
  public function delete(Request $request, $id)
  {
    $em = $this->getDoctrine()->getManager();

    // Ici, on récupérera l'annonce correspondant à $id
    $advert = $em->getRepository(Advert::class)->find($id);

    // si l'id $id  n'existe pas, d'où ce if :
    if (!$advert) {
        throw $this->createNotFoundException(
            "L'annonce d'id ".$id." n'existe pas."
        );
    }

    // On crée un formulaire vide, qui ne contiendra que le champ CSRF
    // Cela permet de protéger la suppression d'annonce contre cette faille
    $form = $this->get('form.factory')->create();

    if ($request->isMethod('POST')){

      if($form->isSubmitted() && $form->isValid()) {

        $em->remove($advert);
        $em->flush();
        $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

        return $this->redirectToRoute('oc_advert_index');        
      }

    }

    return $this->render('Advert/delete.html.twig', array(
      'advert' => $advert,
      'form'   => $form->createView()
    ));
  }


  public function menuAction()
  {
    // on  récupère les titres des annonces depuis la BDD !
    $em = $this->getDoctrine()->getManager();
    $limit=3;
    $listAdverts = $em->getRepository(Advert::class)->findBy(
      array(),                 // Pas de critère
      array('date' => 'desc'), // On trie par date décroissante
      $limit,                  // On sélectionne $limit annonces
      0                        // À partir du premier
    );

    return $this->render('Advert/menu.html.twig', array(
      // Tout l'intérêt est ici : le contrôleur passe
      // les variables nécessaires au template !
      'listAdverts' => $listAdverts
    ));
  }
}
?>
