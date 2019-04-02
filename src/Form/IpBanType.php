<?php

namespace App\Form;

use App\Form\DataTransformer\UserTransformer;
use App\Form\Model\IpBanData;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class IpBanType extends AbstractType {
    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('ip', TextType::class, [
                'label' => 'label.ip_address',
            ])
            ->add('reason', TextType::class, [
                'label' => 'label.reason_for_banning',
            ])
            ->add('expiryDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'label' => 'Expires at (YYYY-MM-DD hh:mm)',
                'time_widget' => 'single_text',
                'required' => false,
            ])
            ->add('user', TextType::class, [
                'label' => 'label.user_associated_with_ip',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'action.ban',
            ]);

        $builder->get('user')->addModelTransformer(
            new UserTransformer($this->userRepository)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => IpBanData::class,
        ]);
    }
}
