<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

class ContentTypePropertiesFactory implements PropertiesFactoryInterface
{
    /**
     * @var ContentTypeLookupInterface
     */
    private $contentTypeLookup;


    public function __construct(ContentTypeLookupInterface $contentTypeLookup)
    {
        $this->contentTypeLookup = $contentTypeLookup;
    }

    /**
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage)
    {
        $payloadClassName = get_class($domainMessage->getPayload());
        $contentType = $this->contentTypeLookup->getContentType($payloadClassName);
        return ['content_type' => $contentType];
    }
}
