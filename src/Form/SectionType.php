<?php

namespace App\Form;

use App\Entity\Section;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Название',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('moderators', EntityType::class, [
                'label' => 'Модераторы раздела',
                'class' => User::class,
                'choice_label' => static function (User $user): string {
                    return sprintf('%s (%s)', $user->getDisplayName(), $user->getEmail());
                },
                'multiple' => true,
                'required' => false,
                'query_builder' => static fn (UserRepository $repository) => $repository
                    ->createQueryBuilder('u')
                    ->orderBy('u.displayName', 'ASC')
                    ->addOrderBy('u.email', 'ASC'),
                'help' => 'При сохранении выбранным пользователям будет добавлена роль модератора.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Section::class,
        ]);
    }
}
