<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostImage;
use App\Entity\Section;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\PostType;
use App\Security\PostVoter;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/sections/{id}/posts/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    #[IsGranted(User::ROLE_USER)]
    public function new(
        Section $section,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $post = new Post();
        $post->setSection($section);
        $post->setAuthor($user);

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addUploadedImages($post, $form->get('imageFiles')->getData(), $fileUploader);

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Пост опубликован.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/form.html.twig', [
            'pageTitle' => 'Новый пост',
            'postForm' => $form->createView(),
            'section' => $section,
            'post' => $post,
        ]);
    }

    #[Route('/posts/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        $commentFormView = null;

        if ($this->getUser() instanceof User) {
            $commentForm = $this->createForm(CommentType::class, new Comment(), [
                'action' => $this->generateUrl('app_comment_create', ['id' => $post->getId()]),
                'method' => 'POST',
            ]);

            $commentFormView = $commentForm->createView();
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'commentForm' => $commentFormView,
        ]);
    }

    #[Route('/posts/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    #[IsGranted(attribute: PostVoter::EDIT, subject: 'post')]
    public function edit(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
    ): Response {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTimeImmutable());
            $this->addUploadedImages($post, $form->get('imageFiles')->getData(), $fileUploader);

            $entityManager->flush();

            $this->addFlash('success', 'Пост обновлен.');

            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/form.html.twig', [
            'pageTitle' => 'Редактирование поста',
            'postForm' => $form->createView(),
            'section' => $post->getSection(),
            'post' => $post,
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    #[IsGranted(attribute: PostVoter::DELETE, subject: 'post')]
    public function delete(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
    ): Response {
        $sectionId = $post->getSection()?->getId();

        if ($this->isCsrfTokenValid('delete_post_'.$post->getId(), (string) $request->request->get('_token'))) {
            foreach ($post->getImages() as $image) {
                $fileUploader->delete($image->getFilename(), $this->getParameter('post_images_directory'));
            }

            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash('success', 'Пост удален.');
        }

        return $this->redirectToRoute('app_section_show', ['id' => $sectionId]);
    }

    #[Route('/posts/{post}/images/{image}/delete', name: 'app_post_image_delete', methods: ['POST'])]
    #[IsGranted(attribute: PostVoter::EDIT, subject: 'post')]
    public function deleteImage(
        Post $post,
        PostImage $image,
        Request $request,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
    ): Response {
        if ($image->getPost()?->getId() !== $post->getId()) {
            throw $this->createNotFoundException('Изображение не относится к этому посту.');
        }

        if ($this->isCsrfTokenValid('delete_post_image_'.$image->getId(), (string) $request->request->get('_token'))) {
            $fileUploader->delete($image->getFilename(), $this->getParameter('post_images_directory'));
            $post->removeImage($image);
            $post->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->remove($image);
            $entityManager->flush();

            $this->addFlash('success', 'Изображение удалено.');
        }

        return $this->redirectToRoute('app_post_edit', ['id' => $post->getId()]);
    }

    /**
     * @param iterable<UploadedFile> $uploadedFiles
     */
    private function addUploadedImages(Post $post, iterable $uploadedFiles, FileUploader $fileUploader): void
    {
        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $image = new PostImage();
            $image->setFilename($fileUploader->upload($uploadedFile, $this->getParameter('post_images_directory')));
            $image->setOriginalName($uploadedFile->getClientOriginalName());

            $post->addImage($image);
        }
    }
}
