<?php

namespace App\Form;

use App\Form\Model\WikiData;
use App\Form\Type\MarkdownType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WikiType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('title', TextType::class, [
                'label' => 'label.title',
            ])
            ->add('body', MarkdownType::class, [
                'label' => 'label.body',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'action.save',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => WikiData::class,
        ]);
    }
}
