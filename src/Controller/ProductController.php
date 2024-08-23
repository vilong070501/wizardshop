<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
    #[Route('/shop', name: 'shop_index')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products
        ]);
    }

    #[Route('/product/{id}', name: 'product_detail')]
    public function detailPage(ProductRepository $productRepository, int $id): Response
    {
        $product = $productRepository->find($id);
        return $this->render('product/detail.html.twig', [
            'product' => $product
        ]);
    }
}
