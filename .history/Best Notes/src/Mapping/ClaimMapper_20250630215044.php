<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

class ClaimMapper implements MapperInterface
{
    protected array $mapping;

    public function __construct()
    {
        // Load JSON mapping file that defines FHIR path → XML tag name
        $mappingPath = __DIR__ . '/../../patient_mapping.json';
        $json = file_get_contents($mappingPath);
        $this->mapping = json_decode($json, true);
    }

    /**
     * Map a FHIR Patient resource to XML using a field mapping configuration.
     */
    public function map(array $fhir, DOMDocument $doc): DOMElement
    {
        // Create the <Patient> root element
        $patient = $doc->createElement("Patient");

        // Loop over each mapping rule
        foreach ($this->mapping as $fhirPath => $xmlTag) {
            // Extract value from FHIR array using path like "address[0].city"
            $value = $this->extractValue($fhir, $fhirPath);
            $element = $doc->createElement($xmlTag, htmlspecialchars($value ?? ''));
            $patient->appendChild($element);
        }

        return $patient;
    }

    /**
     * Extracts a nested value from a FHIR array using a dotted path with array indexes.
     * Example path: "address[0].city" will return $fhir['address'][0]['city']
     */
    protected function extractValue(array $data, string $path)
    {
        $segments = preg_split('/\.(?![^\[]*\])/', $path); // split on dots not inside brackets
        foreach ($segments as $segment) {
            if (preg_match('/(\w+)\[(\d+)\]/', $segment, $matches)) {
                // Handle array index access like "address[0]"
                $key = $matches[1];
                $index = (int) $matches[2];
                if (!isset($data[$key][$index])) {
                    return null;
                }
                $data = $data[$key][$index];
            } else {
                // Handle simple key access like "city"
                if (!isset($data[$segment])) {
                    return null;
                }
                $data = $data[$segment];
            }
        }
        return is_array($data) ? null : $data;
    }
}
