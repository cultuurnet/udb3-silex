<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Media;

use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class MediaImporter implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ImageCollectionFactoryInterface
     */
    private $imageCollectionFactory;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;


    public function __construct(
        MediaManagerInterface $mediaManager,
        ImageCollectionFactoryInterface $imageCollectionFactory
    ) {
        $this->mediaManager = $mediaManager;
        $this->imageCollectionFactory = $imageCollectionFactory;
        $this->logger = new NullLogger();
    }

    /**
     * @return ImageCollection
     */
    public function importImages(CultureFeed_Cdb_Item_Base $cdbItem)
    {
        $imageCollection = $this
            ->imageCollectionFactory
            ->fromUdb2Item($cdbItem);

        $imageArray = $imageCollection->toArray();
        array_walk($imageArray, [$this, 'importImage']);

        return $imageCollection;
    }


    private function importImage(Image $image)
    {
        $this->mediaManager->create(
            $image->getMediaObjectId(),
            $image->getMimeType(),
            $image->getDescription(),
            $image->getCopyrightHolder(),
            $image->getSourceLocation(),
            $image->getLanguage()
        );
    }
}
