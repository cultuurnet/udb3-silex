<?php

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

class CreateProductionValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        $messages = [];
        $messages = array_merge($messages, $this->validateName($data));
        $messages = array_merge($messages, $this->validateEvents($data));

        if (!empty($messages)) {
            $exception = new DataValidationException();
            $exception->setValidationMessages($messages);
            throw $exception;
        }
    }

    private function validateName(array $data): array
    {
        if (!isset($data['name'])) {
            return [
                'name' => 'Required but could not be found',
            ];
        }

        if (empty(trim($data['name']))) {
            return [
                'name' => 'Cannot be empty',
            ];
        }

        return [];
    }

    private function validateEvents(array $data): array
    {
        if (!isset($data['eventIds'])) {
            return [
                'eventIds' => 'Required but could not be found',
            ];
        }

        if (count($data['eventIds']) < 2) {
            return [
                'eventIds' => 'At least two events should be provided',
            ];
        }

        return [];
    }
}
