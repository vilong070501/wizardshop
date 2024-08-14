<?php

namespace App\DataFixtures;

use App\Entity\CategoryShop;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryShopFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            1 => [
                "name" => "Cloak",
                "slug" => "cloak"
            ],
            2 => [
                "name" => "Broomstick",
                "slug" => "broomstick"
            ],
            3 => [
                "name" => "Hat",
                "slug" => "hat"
            ],
            4 => [
                "name" => "Sword",
                "slug" => "sword"
            ],
            5 => [
                "name" => "Shirt",
                "slug" => "shirt"
            ],
            6 => [
                "name" => "Short",
                "slug" => "short"
            ],
            7 => [
                "name" => "Wand",
                "slug" => "wand"
            ]
        ];

        foreach ($categories as $index => $category) {
            $categoryShop = new CategoryShop();
            $categoryShop->setName($category["name"])
                         ->setSlug($category["slug"]);
            $manager->persist($categoryShop);
            $this->addReference('categoryShop_' . $index, $categoryShop);
        }

        $manager->flush();
    }
}
