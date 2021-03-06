<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Language;

trait MultilingualJsonLDProjectorTrait
{
    /**
     * @return \stdClass
     */
    protected function setMainLanguage(\stdClass $jsonLd, Language $language)
    {
        $jsonLd->mainLanguage = $language->getCode();
        return $jsonLd;
    }

    /**
     * @return Language
     */
    protected function getMainLanguage(\stdClass $jsonLd)
    {
        if (isset($jsonLd->mainLanguage)) {
            return new Language($jsonLd->mainLanguage);
        } else {
            return new Language('nl');
        }
    }
}
