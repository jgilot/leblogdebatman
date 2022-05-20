<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\EditPhotoFormType;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bridge\Twig\TokenParser\DumpTokenParser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('', name: 'main_')]
class MainController extends AbstractController
{

    /**
     * Controlleur de la page d'accueil
     */

    #[Route('/', name: 'home')]
    public function home(ManagerRegistry $doctrine): Response
    {

        $articleRepo = $doctrine->getRepository(Article::class);

        $articles = $articleRepo->findBy(
            [],  // WHERE du SELECT
            ['publicationDate' => 'DESC'],  // ORDER BY du SELECT
            $this->getParameter('app.article.last_article_number_on_home') // LIMIT du SELECT
        );

        dump( $articles );

        return $this->render('main/home.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/mon-profil/', name: 'profil')]
    #[IsGranted('ROLE_USER')]
    public function profil(): Response
    {
        return $this->render('main/profil.htm.twig');
    }

    /**
     * Controleur de la page de modification de la photo de profil
     *
     * accès reservé aux connectés (role_user)
     */
    #[Route('/editer-photo/', name: 'edit_photo')]
    #[IsGranted('ROLE_USER')]
    public function editPhoto(Request $request, ManagerRegistry $doctrine): Response
    {

        $form = $this->createForm(EditPhotoFormType::class);

        $form->handleRequest($request);

        // Si le formulaire a été envoyé et n'a pas d'erreur
        if ($form->isSubmitted() && $form->isValid()){

            // r"cupération de la photo envoyé
            $photo = $form->get('photo')->getData();

            // Si l'utilisateur a deja une photo de profil, on la suprrime
            if
            (
                $this->getUser()->getPhoto() != null &&
                file_exists($this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto() )
            ){

                unlink($this->getParameter('app.user.photo.directory') . $this->getUser()->getPhoto());
                
            }

            //Création d'un nouveau nom pour la photo

            do{

                $newFileName = md5( random_bytes(100) ) . '.' . $photo->guessExtension();


            }while( file_exists( $this->getParameter('app.user.photo.directory') . $newFileName ) );

            // Sauvegarde du nom de la photo (tant que le nom est deja pris on en regénère un)
            $this->getUser()->setPhoto($newFileName);

            $em = $doctrine->getManager();
            $em->flush();

            //déplacement physique de l'image dans le dossier paramétré dans services.yaml
            $photo->move(
                $this->getParameter('app.user.photo.directory'),
                $newFileName,
            );

            // Message flash de succès
            $this->addFlash('success', 'Photo de profil modifiée avec succès !');

            // Redirection vesr le profil
            return $this->redirectToRoute('main_profil');

        }

        return $this->render('main/edit_photo.html.twig', [
            'form' => $form->createView(),
        ]);

    }

}
