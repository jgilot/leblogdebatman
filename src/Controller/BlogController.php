<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CreateCommentFormType;
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
    public function publicationView(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        $newComment = new Comment();

        $form = $this->createForm(CreateCommentFormType::class, $newComment);

        $form->handleRequest($request);

        // Si le formulaire est envoyé et sans errerus
        if ($form->isSubmitted() && $form->isValid()){

            // Si l'utilisateur n'est pas connecté, appel direct de la vue en lui envoyant seulement l'article à afficher
            // On fait ca pour eviter que le traitement du formulaire ne se fasse alors que la personne n'est pas connectée
            if (!$this->getUser()){
                return $this->render('blog/publication_view.html.twig', [
                   'article' => $article,
                ]);
            }


            $newComment
                ->setPublicationDate(new \DateTime())
                ->setAuthor($this->getUser())
                ->setArticle( $article )
            ;

            $em = $doctrine->getManager();

            $em->persist($newComment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été publié avec succès !');

            // Réinitialisation des variables $form et $comment pour un nv formulaire vierge
            unset($comment);
            unset($form);

            $newComment = new Comment();
            $form = $this->createForm(CreateCommentFormType::class, $newComment);

        }

        return $this->render('blog/publication_view.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
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


    /**
     * Contrôleur de la page admin servant a supprimer un article via son id dans l'url
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */
    #[Route('/publication/suppression/{id}/', name: 'publication_delete', priority: 10)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationDelete(Article $article, Request $request, ManagerRegistry $doctrine): Response
    {

        $csrfToken = $request->query->get('csrf_token', '');

        if (!$this->isCsrfTokenValid('blog_publication_delete_' . $article->getId(), $csrfToken) ){

            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer');

        }else {



        // Suppression de l'article en bdd
        $em = $doctrine->getManager();

        $em->remove($article);

        $em->flush();

        // message flash de succès
        $this->addFlash('success', 'La publication a été supprimée avec succès !');


    }

        // redirection vers la page qui liste les articles
        return $this->redirectToRoute('blog_publication_list');

    }

    /**
     * Contrôleur de la page admin servant a modifier un article existant via son id dans l'url
     *
     * Accès réservé aux administrateurs (ROLE_ADMIN)
     */

    #[Route('/publication/modifier/{id}/', name: 'publication_edit', priority: 11)]
    #[IsGranted('ROLE_ADMIN')]
    public function publicationEdit(Article $article, Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger ) : Response{

        // Instanciation d'un nouveau formulaire basé sur $article qui contient déjà les données actuelles de l'article a modifier
        $form = $this->createForm(NewArticleFormType::class, $article);

        $form->handleRequest($request);

        //si le formulaire est envoyé et sans erreurs
        if ($form->isSubmitted() && $form->isValid()){

            //Sauvegarde des données modifiées en BDD
            $article->setSlug( $slugger->slug( $article->getTitle() )->lower() );
            $em = $doctrine->getManager();
            $em->flush();

            // Message flash de succès
            $this->addFlash('success', 'Publication modifiée avec succès !');

            // redirection vers l'rticle modifié
            return $this->redirectToRoute('blog_publication_view', [
                'id' => $article->getId(),
                'slug' => $article->getSlug(),
            ]);

        }

        return $this->render('blog/publication_edit.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * Controlleur de la page permettant aux admins de supprimer un commentaire
     *
     * Accès réservé aux adminnistarteurs de site
     */
    #[Route('/commentaire/suppression/{id}/', name: 'comment_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function commentDelete(Comment $comment, Request $request, ManagerRegistry $doctrine): Response
    {

        //Si le token n'est pas valide
        if (!$this->isCsrfTokenValid('blog_comment_delete' . $comment->getId(), $request->query->get('csrf_token'))){
            $this->addFlash('error', 'Token sécurité invalide, veuillez ré-essayer');
        }else {




        // Supression du commentaire en BDD
        $em = $doctrine->getManager();
        $em->remove($comment);
        $em->flush();

        // Redirection sur la page de l'article lié au commentaire que l'on vient de supprimer
        $this->addFlash('success', 'Le commentaire a été supprimé avec succès');

        }

        // Redirection sur la page de l'article lié au commentaire que l'on vient de supprimer
        return $this->redirectToRoute('blog_publication_view', [
            'id' => $comment->getArticle()->getId(),
            'slug' => $comment->getArticle()->getSlug(),
        ]);

    }

    #[Route('/recherche/', name: 'search')]
    public function search(ManagerRegistry $doctrine, Request $request, PaginatorInterface $paginator): Response
    {

        // Récupération de $_GET['page'], 1 si elle n'existe pas
        $requestedPage = $request->query->getInt('page', 1);

        // Vérification que le nombre est positif
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }

        // On récupère la recherche de l'utilisateur depuis l'URL ( $_GET['s'] )
        $search = $request->query->get('s', '');

        $em = $doctrine->getManager();

        // On récupère la recherche de l'utilisateur depuis l'url
        $query = $em
            ->createQuery('SELECT a FROM App\Entity\Article a WHERE a.title LIKE :search OR a.content LIKE :search ORDER BY a.publicationDate DESC')
            ->setParameters([
                'search' => '%' . $search . '%'
            ])
        ;

        $articles = $paginator->paginate(
            $query,     // Requête créée juste avant
            $requestedPage,     // Page qu'on souhaite voir
            10,     // Nombre d'article à afficher par page
        );

        return $this->render('blog/list_search.html.twig', [
            'articles' => $articles,
        ]);
    }


}
