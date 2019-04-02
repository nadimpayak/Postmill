<?php

namespace App\Form;

use App\Entity\User;
use App\Form\Model\UserData;
use App\Form\Type\ThemeSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserSettingsType extends AbstractType {
    /**
     * @var array
     */
    private $availableLocales;

    public function __construct(array $availableLocales) {
        $this->availableLocales = $availableLocales;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $localeChoices = [];
        $localeBundle = Intl::getLocaleBundle();

        foreach ($this->availableLocales as $locale) {
            $name = $localeBundle->getLocaleName($locale, $locale);

            $localeChoices[$name] = $locale;
        }

        \uksort($localeChoices, function ($a, $b) {
            [$a, $b] = \array_map(function ($key) {
                return \transliterator_transliterate(
                    'NFKD; Latin; Latin/US-ASCII; [:Nonspacing Mark:] Remove; Lower',
                    $key
                );
            }, [$a, $b]);

            return \strnatcasecmp($a, $b);
        });

        $builder
            ->add('locale', ChoiceType::class, [
                'choices' => $localeChoices,
                'choice_translation_domain' => false,
            ])
            ->add('front_page', ChoiceType::class, [
                'choices' => [
                    'label.default' => User::FRONT_DEFAULT,
                    'label.featured' => User::FRONT_FEATURED,
                    'label.subscribed' => User::FRONT_SUBSCRIBED,
                    'label.all' => User::FRONT_ALL,
                    'label.moderated' => User::FRONT_MODERATED,
                ],
                'label' => 'label.front_page',
            ])
            ->add('night_mode', CheckboxType::class, [
                'required' => false,
            ])
            ->add('show_custom_stylesheets', CheckboxType::class, [
                'label' => 'label.let_forums_override_preferred_theme',
                'required' => false,
            ])
            ->add('preferred_theme', ThemeSelectorType::class, [
                'label' => 'label.preferred_theme',
            ])
            ->add('openExternalLinksInNewTab', CheckboxType::class, [
                'required' => false,
                'label' => 'label.open_external_links_in_new_tab',
            ])
            ->add('autoFetchSubmissionTitles', CheckboxType::class, [
                'label' => 'label.auto_fetch_submission_titles',
                'required' => false,
            ])
            ->add('enablePostPreviews', CheckboxType::class, [
                'label' => 'label.show_post_previews',
                'required' => false,
            ])
            ->add('showThumbnails', CheckboxType::class, [
                'label' => 'label.show_thumbnails',
                'required' => false,
            ])
            ->add('notifyOnReply', CheckboxType::class, [
                'help' => 'help.notify_on_reply',
                'label' => 'label.notify_on_reply',
                'required' => false,
            ])
            ->add('notifyOnMentions', CheckboxType::class, [
                'help' => 'help.notify_on_mentions',
                'label' => 'label.notify_on_mentions',
                'required' => false,
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'label_format' => 'user_settings_form.%name%',
            'validation_groups' => ['settings'],
        ]);
    }
}
