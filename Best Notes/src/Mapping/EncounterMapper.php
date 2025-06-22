<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class EncounterMapper implements MapperInterface{


    public function map(array $fhirResource, DOMDocument $doc): DOMElement{
        $encounter = $doc->createElement("Patient");
        return $encounter;
    }
}
