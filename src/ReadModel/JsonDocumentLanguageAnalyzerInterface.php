<?php

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Language;

interface JsonDocumentLanguageAnalyzerInterface
{
    /**
     * @return Language[]
     */
    public function determineAvailableLanguages(JsonDocument $jsonDocument);

    /**
     * @return Language[]
     */
    public function determineCompletedLanguages(JsonDocument $jsonDocument);
}
