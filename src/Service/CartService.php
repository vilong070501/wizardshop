<?php

namespace App\Service;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function addToCart(int $id): void
    {
        $session = $this->getSession();
        $cart = $session->get('cart', []);
        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }
        $session->set('cart', $cart);
    }

    public function decrease(int $id): void
    {
        $session = $this->getSession();
        $cart = $session->get('cart', []);
        if ($cart[$id] > 1) {
            $cart[$id]--;
        } else {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);
    }

    public function removeFromCart(int $id): void
    {
        $session = $this->getSession();
        $cart = $session->get('cart', []);
        unset($cart[$id]);
        $session->set('cart', $cart);
    }

    public function removeCartAll(): void
    {
        $this->getSession()->remove('cart');
    }

    public function getTotal(): array
    {
        $cart = $this->getSession()->get('cart', []);
        if (!$cart) {
            return [];
        }

        $cartData = [];
        foreach ($cart as $id => $quantity) {
            $product = $this->entityManager->getRepository(Product::class)->findOneBy(['id' => $id]);
            if (!$product) {
                // Remove the product and continue the loop
                $this->removeFromCart($id);
                continue;
            }
            $cartData[] = [
                'product' => $product,
                'quantity' => $quantity,
            ];
        }
        return $cartData;
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
