<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @throws ApiErrorException
     */
    #[Route('/order/create-session-stripe/{reference}', name: 'payment_stripe')]
    public function stripeCheckout($reference) : RedirectResponse
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        if (!$order) {
            return $this->redirectToRoute('cart_index');
        }

        $productStripe = [];
        foreach ($order->getRecapDetails()->getValues() as $item)
        {
            $product = $this->entityManager->getRepository(Product::class)->findOneBy(['title' => $item->getProduct()]);
            $productStripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product->getPrice() * 100,
                    'product_data' => [
                        'name' => $product->getTitle()
                    ]
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        /* Add shipping costs */
        $productStripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getTransporterPrice(),
                'product_data' => [
                    'name' => $order->getTransporterName()
                ]
            ],
            'quantity' => 1,
        ];

        $stripeSecretKey = "sk_test_51PsKm9C6T3RomIRGxXvr0Hi48rnDhEfmKMpGL9HMj3Qn71QdhLMqUdplMnzcjw8l0tJWwkRWKEE7dQRFIDrcSfbc002XSeWKyN";
        Stripe::setApiKey($stripeSecretKey);

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => $productStripe,
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('payment_success', [
                'reference' => $order->getReference()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urlGenerator->generate('payment_error', [
                'reference' => $order->getReference()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $order->setStripeSessionId($checkout_session->id);
        $this->entityManager->flush();

        return new RedirectResponse($checkout_session->url);
    }

    #[Route('/order/success/{reference}', name: 'payment_success')]
    public function paymentSuccess($reference, CartService $cartService) : Response
    {
        return $this->render('order/success.html.twig');
    }

    #[Route('/order/error/{reference}', name: 'payment_error')]
    public function paymentError($reference, CartService $cartService) : Response
    {
        return $this->render('order/error.html.twig');
    }
}
