<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function upload(UploadedFile $file, string $targetDirectory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $file->guessExtension() ?: 'bin';
        $newFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

        $file->move($targetDirectory, $newFilename);

        return $newFilename;
    }

    public function delete(?string $filename, string $targetDirectory): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $targetDirectory.'/'.$filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}
