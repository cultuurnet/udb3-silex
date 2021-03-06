<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\XSD;

class CachedInMemoryXSDReader implements XSDReaderInterface
{
    /**
     * @var XSDReaderInterface
     */
    private $decoratedReader;

    /**
     * @var XSD|null
     */
    private $cachedXSD;


    public function __construct(XSDReaderInterface $xsdReader)
    {
        $this->decoratedReader = $xsdReader;
    }

    /**
     * @return XSD
     */
    public function read()
    {
        if (!$this->cachedXSD) {
            $this->cachedXSD = $this->decoratedReader->read();
        }

        return $this->cachedXSD;
    }
}
