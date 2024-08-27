<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;
use PayPalHttp\IOException;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    #[Route('/order/create-session-stripe/{reference}', name: 'payment_stripe', methods: ['POST'])]
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

    public function getPaypalClient(): PayPalHttpClient
    {
        $clientId = "AYxIuXN8fC2eAzm9ZZL6KTAx69xb-K6wu2pqAqdqEYDtukyWM5qwmziIFppcpDWQB-Je74JPIRHtES4A";
        $clientSecret = "ELgdYRRL9jvwEWuxg5W-zBM0hFkeABMQ2oNBMY48qBk8pfV1SWBynvs4pmEn8bpgNB-MrmWbfZ1THcwx";
        $environment = new SandboxEnvironment($clientId, $clientSecret);
        return new PayPalHttpClient($environment);
    }

    /**
     * @throws HttpException
     * @throws IOException
     */
    #[Route('/order/create-session-paypal/{reference}', name: 'payment_paypal', methods: ['POST'])]
    public function paypalCheckout($reference) : RedirectResponse
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        if (!$order) {
            return $this->redirectToRoute('cart_index');
        }

        $items = [];
        $itemTotal = 0;

        foreach ($order->getRecapDetails()->getValues() as $item)
        {
            $items[] = [
                'name' => $item->getProduct(),
                'quantity' => $item->getQuantity(),
                'unit_amount' => [
                    'value' => $item->getPrice(),
                    'currency_code' => 'EUR',
                ],
            ];
            $itemTotal += $item->getPrice() * $item->getQuantity();
        }

        $total = $itemTotal + $order->getTransporterPrice();

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => $total,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => 'EUR',
                                'value' => $itemTotal,
                            ],
                            'shipping' => [
                                'currency_code' => 'EUR',
                                'value' => $order->getTransporterPrice(),
                            ]
                        ]
                    ],
                    'items' => $items
                ]
            ],
            'application_context' => [
                'return_url' => $this->urlGenerator->generate('payment_success', [
                    'reference' => $order->getReference()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'cancel_url' => $this->urlGenerator->generate('payment_error', [
                    'reference' => $order->getReference()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ]
        ];

        $client = $this->getPaypalClient();
        $response = $client->execute($request);

        if ($response->statusCode != 201) {
            return $this->redirectToRoute('cart_index');
        }

        $approvalLink = '';
        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                $approvalLink = $link->href;
                break;
            }
        }

        if (empty($approvalLink)) {
            return $this->redirectToRoute('cart_index');
        }

        $order->setPaypalOrderId($response->result->id);

        $this->entityManager->flush();

        return new RedirectResponse($approvalLink);
    }

    #[Route('/order/success/{reference}', name: 'payment_success')]
    public function paymentSuccess($reference, CartService $cartService) : Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('shop_index');
        }

        if (!$order->isPaid()) {
            $cartService->removeCartAll();
            $order->setPaid(true);
            $this->entityManager->flush();
        }

        return $this->render('order/success.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/order/error/{reference}', name: 'payment_error')]
    public function paymentError($reference, CartService $cartService) : Response
    {
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('shop_index');
        }

        return $this->render('order/error.html.twig', [
            'order' => $order
        ]);
    }
}
