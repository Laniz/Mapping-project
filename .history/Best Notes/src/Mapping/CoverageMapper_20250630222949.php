<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class CoverageMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('FHIR-Coverage-example.json');
    }

    protected function getRootElementName(): string
    {
        return 'Coverage';
    }
}
