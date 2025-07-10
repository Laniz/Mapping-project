<?php

namespace App\Mapping;

/**
 * Maps FHIR Practitioner resource to XML using practitioner_mapping.json
 */
class PractitionerMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('practitioner_mapping.json');
    }

    protected function getRootElementName(): string
    {
        return 'Rendering';
    }
}
