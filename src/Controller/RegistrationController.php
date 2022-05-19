<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Recaptcha\RecaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/creer-un-compte/', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, RecaptchaValidator $recaptcha): Response
    {

        if($this->getUser()){
            return $this->redirectToRoute('main_home');
        }

        /**
         * controlleur de la page d'inscription
         */
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // Récupération de $_POST['g-recaptcha-reponse'], sinon null
            $captchaResponse = $request->request->get('g-recaptcha-response', null);

            // On récupère l'adresse IP du client
            $ip = $request->server->get('REMOTE_ADDR');

            //SI le recaptcha n'est aos valide, on ajoute une erreur au formulaire
            if (!$recaptcha->verify($captchaResponse, $ip)){
                $form->addError( new FormError('Veuillez remplir le captcha de sécurité') );
            }



            if ($form->isValid()){

            // hydratation du mot de passe avec le hashage venant du formulaire
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // hydratation de la date d'inscription
            $user->setRegistationDate( new \DateTime() );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            // message flash de succès
            $this->addFlash('success', 'Votre compte a été créé avec succès !');

            return $this->redirectToRoute('app_login');

            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
