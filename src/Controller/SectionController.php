<?php

namespace App\Controller;

use App\Entity\Section;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SectionController extends AbstractController
{
    #[Route('/sections/{id}', name: 'app_section_show', methods: ['GET'])]
    public function show(Section $section, PostRepository $postRepository): Response
    {
        return $this->render('section/show.html.twig', [
            'section' => $section,
            'posts' => $postRepository->findBySectionOrdered($section),
        ]);
    }
}
