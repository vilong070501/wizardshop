<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\MailerService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerService $mailerService,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate token
            $tokenRegistration = $tokenGenerator->generateToken();

            // Encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Set user token
            $user->setTokenRegistration($tokenRegistration);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Your account has been created. Please verify your email to activate your account.'
            );
            $mailerService->sendMail(
                $user->getEmail(),
                'Confirmation of your user account',
                'registration_confirmation.html.twig',
                [
                    'user' => $user,
                    'token' => $tokenRegistration,
                    'lifeTimeToken' => $user->getTokenRegistrationLifeTime()->format("Y-m-d H:i:s")
                ]
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/{token}/{id<\d+>}', name: 'account_verify', methods: ['GET'])]
    public function verifyAccount(string $token, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user->getTokenRegistration() !== $token) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        if ($user->getTokenRegistration() === null) {
            throw new AccessDeniedHttpException('You user account has already been confirmed');
        }

        if (new DateTime('now') > $user->getTokenRegistrationLifeTime()) {
            throw new AccessDeniedException('Your activation link has expired !');
        }

        $user->setVerified(true);
        $user->setTokenRegistration(null);
        $entityManager->flush();

        $this->addFlash('success', 'Your account has been verified.');

        return $this->redirectToRoute('app_login');
    }
}
