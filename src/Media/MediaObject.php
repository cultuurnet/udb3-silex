<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Events\MediaObjectCreated;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

/**
 * MediaObjects for UDB3.
 */
class MediaObject extends EventSourcedAggregateRoot
{
    /**
     * Mime type of the media object.
     *
     * @var MIMEType
     */
    protected $mimeType;

    /**
     * The id of the media object.
     *
     * @var UUID
     */
    protected $mediaObjectId;

    /**
     * Description of the media object.
     *
     * @var StringLiteral
     */
    protected $description;

    /**
     * Copyright info.
     *
     * @var CopyrightHolder
     */
    protected $copyrightHolder;

    /**
     * The URL where the source file can be found.
     * @var Url
     */
    protected $sourceLocation;

    /**
     * @var Language
     */
    protected $language;

    public static function create(
        UUID $id,
        MIMEType $mimeType,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ): MediaObject {
        $mediaObject = new self();
        $mediaObject->apply(
            new MediaObjectCreated(
                $id,
                $mimeType,
                $description,
                $copyrightHolder,
                $sourceLocation,
                $language
            )
        );

        return $mediaObject;
    }

    public function getAggregateRootId(): string
    {
        return $this->mediaObjectId->toNative();
    }

    protected function applyMediaObjectCreated(MediaObjectCreated $mediaObjectCreated)
    {
        $this->mediaObjectId = $mediaObjectCreated->getMediaObjectId();
        $this->mimeType = $mediaObjectCreated->getMimeType();
        $this->description = $mediaObjectCreated->getDescription();
        $this->copyrightHolder = $mediaObjectCreated->getCopyrightHolder();
        $this->sourceLocation = $mediaObjectCreated->getSourceLocation();
        $this->language = $mediaObjectCreated->getLanguage();
    }

    /**
     * @return StringLiteral
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getCopyrightHolder(): CopyrightHolder
    {
        return $this->copyrightHolder;
    }

    /**
     * @return UUID
     */
    public function getMediaObjectId()
    {
        return $this->mediaObjectId;
    }

    /**
     * @return MIMEType
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return Url
     */
    public function getSourceLocation()
    {
        return $this->sourceLocation;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }
}
