<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AddressFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AddressController extends AbstractController
{
    #[Route('/address', name: 'address_index')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        if (!$user = $this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $addresses = $user->getAddresses();
        $addressesForms = [];
        foreach ($addresses as $index => $address) {
            $form = $this->createForm(AddressFormType::class, $address, [
                'attr' => [
                    'id' => 'address_form_' . $index
                ]
            ]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($address);
                $entityManager->flush();
                $this->addFlash(
                    'success',
                    'Your address has been successfully updated !'
                );
                $this->redirectToRoute('address_index');
            }
            $addressesForms[$address->getId()] = $form->createView();
        }

        return $this->render('address/index.html.twig', [
            'addressesForms' => $addressesForms,
        ]);
    }
}
