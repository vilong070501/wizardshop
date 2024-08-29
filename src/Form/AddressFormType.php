<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', HiddenType::class, [
                'label' => 'Title'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'First Name'
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Last Name'
            ])
            ->add('company', TextType::class, [
                'label' => 'Company',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Address',
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Postal Code'
            ])
            ->add('city', TextType::class, [
                'label' => 'City'
            ])
            ->add('country', CountryType::class, [
                'label' => 'Country'
            ])
            ->add('phone', TextType::class, [
                'label' => 'Phone'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
