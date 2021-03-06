<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Commands\UploadImage;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class ImageUploaderService implements ImageUploaderInterface
{
    /**
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var string
     */
    protected $uploadDirectory;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @var Natural|null
     *  The maximum file size in bytes.
     *  There is no limit when the file size if null.
     */
    protected $maxFileSize;

    public function __construct(
        UuidGeneratorInterface $uuidGenerator,
        CommandBus $commandBus,
        FilesystemInterface $filesystem,
        string $uploadDirectory,
        Natural $maxFileSize = null
    ) {
        $this->uuidGenerator = $uuidGenerator;
        $this->commandBus = $commandBus;
        $this->filesystem = $filesystem;
        $this->uploadDirectory = $uploadDirectory;
        $this->maxFileSize = $maxFileSize;
    }

    public function upload(
        UploadedFile $file,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        Language $language
    ): UUID {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('The file did not upload correctly.');
        }

        $mimeTypeString = $file->getMimeType();

        if (!$mimeTypeString) {
            throw new \InvalidArgumentException('The type of the uploaded file can not be guessed.');
        }

        $this->guardFileSizeLimit($file);

        $fileTypeParts = explode('/', $mimeTypeString);
        $fileType = array_shift($fileTypeParts);
        if ($fileType !== 'image') {
            throw new \InvalidArgumentException('The uploaded file is not an image.');
        }

        /** @var MIMEType $mimeType */
        $mimeType = MIMEType::fromNative($mimeTypeString);

        $fileId = new UUID($this->uuidGenerator->generate());
        $fileName = $fileId . '.' . $file->guessExtension();
        $destination = $this->getUploadDirectory() . '/' . $fileName;
        $stream = fopen($file->getRealPath(), 'r+');
        $this->filesystem->writeStream($destination, $stream);
        fclose($stream);

        $this->commandBus->dispatch(
            new UploadImage(
                $fileId,
                $mimeType,
                $description,
                $copyrightHolder,
                new StringLiteral($destination),
                $language
            )
        );

        return $fileId;
    }

    private function guardFileSizeLimit(UploadedFile $file): void
    {
        $filePath = $file->getRealPath();
        $fileSize = filesize($filePath);

        if ($this->maxFileSize && !$fileSize) {
            throw new \InvalidArgumentException('There is a maximum size and we could not determine the size of the uploaded image.');
        }

        if ($this->maxFileSize && $fileSize > $this->maxFileSize->toNative()) {
            throw new FileSizeExceededException(
                'The file size of the uploaded image is too big.'
            );
        }
    }

    public function getUploadDirectory(): string
    {
        return $this->uploadDirectory;
    }
}
