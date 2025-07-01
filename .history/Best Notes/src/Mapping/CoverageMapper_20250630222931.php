<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class CoverageMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('cov');
    }

    protected function getRootElementName(): string
    {
        return 'Coverage';
    }
}
