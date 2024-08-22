<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart_index')]
    public function index(CartService $cartService): Response
    {
        $total = $cartService->getTotal();
        return $this->render('cart/index.html.twig', [
            'cart' => $total,
        ]);
    }

    #[Route('/cart/add/{id<\d+>}', name: 'cart_add')]
    public function addToCartAction(CartService $cartService, int $id): Response
    {
        $cartService->addToCart($id);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/decrease/{id<\d+>}', name: 'cart_decrease')]
    public function decreaseAction(CartService $cartService, int $id): RedirectResponse
    {
        $cartService->decrease($id);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/remove/{id<\d+>}', name: 'cart_remove')]
    public function removeFromCartAction(CartService $cartService, int $id): Response
    {
        $cartService->removeFromCart($id);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/cart/removeAll', name: 'cart_remove_all')]
    public function removeAllAction(CartService $cartService): Response
    {
        $cartService->removeCartAll();
        return $this->redirectToRoute('cart_index');
    }
}
