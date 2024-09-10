<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;

class OrderHistoryService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @return array
     */
    public function getAllPassedOrders(): array
    {
        $orders = [];
        foreach ($this->orderRepository->findAll() as $order) {
            $recapOrder = $this->getRecapForOrder($order);
            $orders[] = [
                'reference' => $order->getReference(),
                'createdAt' => $order->getCreatedAt(),
                'total' => $recapOrder['total'] + ($order->getTransporterPrice() / 100),
                'isPaid' => $order->isPaid(),
            ];
        }
        return $orders;
    }

    public function getOrderByReference(string $reference): ?Order
    {
        return $this->orderRepository->findOneBy(['reference' => $reference]);
    }


    public function getRecapForOrder(Order $order): array
    {
        $recapDetails = [];
        $totalPrice = 0;
        foreach ($order->getRecapDetails() as $recapDetail) {
            $recapDetails['cart'][] = [
                'quantity' => $recapDetail->getQuantity(),
                'product' => $this->productRepository->findOneBy(['title' => $recapDetail->getProduct()]),
            ];
            $totalPrice += $recapDetail->getTotalRecap();
        }
        $recapDetails['total'] = $totalPrice;
        return $recapDetails;
    }
}
