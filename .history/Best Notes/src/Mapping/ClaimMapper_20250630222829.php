<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

/**
 * ClaimMapper extends BaseMapper and uses claim_mapping.json
 * to convert FHIR Claim resources to XML.
 */
class ClaimMapper extends BaseMapper
{
    public function __construct()
    {
        // Tell the BaseMapper to load the claim mapping config
        parent::__construct('FHIR-CLAIM.json');
    }

    /**
     * Return the XML root tag for this resource
     */
    protected function getRootElementName(): string
    {
        return 'Claim';
    }
}
