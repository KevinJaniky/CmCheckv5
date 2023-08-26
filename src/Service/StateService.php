<?php

namespace App\Service;

class  StateService
{
    public static function getStates(): array
    {
        return [
            'draft' => 'Brouillon',
            'check' => 'En attente de validation',
            'validate' => 'Validé',
            'rework' => 'A retravailler',
        ];
    }

    public static function getState(string $state): string
    {
        return self::getStates()[$state];
    }
}