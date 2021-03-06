<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use CultureFeed_Cdb_Item_Base;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Collection\Exception\CollectionItemNotFoundException;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelAwareAggregateRoot;
use CultuurNet\UDB3\LabelCollection;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Events\AbstractBookingInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractCalendarUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractContactPointUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractDescriptionUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractFacilitiesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractGeoCoordinatesUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractLabelAdded;
use CultuurNet\UDB3\Offer\Events\AbstractLabelRemoved;
use CultuurNet\UDB3\Offer\Events\AbstractLabelsImported;
use CultuurNet\UDB3\Offer\Events\AbstractOfferDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractOrganizerUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractOwnerChanged;
use CultuurNet\UDB3\Offer\Events\AbstractPriceInfoUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractThemeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleTranslated;
use CultuurNet\UDB3\Offer\Events\AbstractTitleUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypeUpdated;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Events\AbstractTypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageAdded;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageRemoved;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesEvent;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Events\Image\AbstractImageUpdated;
use CultuurNet\UDB3\Offer\Events\Image\AbstractMainImageSelected;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractApproved;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractFlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractFlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractPublished;
use CultuurNet\UDB3\Offer\Events\Moderation\AbstractRejected;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use Exception;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

abstract class Offer extends EventSourcedAggregateRoot implements LabelAwareAggregateRoot
{
    public const DUPLICATE_REASON = 'duplicate';
    public const INAPPROPRIATE_REASON = 'inappropriate';

    /**
     * @var LabelCollection
     */
    protected $labels;

    /**
     * @var ImageCollection
     */
    protected $images;

    /**
     * @var string|null
     *
     * Organizer ids can come from UDB2 which does not strictly use UUIDs.
     */
    protected $organizerId;

    /**
     * @var WorkflowStatus
     */
    protected $workflowStatus;

    /**
     * @var StringLiteral|null
     */
    protected $rejectedReason;

    /**
     * @var PriceInfo|null
     */
    protected $priceInfo;

    /**
     * @var StringLiteral[]
     */
    protected $titles;

    /**
     * @var Description[]
     */
    protected $descriptions;

    /**
     * @var Language
     */
    protected $mainLanguage;

    /**
     * @var string;
     */
    protected $typeId;

    /**
     * @var string;
     */
    protected $themeId;

    /**
     * @var array
     */
    protected $facilities;

    /**
     * @var ContactPoint|null
     */
    protected $contactPoint;

    /**
     * @var Calendar|null
     */
    protected $calendar;

    /**
     * @var AgeRange|null
     */
    protected $typicalAgeRange;

    /**
     * @var BookingInfo|null
     */
    protected $bookingInfo;

    /**
     * @var bool
     */
    protected $isDeleted = false;

    /**
     * @var string|null
     */
    private $ownerId;

    /**
     * Offer constructor.
     */
    public function __construct()
    {
        $this->titles = [];
        $this->descriptions = [];
        $this->labels = new LabelCollection();
        $this->images = new ImageCollection();
        $this->facilities = [];
        $this->contactPoint = null;
        $this->calendar = null;
        $this->typicalAgeRange = null;
        $this->bookingInfo = null;
    }

    public function changeOwner(string $newOwnerId): void
    {
        // Will always be true for the first call to changeOwner() since we have no way to know who the creator was
        // inside the aggregate root. That's stored in the metadata of the DomainMessage, not the payload, and Broadway
        // does not pass that metadata to the apply...() methods.
        if ($this->ownerId !== $newOwnerId) {
            $this->apply($this->createOwnerChangedEvent($newOwnerId));
        }
    }

    protected function applyOwnerChanged(AbstractOwnerChanged $ownerChanged): void
    {
        $this->ownerId = $ownerChanged->getNewOwnerId();
    }

    abstract protected function createOwnerChangedEvent($newOwnerId): AbstractOwnerChanged;


    public function updateType(EventType $type)
    {
        if (!$this->typeId || $this->typeId !== $type->getId()) {
            $this->apply($this->createTypeUpdatedEvent($type));
        }
    }

    public function updateAllStatuses(Status $status): void
    {
        $this->updateCalendar(
            $this->calendar
                ->withStatus($status)
                ->withStatusOnTimestamps($status)
        );
    }


    public function updateTheme(Theme $theme)
    {
        if (!$this->themeId || $this->themeId !== $theme->getId()) {
            $this->apply($this->createThemeUpdatedEvent($theme));
        }
    }


