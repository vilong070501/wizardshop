<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\RecapDetails;
use App\Entity\Transporter;
use App\Form\OrderType;
use App\Service\CartService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/order/create', name: 'order_create')]
    public function index(CartService $cartService): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser()
        ]);

        return $this->render('order/index.html.twig', [
            'form' => $form->createView(),
            'recapCart' => $cartService->getTotal()
        ]);
    }

    #[Route('/order/verify', name: 'order_prepare', methods: ['POST'])]
    public function prepareOrder(Request $request, CartService $cartService): Response
    {
        if (!$this->getUser())
        {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(OrderType::class, null, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $datetime = new DateTime('now');
            /** @var Transporter $transporter */
            $transporter = $form->get('transporter')->getData();
            /** @var Address $delivery */
            $delivery = $form->get('addresses')->getData();
            $deliveryForOrder = $delivery->getFirstname() . ' ' . $delivery->getLastname();
            $deliveryForOrder .= '</br>' . $delivery->getPhone();
            if ($delivery->getCompany()) {
                $deliveryForOrder .= ' - ' . $delivery->getCompany();
            }
            $deliveryForOrder .= '</br>' . $delivery->getAddress();
            $deliveryForOrder .= '</br>' . $delivery->getZipCode() . ' - ' . $delivery->getCity();
            $deliveryForOrder .= '</br>' . $delivery->getCountry();

            $paymentMethod = $form->get('payment')->getData();

            $order = new Order();
            $reference = $datetime->format('dmY') . '-' . uniqid();
            $order->setUser($this->getUser())
                ->setCreatedAt($datetime)
                ->setTransporterName($transporter->getTitle())
                ->setTransporterPrice($transporter->getPrice())
                ->setPaid(false)
                ->setReference($reference)
                ->setDelivery($deliveryForOrder)
                ->setMethod($paymentMethod);

            $this->entityManager->persist($order);

            foreach ($cartService->getTotal() as $item) {
                $recapDetails = new RecapDetails();
                /** @var Product $product */
                $product = $item['product'];
                $recapDetails->setOrderProduct($order)
                    ->setProduct($product->getTitle())
                    ->setQuantity($item['quantity'])
                    ->setPrice($product->getPrice())
                    ->setTotalRecap($product->getPrice() * $item['quantity']);

                $this->entityManager->persist($recapDetails);
            }

            $this->entityManager->flush();

            return $this->render('order/recap.html.twig', [
                'method' => $order->getMethod(),
                'recapCart' => $cartService->getTotal(),
                'transporter' => $transporter,
                'delivery' => $deliveryForOrder,
                'reference' => $reference,
            ]);
        }

        return $this->redirectToRoute('cart_index');
    }
}
