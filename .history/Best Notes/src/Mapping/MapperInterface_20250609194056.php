<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

interface MapperInterface {
    public function map(array $fhirResource, DOMDocument $doc): DOMElement;
}
