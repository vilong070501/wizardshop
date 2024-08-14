<?php

namespace App\DataFixtures;

use DateTime;
use Faker\Factory;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en');
        for ($i = 0; $i < 150; $i++) {
            $category = $this->getReference('categoryShop_' . $faker->numberBetween(1, 6));

            $product = new Product();
            $product->setTitle($faker->sentence())
                    ->setSlug($faker->slug())
                    ->setContent($faker->text())
                    ->setOnline(true)
                    ->setCreatedAt(new DateTime('now'))
                    ->setSubtitle($faker->text(155))
                    ->setAttachment(($faker->imageUrl(640, 480, 'harry_potter', true)))
                    ->setPrice($faker->randomFloat(2, max: 100))
                    ->setCategory($category);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
