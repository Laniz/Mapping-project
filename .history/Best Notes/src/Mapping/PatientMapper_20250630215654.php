<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class PatientMapper extends BaseMapper
{
    public function __construct()
    {
        parent::__construct('patient_mapping.json');
    }

    protected function getRootElementName(): string
    {
        return 'Patient';
    }
}
