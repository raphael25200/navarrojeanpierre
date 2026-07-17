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
    private string $displayDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->filesystem = new Filesystem();
        $projectDir = $params->get('kernel.project_dir');
        $this->sourceDir = $projectDir . '/public/images/images_sources/';
        $this->thumbnailDir = $projectDir . '/public/images/thumbnail/';
        $this->previewDir = $projectDir . '/public/images/preview/';
        $this->displayDir = $projectDir . '/public/images/display/';
    }

    public function uploadImage(Tableau $tableau): void
    {
        ini_set('memory_limit', '512M');

        if (!$tableau->getImage()) {
            return;
        }

        $fileName = $tableau->getImage();

        $this->ensureDirectoriesExist();

        $sourcePath = $this->sourceDir . $fileName;

        if (!$this->filesystem->exists($sourcePath)) {
            return;
        }

        $oldThumbnail = $tableau->getThumbnail();
        if ($oldThumbnail && $this->filesystem->exists($this->thumbnailDir . $oldThumbnail)) {
            $this->filesystem->remove($this->thumbnailDir . $oldThumbnail);
        }

        $oldPreview = $tableau->getPreview();
        if ($oldPreview) {
            $this->removeWithWebp($this->previewDir, $oldPreview);
        }

        $oldDisplay = $tableau->getDisplay();
        if ($oldDisplay) {
            $this->removeWithWebp($this->displayDir, $oldDisplay);
        }

        $imagine = new Imagine();
        $image = $imagine->open($sourcePath);

        // Correction de l'orientation EXIF AVANT toute recompression
        // (une fois recompressé par GD, le tag EXIF est perdu définitivement)
        $image = $this->correctOrientation($image, $sourcePath);

        $image->save($sourcePath, [
            'jpeg_quality' => 75,
            'png_compression_level' => 9,
        ]);

        $this->generateVariants($tableau, $image, $fileName);
    }

    public function regenerateVariantsFromSource(Tableau $tableau): bool
    {
        ini_set('memory_limit', '512M');

        if (!$tableau->getImage()) {
            return false;
        }

        $fileName = $tableau->getImage();
        $sourcePath = $this->sourceDir . $fileName;

        if (!$this->filesystem->exists($sourcePath)) {
            return false;
        }

        $this->ensureDirectoriesExist();

        $oldPreview = $tableau->getPreview();
        if ($oldPreview) {
            $this->removeWithWebp($this->previewDir, $oldPreview);
        }
        $oldDisplay = $tableau->getDisplay();
        if ($oldDisplay) {
            $this->removeWithWebp($this->displayDir, $oldDisplay);
        }

        $imagine = new Imagine();
        $image = $imagine->open($sourcePath);

        $this->generateVariants($tableau, $image, $fileName);

        return true;
    }

    /**
     * Lit l'orientation EXIF (si présente) et tourne physiquement l'image en conséquence.
     * Sans effet si le fichier n'a pas de métadonnée EXIF ou si l'extension exif est absente.
     */
    private function correctOrientation(ImageInterface $image, string $path): ImageInterface
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        if (!in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg'])) {
            return $image;
        }

        $exif = @exif_read_data($path);

        if (!$exif || empty($exif['Orientation'])) {
            return $image;
        }

        switch ($exif['Orientation']) {
            case 3:
                return $image->rotate(180);
            case 6:
                return $image->rotate(90);
            case 8:
                return $image->rotate(-90);
            default:
                return $image;
        }
    }

    public function generateVariants(Tableau $tableau, ImageInterface $image, string $fileName): void
    {
        $size = $image->getSize();
        $ratio = $size->getHeight() / $size->getWidth();

        $thumbnailPath = $this->thumbnailDir . 'thumb_' . $fileName;
        $thumbnailImage = $image->copy()->thumbnail(new Box(50, 50), ImageInterface::THUMBNAIL_OUTBOUND);
        $thumbnailImage->save($thumbnailPath, ['jpeg_quality' => 80]);
        $tableau->setThumbnail('thumb_' . $fileName);

        $previewWidth = 600;
        $previewHeight = (int) ($previewWidth * $ratio);
        $previewFileName = 'prev_' . $fileName;
        $previewPath = $this->previewDir . $previewFileName;

        $previewImage = $image->copy()->resize(new Box($previewWidth, $previewHeight));
        $previewImage->save($previewPath, ['jpeg_quality' => 80]);
        $this->saveAsWebp($previewImage, $this->previewDir . $this->toWebp($previewFileName));
        $tableau->setPreview($previewFileName);

        $displayWidth = 2400;
        $displayHeight = (int) ($displayWidth * $ratio);
        $displayFileName = 'disp_' . $fileName;
        $displayPath = $this->displayDir . $displayFileName;

        $displayImage = $image->copy()->resize(new Box($displayWidth, $displayHeight));
        $displayImage->save($displayPath, ['jpeg_quality' => 90]);
        $this->saveAsWebp($displayImage, $this->displayDir . $this->toWebp($displayFileName));
        $tableau->setDisplay($displayFileName);
    }

    private function saveAsWebp(ImageInterface $image, string $webpPath): void
    {
        $image->save($webpPath, ['webp_quality' => 88]);
    }

    private function toWebp(string $fileName): string
    {
        return preg_replace('/\.(jpe?g|png)$/i', '.webp', $fileName);
    }

    private function removeWithWebp(string $dir, string $fileName): void
    {
        if ($this->filesystem->exists($dir . $fileName)) {
            $this->filesystem->remove($dir . $fileName);
        }
        $webpFile = $dir . $this->toWebp($fileName);
        if ($this->filesystem->exists($webpFile)) {
            $this->filesystem->remove($webpFile);
        }
    }

    private function ensureDirectoriesExist(): void
    {
        $directories = [$this->thumbnailDir, $this->previewDir, $this->displayDir];

        foreach ($directories as $dir) {
            if (!$this->filesystem->exists($dir)) {
                $this->filesystem->mkdir($dir, 0777);
            }
        }
    }
}
