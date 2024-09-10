<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\RecapDetails;
use App\Entity\Transporter;
use App\Form\OrderType;
use App\Repository\TransporterRepository;
use App\Service\CartService;
use App\Service\OrderHistoryService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TransporterRepository $transporterRepository
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
        if (!$this->getUser()) {
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
                'context' => 'verify',
                'method' => $order->getMethod(),
                'recapCart' => $cartService->getTotal(),
                'transporter' => $transporter,
                'delivery' => $deliveryForOrder,
                'reference' => $reference,
                'isPaid' => $order->isPaid(),
            ]);
        }

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/order/history', name: 'order_history')]
    public function orderHistory(OrderHistoryService $service): Response
    {
        $orders = $service->getAllPassedOrders();
        return $this->render('order_history/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/order/recap/{reference}', name: 'order_recap')]
    public function orderRecap(string $reference, OrderHistoryService $service): Response
    {
        $order = $service->getOrderByReference($reference);
        if (!$order) {
            return $this->redirectToRoute('order_history');
        }

        $transporter = $this->transporterRepository->findOneBy(['title' => $order->getTransporterName()]);

        return $this->render('order/recap.html.twig', [
            'context' => 'history',
            'method' => $order->getMethod(),
            'recapCart' => $service->getRecapForOrder($order)['cart'],
            'transporter' => $transporter,
            'delivery' => $order->getDelivery(),
            'reference' => $reference,
            'isPaid' => $order->isPaid(),
        ]);
    }
}
