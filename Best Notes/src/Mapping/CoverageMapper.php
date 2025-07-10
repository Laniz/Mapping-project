<?php

namespace App\Mapping;

/**
 * Maps FHIR Coverage resource to XML using coverage_mapping.json
 */
class CoverageMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('coverage_mapping.json');
    }

    protected function getRootElementName(): string
    {
        return 'Coverage';
    }
}
