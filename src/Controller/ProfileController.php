<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile_my', methods: ['GET'])]
    #[IsGranted(User::ROLE_USER)]
    public function myProfile(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
    }

    #[Route('/users/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('profile/show.html.twig', [
            'profileUser' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted(User::ROLE_USER)]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatarFile')->getData();

            if ($avatarFile !== null) {
                $fileUploader->delete($user->getAvatarFilename(), $this->getParameter('avatars_directory'));
                $user->setAvatarFilename($fileUploader->upload($avatarFile, $this->getParameter('avatars_directory')));
            }

            $entityManager->flush();

            $this->addFlash('success', 'Профиль обновлен.');

            return $this->redirectToRoute('app_profile_show', ['id' => $user->getId()]);
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
            'profileUser' => $user,
        ]);
    }
}
