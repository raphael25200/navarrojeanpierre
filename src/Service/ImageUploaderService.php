<?php

namespace App\Service;

use App\Entity\Tableau;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageUploaderService
{
    private Filesystem $filesystem;
    private string $sourceDir;
    private string $thumbnailDir;
    private string $previewDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->filesystem = new Filesystem();
        $projectDir = $params->get('kernel.project_dir');
        $this->sourceDir = $projectDir . '/public/images/images_sources/';
        $this->thumbnailDir = $projectDir . '/public/images/thumbnail/';
        $this->previewDir = $projectDir . '/public/images/preview/';
    }

    public function uploadImage(Tableau $tableau): void
    {
        ini_set('memory_limit', '256M');

        if (!$tableau->getImage()) {
            return;
        }

        $fileName = $tableau->getImage();

        // Vérifier et créer les dossiers si nécessaire
        $this->ensureDirectoriesExist();

        // Chemins complets
        $sourcePath = $this->sourceDir . $fileName;
        $thumbnailPath = $this->thumbnailDir . 'thumb_' . $fileName;
        $previewPath = $this->previewDir . 'prev_' . $fileName;

        if (!$this->filesystem->exists($sourcePath)) {
            return;
        }

        // Suppression ancienne miniature
        $oldThumbnail = $tableau->getThumbnail();
        if ($oldThumbnail && $this->filesystem->exists($this->thumbnailDir . $oldThumbnail)) {
            $this->filesystem->remove($this->thumbnailDir . $oldThumbnail);
        }

        // Suppression ancienne preview
        $oldPreview = $tableau->getPreview();
        if ($oldPreview && $this->filesystem->exists($this->previewDir . $oldPreview)) {
            $this->filesystem->remove($this->previewDir . $oldPreview);
        }

        $imagine = new Imagine();
        $image = $imagine->open($sourcePath);

        // Recompression directe
        $image->save($sourcePath, [
            'jpeg_quality' => 75, // réduit fortement la taille
            'png_compression_level' => 9, // si c’est un PNG
        ]);

        // Miniature (150x150)
        $thumbnailImage = $image->thumbnail(new Box(50, 50), ImageInterface::THUMBNAIL_OUTBOUND);
        $thumbnailImage->save($thumbnailPath, ['jpeg_quality' => 80]);
        $tableau->setThumbnail('thumb_' . $fileName);

        // Prévisualisation (600px de large)
        $size = $image->getSize();
        $ratio = $size->getHeight() / $size->getWidth();
        $previewWidth = 600;
        $previewHeight = (int) ($previewWidth * $ratio);

        $previewImage = $image->resize(new Box($previewWidth, $previewHeight));
        $previewImage->save($previewPath, ['jpeg_quality' => 80]);
        $tableau->setPreview('prev_' . $fileName);
    }

    private function ensureDirectoriesExist(): void
    {
        $directories = [$this->thumbnailDir, $this->previewDir];

        foreach ($directories as $dir) {
            if (!$this->filesystem->exists($dir)) {
                $this->filesystem->mkdir($dir, 0777);
            }
        }
    }
}
