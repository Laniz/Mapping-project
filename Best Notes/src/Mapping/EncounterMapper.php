<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class EncounterMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('encounter_mapping.json');
    }

    protected function getRootElementName(): string
    {
        return 'Encounter';
    }
}
