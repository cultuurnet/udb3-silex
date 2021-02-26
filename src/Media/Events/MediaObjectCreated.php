<?php

namespace CultuurNet\UDB3\Media\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

final class MediaObjectCreated implements Serializable
{
    /**
     * @var UUID
     */
    private $mediaObjectId;

    /**
     * @var MIMEType
     */
    private $mimeType;

    /**
     * @var StringLiteral
     */
    private $description;

    /**
     * @var StringLiteral
     */
    private $copyrightHolder;

    /**
     * @var Url
     */
    private $sourceLocation;

    /**
     * @var Language
     */
    private $language;

    public function __construct(
        UUID $id,
        MIMEType $fileType,
        StringLiteral $description,
        StringLiteral $copyrightHolder,
        Url $sourceLocation,
        Language $language
    ) {
        $this->mediaObjectId = $id;
        $this->mimeType = $fileType;
        $this->description = $description;
        $this->copyrightHolder = $copyrightHolder;
        $this->sourceLocation = $sourceLocation;
        $this->language = $language;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getMediaObjectId(): UUID
    {
        return $this->mediaObjectId;
    }

    public function getDescription(): StringLiteral
    {
        return $this->description;
    }

    public function getCopyrightHolder(): StringLiteral
    {
        return $this->copyrightHolder;
    }

    public function getMimeType(): MIMEType
    {
        return $this->mimeType;
    }

    public function getSourceLocation(): Url
    {
        return $this->sourceLocation;
    }

    public function serialize(): array
    {
        return [
            'media_object_id' => $this->getMediaObjectId()->toNative(),
            'mime_type' => $this->getMimeType()->toNative(),
            'description' => $this->getDescription()->toNative(),
            'copyright_holder' => $this->getCopyrightHolder()->toNative(),
            'source_location' => (string) $this->getSourceLocation(),
            'language' => (string) $this->getLanguage(),
        ];
    }

    public static function deserialize(array $data): MediaObjectCreated
    {
        return new self(
            new UUID($data['media_object_id']),
            new MIMEType($data['mime_type']),
            new StringLiteral($data['description']),
            new StringLiteral($data['copyright_holder']),
            Url::fromNative($data['source_location']),
            array_key_exists('language', $data) ? new Language($data['language']) : new Language('nl')
        );
    }
}
