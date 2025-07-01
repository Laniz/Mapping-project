<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

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
