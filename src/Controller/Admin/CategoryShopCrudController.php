<?php

namespace App\Controller\Admin;

use App\Entity\CategoryShop;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryShopCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CategoryShop::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('name'),
        ];
    }
}
