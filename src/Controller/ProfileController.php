<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Form\UpdatePasswordType;
use App\Service\MailerService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ProfileController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/profile', name: 'profile_index')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerService $mailerService,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        /** @var User $user */
        if (!$user = $this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $currentUsername = $user->getUsername();
        $currentEmail = $user->getEmail();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle password change

            // Handle username change
            if ($currentUsername !== $form->get('username')->getData()) {
                $user->setUsername($form->get('username')->getData());
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Your profile has been successfully updated !'
                );

                return $this->redirectToRoute('profile_index');
            }

            // Handle email change and verification
            if ($currentEmail !== $form->get('email')->getData()) {
                // Generate a new token for email verification
                $tokenRegistration = $tokenGenerator->generateToken();
                $user->setTokenRegistration($tokenRegistration);
                $user->setTokenRegistrationLifeTime((new DateTime('now'))->add(new DateInterval('P1D')));
                $user->setVerified(false);
                $entityManager->flush();

                // Send email verification
                $mailerService->sendMail(
                    $user->getEmail(),
                    'Confirmation of e-mail change',
                    'registration_confirmation.html.twig',
                    [
                        'user' => $user,
                        'token' => $tokenRegistration,
                        'lifeTimeToken' => $user->getTokenRegistrationLifeTime()->format("Y-m-d H:i:s")
                    ]
                );

                $this->addFlash(
                    'success',
                    'Your profile has been successfully updated !'
                );

                return $this->redirectToRoute('profile_index');
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Your profile has been successfully updated !'
            );

            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/change-password', name: 'profile_change_password')]
    public function updatePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
    ): Response {
        /** @var User $user */
        if (!$user = $this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $currentPasswordDb = $user->getPassword();

        $form = $this->createForm(UpdatePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword =  $userPasswordHasher->hashPassword(
                $user,
                $form->get('password')->getData()
            );

            $newPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('newPassword')->getData()
            );

            if ($currentPassword !== $currentPasswordDb) {
                $this->addFlash(
                    'error',
                    'Please provide a valid current password'
                );
                return $this->redirectToRoute('profile_change_password');
            }

            if ($currentPassword !== $newPassword) {
                $user->setPassword($newPassword);
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    'Your password has been successfully updated !'
                );
                return $this->redirectToRoute('profile_index');
            }
        }

        return $this->render('profile/update_password.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