    public function updateFacilities(array $facilities)
    {
        if (empty($this->facilities) || !$this->sameFacilities($this->facilities, $facilities)) {
            $this->apply($this->createFacilitiesUpdatedEvent($facilities));
        }
    }


    protected function applyFacilitiesUpdated(AbstractFacilitiesUpdated $facilitiesUpdated)
    {
        $this->facilities = $facilitiesUpdated->getFacilities();
    }

    /**
     * @param array $facilities1
     * @param array $facilities2
     * @return bool
     */
    private function sameFacilities($facilities1, $facilities2)
    {
        if (count($facilities1) !== count($facilities2)) {
            return false;
        }

        $sameFacilities = array_uintersect(
            $facilities1,
            $facilities2,
            function (Facility $facility1, Facility $facility2) {
                return strcmp($facility1->getId(), $facility2->getId());
            }
        );

        return count($sameFacilities) === count($facilities2);
    }

    /**
     * Get the id of the main image if one is selected for this offer.
     *
     * @return UUID|null
     */
    protected function getMainImageId()
    {
        $mainImage = $this->images->getMain();
        return isset($mainImage) ? $mainImage->getMediaObjectId() : null;
    }

    /**
     * @inheritdoc
     */
    public function addLabel(Label $label)
    {
        if (!$this->labels->contains($label)) {
            $this->apply(
                $this->createLabelAddedEvent($label)
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function removeLabel(Label $label)
    {
        if ($this->labels->contains($label)) {
            $this->apply(
                $this->createLabelRemovedEvent($label)
            );
        }
    }


    public function importLabels(Labels $labels, Labels $labelsToKeepIfAlreadyOnOffer, Labels $labelsToRemoveWhenOnOffer)
    {
        $convertLabelClass = function (\CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label $label) {
            return new Label(
                $label->getName()->toString(),
                $label->isVisible()
            );
        };

        // Convert the imported labels to label collection.
        $importLabelsCollection = new LabelCollection(
            array_map($convertLabelClass, $labels->toArray())
        );

        // Convert the labels to keep if already applied.
        $keepLabelsCollection = new LabelCollection(
            array_map($convertLabelClass, $labelsToKeepIfAlreadyOnOffer->toArray())
        );

        // Convert the labels to remove when on offer.
        $removeLabelsCollection = new LabelCollection(
            array_map($convertLabelClass, $labelsToRemoveWhenOnOffer->toArray())
        );

        // What are the added labels?
        // Labels which are not inside the internal state but inside the imported labels
        $addedLabels = new LabelCollection();
        foreach ($importLabelsCollection->asArray() as $label) {
            if (!$this->labels->contains($label)) {
                $addedLabels = $addedLabels->with($label);
            }
        }

        // Fire a LabelsImported for all new labels.
        $importLabels = new Labels();
        foreach ($addedLabels->asArray() as $addedLabel) {
            $importLabels = $importLabels->with(
                new \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label(
                    new LabelName((string) $addedLabel),
                    $addedLabel->isVisible()
                )
            );
        }
        if ($importLabels->count() > 0) {
            $this->apply($this->createLabelsImportedEvent($importLabels));
        }

        // For each added label fire a LabelAdded event.
        foreach ($addedLabels->asArray() as $label) {
            $this->apply($this->createLabelAddedEvent($label));
        }

        // What are the deleted labels?
        // Labels which are inside the internal state but not inside imported labels or labels to keep.
        // For each deleted label fire a LabelDeleted event.
        foreach ($this->labels->asArray() as $label) {
            if (!$importLabelsCollection->contains($label) && !$keepLabelsCollection->contains($label) && $removeLabelsCollection->contains($label)) {
                $this->apply($this->createLabelRemovedEvent($label));
            }
        }
    }


    public function updateTitle(Language $language, Title $title)
    {
        if ($this->isTitleChanged($title, $language)) {
            if ($language->getCode() !== $this->mainLanguage->getCode()) {
                $event = $this->createTitleTranslatedEvent($language, $title);
            } else {
                $event = $this->createTitleUpdatedEvent($title);
            }

            $this->apply($event);
        }
    }


    public function applyTitleTranslated(AbstractTitleTranslated $titleTranslated)
    {
        $this->titles[$titleTranslated->getLanguage()->getCode()] = $titleTranslated->getTitle();
    }



    public function applyTitleUpdated(AbstractTitleUpdated $titleUpdated)
    {
        $this->titles[$this->mainLanguage->getCode()] = $titleUpdated->getTitle();
    }


    public function updateDescription(Description $description, Language $language)
    {
        if ($this->isDescriptionChanged($description, $language)) {
            if ($language->getCode() !== $this->mainLanguage->getCode()) {
                $event = $this->createDescriptionTranslatedEvent($language, $description);
            } else {
                $event = $this->createDescriptionUpdatedEvent($description);
            }

            $this->apply($event);
        }
    }


    public function updateCalendar(Calendar $calendar)
    {
        if (is_null($this->calendar) || !$this->calendar->sameAs($calendar)) {
            $this->apply(
                $this->createCalendarUpdatedEvent($calendar)
            );
        }
    }


    protected function applyCalendarUpdated(AbstractCalendarUpdated $calendarUpdated)
    {
        $this->calendar = $calendarUpdated->getCalendar();
    }


    public function updateTypicalAgeRange(AgeRange $typicalAgeRange)
    {
        $typicalAgeRangeUpdatedEvent = $this->createTypicalAgeRangeUpdatedEvent($typicalAgeRange);

        if (empty($this->typicalAgeRange) || !$this->typicalAgeRange->sameAs($typicalAgeRangeUpdatedEvent->getTypicalAgeRange())) {
            $this->apply($typicalAgeRangeUpdatedEvent);
        }
    }


    protected function applyTypicalAgeRangeUpdated(AbstractTypicalAgeRangeUpdated $typicalAgeRangeUpdated)
    {
        $this->typicalAgeRange = $typicalAgeRangeUpdated->getTypicalAgeRange();
    }

    public function deleteTypicalAgeRange()
    {
        if (!is_null($this->typicalAgeRange)) {
            $this->apply(
                $this->createTypicalAgeRangeDeletedEvent()
            );
        }
    }


    public function applyTypicalAgeRangeDeleted(AbstractTypicalAgeRangeDeleted $typicalAgeRangeDeleted)
    {
        $this->typicalAgeRange = null;
    }

    /**
     * @param string $organizerId
     */
    public function updateOrganizer($organizerId)
    {
        if ($this->organizerId !== $organizerId) {
            $this->apply(
                $this->createOrganizerUpdatedEvent($organizerId)
            );
        }
    }

    /**
     * Delete the given organizer.
     *
     * @param string $organizerId
     */
    public function deleteOrganizer($organizerId)
    {
        if ($this->organizerId === $organizerId) {
            $this->apply(
                $this->createOrganizerDeletedEvent($organizerId)
            );
        }
    }

    /**
     * Delete the current organizer regardless of the id.
     */
    public function deleteCurrentOrganizer()
    {
        if (!is_null($this->organizerId)) {
            $this->apply(
                $this->createOrganizerDeletedEvent($this->organizerId)
            );
        }
    }

    /**
     * Updated the contact info.
     */
    public function updateContactPoint(ContactPoint $contactPoint)
    {
        if (is_null($this->contactPoint) || !$this->contactPoint->sameAs($contactPoint)) {
            $this->apply(
                $this->createContactPointUpdatedEvent($contactPoint)
            );
        }
    }


    protected function applyContactPointUpdated(AbstractContactPointUpdated $contactPointUpdated)
    {
        $this->contactPoint = $contactPointUpdated->getContactPoint();
    }


    public function updateGeoCoordinates(Coordinates $coordinates)
    {
        // Note: DON'T compare to previous coordinates and apply only on
        // changes. Various projectors expect GeoCoordinatesUpdated after
        // MajorInfoUpdated and PlaceUpdatedFromUDB2, even if the address
        // and thus the coordinates haven't actually changed.
        $this->apply(
            $this->createGeoCoordinatesUpdatedEvent($coordinates)
        );
    }

    /**
     * Updated the booking info.
     *
     */
    public function updateBookingInfo(BookingInfo $bookingInfo)
    {
        if (is_null($this->bookingInfo) || !$this->bookingInfo->sameAs($bookingInfo)) {
            $this->apply(
                $this->createBookingInfoUpdatedEvent($bookingInfo)
            );
        }
    }


    public function applyBookingInfoUpdated(AbstractBookingInfoUpdated $bookingInfoUpdated)
    {
        $this->bookingInfo = $bookingInfoUpdated->getBookingInfo();
    }


    public function updatePriceInfo(PriceInfo $priceInfo)
    {
        if (is_null($this->priceInfo) || $priceInfo->serialize() !== $this->priceInfo->serialize()) {
            $this->apply(
                $this->createPriceInfoUpdatedEvent($priceInfo)
            );
        }
    }


    protected function applyPriceInfoUpdated(AbstractPriceInfoUpdated $priceInfoUpdated)
    {
        $this->priceInfo = $priceInfoUpdated->getPriceInfo();
    }


    protected function applyLabelAdded(AbstractLabelAdded $labelAdded)
    {
        $this->labels = $this->labels->with($labelAdded->getLabel());
    }


    protected function applyLabelRemoved(AbstractLabelRemoved $labelRemoved)
    {
        $this->labels = $this->labels->without($labelRemoved->getLabel());
    }


    protected function applyThemeUpdated(AbstractThemeUpdated $themeUpdated)
    {
        $this->themeId = $themeUpdated->getTheme()->getId();
    }


    protected function applyTypeUpdated(AbstractTypeUpdated $themeUpdated)
    {
        $this->typeId = $themeUpdated->getType()->getId();
    }

    protected function applyDescriptionUpdated(AbstractDescriptionUpdated $descriptionUpdated)
    {
        $mainLanguageCode = $this->mainLanguage->getCode();
        $this->descriptions[$mainLanguageCode] = $descriptionUpdated->getDescription();
    }

    protected function applyDescriptionTranslated(AbstractDescriptionTranslated $descriptionTranslated)
    {
        $languageCode = $descriptionTranslated->getLanguage()->getCode();
        $this->descriptions[$languageCode] = $descriptionTranslated->getDescription();
    }

    /**
     * Add a new image.
     *
     */
    public function addImage(Image $image)
    {
        // Find the image based on UUID inside the internal state.
        $existingImage = $this->images->findImageByUUID($image->getMediaObjectId());

        if ($existingImage === null) {
            $this->apply(
                $this->createImageAddedEvent($image)
            );
        }
    }


    public function updateImage(
        UUID $mediaObjectId,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder
    ) {
        if ($this->updateImageAllowed($mediaObjectId, $description, $copyrightHolder)) {
            $this->apply(
                $this->createImageUpdatedEvent(
                    $mediaObjectId,
                    $description,
                    $copyrightHolder
                )
            );
        }
    }

    private function updateImageAllowed(
        UUID $mediaObjectId,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder
    ): bool {
        $image = $this->images->findImageByUUID($mediaObjectId);

        // Don't update if the image is not found based on UUID.
        if (!$image) {
            return false;
        }

        // Update when copyright or description is changed.
        return !$copyrightHolder->sameAs($image->getCopyrightHolder()) ||
            !$description->sameValueAs($image->getDescription());
    }

    /**
     * Remove an image.
     *
     */
    public function removeImage(Image $image)
    {
        // Find the image based on UUID inside the internal state.
        // Use the image from the internal state.
        $existingImage = $this->images->findImageByUUID($image->getMediaObjectId());

        if ($existingImage) {
            $this->apply(
                $this->createImageRemovedEvent($existingImage)
            );
        }
    }

    /**
     * Make an existing image of the item the main image.
     *
     */
    public function selectMainImage(Image $image)
    {
        if (!$this->images->findImageByUUID($image->getMediaObjectId())) {
            throw new \InvalidArgumentException('You can not select a random image to be main, it has to be added to the item.');
        }

        $oldMainImage = $this->images->getMain();

        if (!isset($oldMainImage) || $oldMainImage->getMediaObjectId() !== $image->getMediaObjectId()) {
            $this->apply(
                $this->createMainImageSelectedEvent($image)
            );
        }
    }


    public function importImages(ImageCollection $imageCollection)
    {
        $currentImageCollection = $this->images;
        $newMainImage = $imageCollection->getMain();

        $importImages = $imageCollection->toArray();
        $currentImages = $currentImageCollection->toArray();

        $compareImages = function (Image $a, Image $b) {
            $idA = $a->getMediaObjectId()->toNative();
            $idB = $b->getMediaObjectId()->toNative();
            return strcmp($idA, $idB);
        };

        /* @var Image[] $addedImages */
        $addedImages = array_udiff($importImages, $currentImages, $compareImages);

        /* @var Image[] $updatedImages */
        $updatedImages = array_uintersect($importImages, $currentImages, $compareImages);

        /* @var Image[] $removedImages */
        $removedImages = array_udiff($currentImages, $importImages, $compareImages);

        foreach ($addedImages as $addedImage) {
            $this->apply($this->createImageAddedEvent($addedImage));
        }

        foreach ($updatedImages as $updatedImage) {
            $this->apply(
                $this->createImageUpdatedEvent(
                    $updatedImage->getMediaObjectId(),
                    $updatedImage->getDescription(),
                    $updatedImage->getCopyrightHolder()
                )
            );
        }

        foreach ($removedImages as $removedImage) {
            $this->apply($this->createImageRemovedEvent($removedImage));
        }

        if ($newMainImage) {
            $this->apply($this->createMainImageSelectedEvent($newMainImage));
        }
    }

    /**
     * Delete the offer.
     */
    public function delete()
    {
        $this->apply(
            $this->createOfferDeletedEvent()
        );
    }


    protected function importWorkflowStatus(CultureFeed_Cdb_Item_Base $cdbItem)
    {
        try {
            $workflowStatus = WorkflowStatus::fromNative($cdbItem->getWfStatus());
        } catch (\InvalidArgumentException $exception) {
            $workflowStatus = WorkflowStatus::READY_FOR_VALIDATION();
        }
        $this->workflowStatus = $workflowStatus;
    }

    /**
     * Publish the offer when it has workflowstatus draft.
     */
    public function publish(\DateTimeInterface $publicationDate)
    {
        $this->guardPublish() ?: $this->apply(
            $this->createPublishedEvent($publicationDate)
        );
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function guardPublish()
    {
        if ($this->workflowStatus === WorkflowStatus::READY_FOR_VALIDATION()) {
            return true; // nothing left to do if the offer has already been published
        }

        if ($this->workflowStatus !== WorkflowStatus::DRAFT()) {
            throw new Exception('You can not publish an offer that is not draft');
        }

        return false;
    }

    /**
     * Approve the offer when it's waiting for validation.
     */
    public function approve()
    {
        $this->guardApprove() ?: $this->apply($this->createApprovedEvent());
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function guardApprove()
    {
        if ($this->workflowStatus === WorkflowStatus::APPROVED()) {
            return true; // nothing left to do if the offer has already been approved
        }

        if ($this->workflowStatus !== WorkflowStatus::READY_FOR_VALIDATION()) {
            throw new Exception('You can not approve an offer that is not ready for validation');
        }

        return false;
    }

    /**
     * Reject an offer that is waiting for validation with a given reason.
     */
    public function reject(StringLiteral $reason)
    {
        $this->guardRejection($reason) ?: $this->apply($this->createRejectedEvent($reason));
    }

    public function flagAsDuplicate()
    {
        $reason = new StringLiteral(self::DUPLICATE_REASON);
        $this->guardRejection($reason) ?: $this->apply($this->createFlaggedAsDuplicate());
    }

    public function flagAsInappropriate()
    {
        $reason = new StringLiteral(self::INAPPROPRIATE_REASON);
        $this->guardRejection($reason) ?: $this->apply($this->createFlaggedAsInappropriate());
    }

    /**
     * @return bool
     *  false when the offer can still be rejected, true when the offer is already rejected for the same reason
     * @throws Exception
     */
    private function guardRejection(StringLiteral $reason)
    {
        if ($this->workflowStatus === WorkflowStatus::REJECTED()) {
            if ($this->rejectedReason && $reason->sameValueAs($this->rejectedReason)) {
                return true; // nothing left to do if the offer has already been rejected for the same reason
            } else {
                throw new Exception('The offer has already been rejected for another reason: ' . $this->rejectedReason);
            }
        }

        if ($this->workflowStatus !== WorkflowStatus::READY_FOR_VALIDATION()) {
            throw new Exception('You can not reject an offer that is not ready for validation');
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isTitleChanged(Title $title, Language $language)
    {
        $languageCode = $language->getCode();

        return !isset($this->titles[$languageCode]) ||
            !$title->sameValueAs($this->titles[$languageCode]);
    }

    /**
     * @return bool
     */
    private function isDescriptionChanged(Description $description, Language $language)
    {
        $languageCode = $language->getCode();

        return !isset($this->descriptions[$languageCode]) ||
            !$description->sameValueAs($this->descriptions[$languageCode]);
    }

    /**
     * Overwrites or resets the main image and all media objects
     * by importing a new collection of images from UDB2.
     *
     */
    public function importImagesFromUDB2(ImageCollection $images)
    {
        $this->apply($this->createImagesImportedFromUDB2($images));
    }

    /**
     * Overwrites or resets the main image and all media objects
     * by updating with a new collection of images from UDB2.
     *
     */
    public function updateImagesFromUDB2(ImageCollection $images)
    {
        $this->apply($this->createImagesUpdatedFromUDB2($images));
    }


    protected function applyPublished(AbstractPublished $published)
    {
        $this->workflowStatus = WorkflowStatus::READY_FOR_VALIDATION();
    }


    protected function applyApproved(AbstractApproved $approved)
    {
        $this->workflowStatus = WorkflowStatus::APPROVED();
    }


    protected function applyRejected(AbstractRejected $rejected)
    {
        $this->rejectedReason = $rejected->getReason();
        $this->workflowStatus = WorkflowStatus::REJECTED();
    }


    protected function applyFlaggedAsDuplicate(AbstractFlaggedAsDuplicate $flaggedAsDuplicate)
    {
        $this->rejectedReason = new StringLiteral(self::DUPLICATE_REASON);
        $this->workflowStatus = WorkflowStatus::REJECTED();
    }


    protected function applyFlaggedAsInappropriate(AbstractFlaggedAsInappropriate $flaggedAsInappropriate)
    {
        $this->rejectedReason = new StringLiteral(self::INAPPROPRIATE_REASON);
        $this->workflowStatus = WorkflowStatus::REJECTED();
    }

    protected function applyImageAdded(AbstractImageAdded $imageAdded)
    {
        $this->images = $this->images->with($imageAdded->getImage());
    }

    protected function applyImageUpdated(AbstractImageUpdated $imageUpdated)
    {
        $image = $this->images->findImageByUUID($imageUpdated->getMediaObjectId());

        $updatedImage = new Image(
            $image->getMediaObjectId(),
            $image->getMimeType(),
            new ImageDescription($imageUpdated->getDescription()->toNative()),
            $imageUpdated->getCopyrightHolder(),
            $image->getSourceLocation(),
            $image->getLanguage()
        );

        // Currently no other option to update an item inside a collection.
        $this->images = $this->images->without($image);
        $this->images = $this->images->with($updatedImage);
    }

    protected function applyImageRemoved(AbstractImageRemoved $imageRemoved)
    {
        try {
            $this->images = $this->images->without($imageRemoved->getImage());
        } catch (CollectionItemNotFoundException $exception) {
        }
    }

    protected function applyMainImageSelected(AbstractMainImageSelected $mainImageSelected)
    {
        $this->images = $this->images->withMain($mainImageSelected->getImage());
    }

    protected function applyOrganizerUpdated(AbstractOrganizerUpdated $organizerUpdated)
    {
        $this->organizerId = $organizerUpdated->getOrganizerId();
    }

    protected function applyOrganizerDeleted(AbstractOrganizerDeleted $organizerDeleted)
    {
        $this->organizerId = null;
    }


    protected function applyImagesImportedFromUDB2(AbstractImagesImportedFromUDB2 $imagesImportedFromUDB2)
    {
        $this->applyUdb2ImagesEvent($imagesImportedFromUDB2);
    }


    protected function applyImagesUpdatedFromUDB2(AbstractImagesUpdatedFromUDB2 $imagesUpdatedFromUDB2)
    {
        $this->applyUdb2ImagesEvent($imagesUpdatedFromUDB2);
    }

    /**
     * This indirect apply method can be called internally to deal with images coming from UDB2.
     * Imports from UDB2 only contain the native Dutch content.
     * @see https://github.com/cultuurnet/udb3-udb2-bridge/blob/db0a7ab2444f55bb3faae3d59b82b39aaeba253b/test/Media/ImageCollectionFactoryTest.php#L79-L103
     * Because of this we have to make sure translated images are left in place.
     *
     */
    protected function applyUdb2ImagesEvent(AbstractImagesEvent $imagesEvent)
    {
        $newMainImage = $imagesEvent->getImages()->getMain();
        $dutchImagesList = $imagesEvent->getImages()->toArray();
        $translatedImagesList = array_filter(
            $this->images->toArray(),
            function (Image $image) {
                return $image->getLanguage()->getCode() !== 'nl';
            }
        );

        $imagesList = array_merge($dutchImagesList, $translatedImagesList);
        $images = ImageCollection::fromArray($imagesList);

        $this->images = isset($newMainImage) ? $images->withMain($newMainImage) : $images;
    }

    /**
     * @return AbstractLabelAdded
     */
    abstract protected function createLabelAddedEvent(Label $label);

    /**
     * @return AbstractLabelRemoved
     */
    abstract protected function createLabelRemovedEvent(Label $label);

    /**
     * @return AbstractLabelsImported
     */
    abstract protected function createLabelsImportedEvent(Labels $labels);

    /**
     * @return AbstractTitleTranslated
     */
    abstract protected function createTitleTranslatedEvent(Language $language, Title $title);

    /**
     * @return AbstractDescriptionTranslated
     */
    abstract protected function createDescriptionTranslatedEvent(Language $language, Description $description);

    /**
     * @return AbstractImageAdded
     */
    abstract protected function createImageAddedEvent(Image $image);

    /**
     * @return AbstractImageRemoved
     */
    abstract protected function createImageRemovedEvent(Image $image);

    /**
     * @return AbstractImageUpdated
     */
    abstract protected function createImageUpdatedEvent(
        UUID $uuid,
        StringLiteral $description,
        CopyrightHolder $copyrightHolder
    );

    /**
     * @return AbstractMainImageSelected
     */
    abstract protected function createMainImageSelectedEvent(Image $image);

    /**
     * @return AbstractOfferDeleted
     */
    abstract protected function createOfferDeletedEvent();

    /**
     * @return AbstractTitleUpdated
     */
    abstract protected function createTitleUpdatedEvent(Title $title);

    /**
     * @return AbstractDescriptionUpdated
     */
    abstract protected function createDescriptionUpdatedEvent(Description $description);

    /**
     * @return AbstractCalendarUpdated
     */
    abstract protected function createCalendarUpdatedEvent(Calendar $calendar);

    /**
     * @param AgeRange $typicalAgeRange
     * @return AbstractTypicalAgeRangeUpdated
     */
    abstract protected function createTypicalAgeRangeUpdatedEvent($typicalAgeRange);

    /**
     * @return AbstractTypicalAgeRangeDeleted
     */
    abstract protected function createTypicalAgeRangeDeletedEvent();

    /**
     * @param string $organizerId
     * @return AbstractOrganizerUpdated
     */
    abstract protected function createOrganizerUpdatedEvent($organizerId);

    /**
     * @param string $organizerId
     * @return AbstractOrganizerDeleted
     */
    abstract protected function createOrganizerDeletedEvent($organizerId);

    /**
     * @return AbstractContactPointUpdated
     */
    abstract protected function createContactPointUpdatedEvent(ContactPoint $contactPoint);

    /**
     * @return AbstractGeoCoordinatesUpdated
     */
    abstract protected function createGeoCoordinatesUpdatedEvent(Coordinates $coordinates);

    /**
     * @return AbstractBookingInfoUpdated
     */
    abstract protected function createBookingInfoUpdatedEvent(BookingInfo $bookingInfo);

    /**
     * @return AbstractPriceInfoUpdated
     */
    abstract protected function createPriceInfoUpdatedEvent(PriceInfo $priceInfo);

    /**
     * @return AbstractPublished
     */
    abstract protected function createPublishedEvent(\DateTimeInterface $publicationDate);

    /**
     * @return AbstractApproved
     */
    abstract protected function createApprovedEvent();

    /**
     * @return AbstractRejected
     */
    abstract protected function createRejectedEvent(StringLiteral $reason);

    /**
     * @return AbstractFlaggedAsDuplicate
     */
    abstract protected function createFlaggedAsDuplicate();

    /**
     * @return AbstractFlaggedAsInappropriate
     */
    abstract protected function createFlaggedAsInappropriate();

    /**
     * @return AbstractImagesImportedFromUDB2
     */
    abstract protected function createImagesImportedFromUDB2(ImageCollection $images);

    /**
     * @return AbstractImagesUpdatedFromUDB2
     */
    abstract protected function createImagesUpdatedFromUDB2(ImageCollection $images);

    /**
     * @return AbstractTypeUpdated
     */
    abstract protected function createTypeUpdatedEvent(EventType $type);

    /**
     * @return AbstractThemeUpdated
     */
    abstract protected function createThemeUpdatedEvent(Theme $theme);

    /**
     * @return AbstractFacilitiesUpdated
     */
    abstract protected function createFacilitiesUpdatedEvent(array $facilities);
}
