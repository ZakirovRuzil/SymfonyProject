<?php

namespace App\Controller\Admin;

use App\Entity\Section;
use App\Entity\User;
use App\Form\SectionType;
use App\Repository\SectionRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sections')]
#[IsGranted(User::ROLE_ADMIN)]
class SectionAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_section_index', methods: ['GET'])]
    public function index(SectionRepository $sectionRepository): Response
    {
        return $this->render('admin/section/index.html.twig', [
            'sections' => $sectionRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/new', name: 'app_admin_section_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $section = new Section();
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyModeratorRole($section);

            $entityManager->persist($section);
            $entityManager->flush();

            $this->addFlash('success', 'Раздел создан.');

            return $this->redirectToRoute('app_admin_section_index');
        }

        return $this->render('admin/section/form.html.twig', [
            'pageTitle' => 'Новый раздел',
            'sectionForm' => $form->createView(),
            'section' => $section,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_section_edit', methods: ['GET', 'POST'])]
    public function edit(Section $section, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyModeratorRole($section);
            $entityManager->flush();

            $this->addFlash('success', 'Раздел обновлен.');

            return $this->redirectToRoute('app_admin_section_index');
        }

        return $this->render('admin/section/form.html.twig', [
            'pageTitle' => 'Редактирование раздела',
            'sectionForm' => $form->createView(),
            'section' => $section,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_section_delete', methods: ['POST'])]
    public function delete(
        Section $section,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
    ): Response {
        if ($this->isCsrfTokenValid('delete_section_'.$section->getId(), (string) $request->request->get('_token'))) {
            foreach ($section->getPosts() as $post) {
                foreach ($post->getImages() as $image) {
                    $fileUploader->delete($image->getFilename(), $this->getParameter('post_images_directory'));
                }
            }

            $entityManager->remove($section);
            $entityManager->flush();

            $this->addFlash('success', 'Раздел удален.');
        }

        return $this->redirectToRoute('app_admin_section_index');
    }

    private function applyModeratorRole(Section $section): void
    {
        foreach ($section->getModerators() as $moderator) {
            $moderator->addRole(User::ROLE_MODERATOR);
        }
    }
}
