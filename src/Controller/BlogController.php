<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\NewArticleFormType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blog', name: 'blog_')]
class BlogController extends AbstractController
{

    /**
     * contrôleur de la page permettant de créer un nouvel article
     * Accès reservé aux administrateurs (ROLE_ADMIN)
     */

    #[Route('/nouvelle-publiction/', name: 'new_publication')]
    #[IsGranted('ROLE_ADMIN')]
    public function new_publication(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {

        $article = new Article();

        $form = $this->createForm(NewArticleFormType::class, $article);

        $form->handleRequest($request);

        // si le formulaire est envoyé et dans erreurs
        if($form->isSubmitted() && $form->isValid() ){

            $article
                ->setPublicationDate( new \DateTime() )
                ->setAuthor( $this->getUser() )
                ->setSlug( $slugger->slug( $article->getTitle() )->lower() )
            ;

            $em = $doctrine->getManager();
            $em->persist($article);
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Article publié avec succès !');
            // Redirection vers a page qui affiche l'article (en envoyant son id et son slug dans l'url)
            return $this->redirectToRoute('blog_publication_view', [
                'id' => $article->getId(),
                'slug' => $article->getSlug(),
            ]);
        }



        return $this->render('blog/new_publication.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Controleur de lpage permettant de voit un article en detail
     */
    #[Route('/publication/{id}/{slug}/', name: 'publication_view')]
    #[ParamConverter('article', options: [ 'mapping' => [ 'id' => 'id', 'slug' => 'slug' ] ])]
    public function publicationView(Article $article): Response
    {

        return $this->render('blog/publication_view.html.twig', [
            'article' => $article
        ]);

    }

    /**
     * Controleur de la page qui liste les articles
     */
    #[Route('/publication/liste/', name: 'publication_list')]
    public function publicationList(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {

        // Récupération de $_GET['page'], 1 si elle n'existe pas
        $requestedPage = $request->query->getInt('page', 1);

        if ($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        $em = $doctrine->getManager();

        $query = $em->createQuery('SELECT a from App\Entity\Article a ORDER BY a.publicationDate DESC');

        $articles = $paginator->paginate(
            $query, //Requête créée juste avant
            $requestedPage,  // Page qu'on souhaite voir
            10,  // Nomber d'article à afficher par page
        );


        return $this->render('blog/publication_list.html.twig', [
            'articles' => $articles,
        ]);

    }

}
