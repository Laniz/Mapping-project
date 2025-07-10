<?php

namespace App\Mapping;

/**
 * Maps FHIR Organization resource to XML using organization_mapping.json
 */
class OrganizationMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('organization_mapping.json');
    }

    protected function getRootElementName(): string
    {
        return 'Organization';
    }
}
