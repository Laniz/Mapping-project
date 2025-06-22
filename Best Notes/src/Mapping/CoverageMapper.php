<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class CoverageMapper implements MapperInterface{


    public function map(array $fhirResource, DOMDocument $doc): DOMElement{
        $coverage = $doc->createElement("Patient");
        return $coverage;
    }
}
