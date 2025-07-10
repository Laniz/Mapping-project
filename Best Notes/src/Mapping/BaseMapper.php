<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

/**
 * Abstract base class for FHIR → XML mapping.
 * Uses a config file to translate paths to XML elements.
 * Supports:
 *   - flat fields
 *   - grouped elements (e.g., Address.City)
 *   - array-based loops (e.g., item[].code → Charge.Code)
 */
abstract class BaseMapper implements MapperInterface
{
    protected array $mapping;

    /**
     * Loads mapping config and validates it.
     */
    public function __construct(string $mappingFile)
    {
        $mappingPath = __DIR__ . '/../../config/' . $mappingFile;

        error_log("Loading mapping config: $mappingPath");

        if (!file_exists($mappingPath)) {
            throw new \RuntimeException("Mapping file not found: $mappingPath");
        }

        $json = file_get_contents($mappingPath);
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException("Invalid JSON structure in mapping file: $mappingPath");
        }

        if (empty($decoded)) {
            throw new \RuntimeException("Mapping file is empty: $mappingPath");
        }

        $this->mapping = $decoded;
        error_log("Mapping config loaded for " . static::class . " with " . count($this->mapping) . " fields.");
    }

    /**
     * Main mapping function: builds an XML element from FHIR data.
     */
    public function map(array $fhir, DOMDocument $doc): DOMElement
    {
        if (empty($this->mapping)) {
            throw new \RuntimeException("No field mappings loaded for " . static::class);
        }

        // Root element like <Patient>, <Claim>, etc.
        $element = $doc->createElement($this->getRootElementName());

        // Groupings like <Address> or <Qualification>
        $groups = [];

        // Split into flat mappings and looped (e.g., item[], diagnosis[])
        $regularMappings = [];
        $loopMappings = [];

        foreach ($this->mapping as $fhirPath => $xmlTag) {
            if (preg_match('/^(\w+)\[\]\.(.+)/', $fhirPath, $matches)) {
                $arrayName = $matches[1]; // e.g., "item"
                $subPath = $matches[2];   // e.g., "code"
                $loopMappings[$arrayName][] = [$subPath, $xmlTag];
            } else {
                $regularMappings[$fhirPath] = $xmlTag;
            }
        }

        // --- Flat fields and grouped fields ---
        foreach ($regularMappings as $fhirPath => $xmlTag) {
            $value = $this->extractValue($fhir, $fhirPath);

            if ($value === null) {
                error_log("Missing value for $fhirPath → $xmlTag");
                continue;
            }

            // Handle grouped output like "Address.City"
            if (strpos($xmlTag, '.') !== false) {
                [$groupName, $childTag] = explode('.', $xmlTag, 2);

                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = $doc->createElement($groupName);
                }

                $groups[$groupName]->appendChild(
                    $doc->createElement($childTag, htmlspecialchars($value))
                );
            } else {
                // Regular flat field
                $element->appendChild(
                    $doc->createElement($xmlTag, htmlspecialchars($value))
                );
            }
        }

        // --- Repeating structures like item[], diagnosis[] ---
        foreach ($loopMappings as $arrayField => $fieldMappings) {
            if (!isset($fhir[$arrayField]) || !is_array($fhir[$arrayField])) {
                error_log("Expected array for $arrayField, but not found.");
                continue;
            }

            foreach ($fhir[$arrayField] as $i => $entry) {
                $parentTag = null;
                $container = $doc->createElement("Loop$i"); // fallback

                foreach ($fieldMappings as [$subPath, $xmlTag]) {
                    $value = $this->extractValue($entry, $subPath);
                    if ($value === null) continue;

                    // Split the XML tag to find parent + child (e.g., "Charge.CPTCode")
                    $parts = explode('.', $xmlTag, 2);
                    if (!$parentTag) {
                        $parentTag = $parts[0];
                        $container = $doc->createElement($parentTag);
                    }

                    $childTag = $parts[1] ?? $xmlTag;
                    $container->appendChild(
                        $doc->createElement($childTag, htmlspecialchars($value))
                    );
                }

                $element->appendChild($container);
            }
        }

        // Append all group elements (like <Address>) at the end
        foreach ($groups as $groupElement) {
            $element->appendChild($groupElement);
        }

        return $element;
    }

    /**
     * Subclasses define the root tag for each mapped object.
     */
    abstract protected function getRootElementName(): string;

    /**
     * Extracts a deeply nested value from a FHIR structure using bracket-dot notation.
     * Example: "address[0].city"
     */
    protected function extractValue(array $data, string $path)
    {
        // Split path on dots (but keep [0] together)
        $segments = preg_split('/\.(?![^\[]*\])/', $path);

        foreach ($segments as $segment) {
            if (preg_match('/(\w+)\[(\d+)\]/', $segment, $matches)) {
                $key = $matches[1];
                $index = (int) $matches[2];

                if (!isset($data[$key][$index])) {
                    return null;
                }

                $data = $data[$key][$index];
            } else {
                if (!isset($data[$segment])) {
                    return null;
                }

                $data = $data[$segment];
            }
        }

        return is_array($data) ? null : $data;
    }
}
