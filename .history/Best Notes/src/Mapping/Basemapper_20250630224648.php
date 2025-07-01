<?php

namespace App\Mapping; // Define the namespace where this class resides

use DOMDocument;  // Import PHP's DOMDocument class for building XML documents
use DOMElement;   // Import DOMElement for creating XML elements

/**
 * BaseMapper is an abstract class that provides common logic
 * for mapping FHIR JSON data to XML based on a JSON field mapping configuration.
 */
abstract class BaseMapper implements MapperInterface
{
    // Holds the key-value pairs from the mapping file (FHIR path → XML tag)
    protected array $mapping;

    /**
     * Constructor loads a mapping configuration file (e.g., patient_mapping.json).
     * 
     * @param string $mappingFile The relative filename of the mapping JSON file
     */
    public function __construct(string $mappingFile)
    {
        // Construct the absolute path to the mapping file
        $mappingPath = __DIR__ . '/../../' . $mappingFile;

        // Load the JSON file content as a string
        $json = file_get_contents($mappingPath);

        // Decode the JSON string into an associative array
        $this->mapping = json_decode($json, true);
    }

    /**
     * Main mapping method required by the MapperInterface.
     * It generates an XML element using the mapping config and FHIR data.
     *
     * @param array $fhir The decoded FHIR resource as an array
     * @param DOMDocument $doc The DOMDocument to which new elements will belong
     * @return DOMElement The fully built XML element for this resource
     */
    public function map(array $fhir, DOMDocument $doc): DOMElement
    {
        // Create the root XML element (e.g., <Patient>, <Coverage>, etc.)
        $element = $doc->createElement($this->getRootElementName());

        // Loop through each mapping entry: FHIR path => XML tag
        foreach ($this->mapping as $fhirPath => $xmlTag) {
            // Extract the value from the FHIR array using the path
            $value = $this->extractValue($fhir, $fhirPath);

            // Only add the field if a value was found
            if (!is_null($value)) {
                // Create the child XML element and add it to the root
               $field = $doc->createElement("Field", htmlspecialchars($value ?? ''));
                $field->setAttribute("name", $fhirPath);  // Add original path as metadata
                $element->appendChild($field);

            }
        }

        // Return the finished XML element
        return $element;
    }

    /**
     * Subclasses must define the name of the root XML element (e.g., "Patient").
     *
     * @return string The XML tag name
     */
    abstract protected function getRootElementName(): string;

    /**
     * Recursively extract a value from a deeply nested FHIR array using a dotted path.
     *
     * @param array $data The full FHIR array
     * @param string $path A string path like "address[0].city"
     * @return mixed|null The extracted value or null if not found
     */
    protected function extractValue(array $data, string $path)
    {
        // Split the path on dots, ignoring dots inside brackets
        $segments = preg_split('/\.(?![^\[]*\])/', $path);

        // Walk through each segment to drill down into the array
        foreach ($segments as $segment) {
            // If the segment includes an index (e.g., "address[0]")
            if (preg_match('/(\w+)\[(\d+)\]/', $segment, $matches)) {
                $key = $matches[1];        // array name, e.g., "address"
                $index = (int) $matches[2]; // array index, e.g., 0

                // Check if the indexed item exists
                if (!isset($data[$key][$index])) {
                    return null;
                }

                // Move one level deeper
                $data = $data[$key][$index];
            } else {
                // Simple field access (e.g., "city")
                if (!isset($data[$segment])) {
                    return null;
                }

                // Move deeper into the structure
                $data = $data[$segment];
            }
        }

        // Return the value if it's a string or scalar, not an array
        return is_array($data) ? null : $data;
    }
}
