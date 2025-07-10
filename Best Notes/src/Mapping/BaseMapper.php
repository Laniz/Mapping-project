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
     * Loads and parses the mapping JSON config file.
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
     * Builds the full XML DOM element for this resource type.
     */
    public function map(array $fhir, DOMDocument $doc): DOMElement
    {
        if (empty($this->mapping)) {
            throw new \RuntimeException("No field mappings loaded for " . static::class);
        }

        $element = $doc->createElement($this->getRootElementName());
        $groups = []; // holds <Address> or <Qualification> type blocks

        $regularMappings = [];
        $loopMappings = [];

        // Separate loop-based mappings like item[].code from flat ones
        foreach ($this->mapping as $fhirPath => $xmlTag) {
            if (preg_match('/^(\w+)\[\]\.(.+)/', $fhirPath, $matches)) {
                $arrayName = $matches[1];
                $subPath = $matches[2];
                $loopMappings[$arrayName][] = [$subPath, $xmlTag];
            } else {
                $regularMappings[$fhirPath] = $xmlTag;
            }
        }

        /**
         * Handle flat and grouped elements
         */
        foreach ($regularMappings as $fhirPath => $xmlTag) {
            $value = $this->extractValue($fhir, $fhirPath);
            if ($value === null) {
                error_log("Missing value for $fhirPath → $xmlTag");
                continue;
            }

            // Format as MM/DD/YYYY if tag or path indicates a date
            if (
                preg_match('/(?:date|birthDate|start|end)$/i', $fhirPath) ||
                preg_match('/(?:date|birthDate|start|end)$/i', $xmlTag)
            ) {
                $value = $this->formatDate((string) $value);
            }

            // Flag FHIR references for deferred resolution
            $isReference = str_ends_with(strtolower($fhirPath), 'reference') || str_ends_with(strtolower($xmlTag), 'reference');

            // Handle grouped tags like "Address.City"
            if (strpos($xmlTag, '.') !== false) {
                [$groupName, $childTag] = explode('.', $xmlTag, 2);

                if (!isset($groups[$groupName])) {
                    $groups[$groupName] = $doc->createElement($groupName);
                }

                $field = $doc->createElement($childTag, htmlspecialchars($value));
                if ($isReference) {
                    $field->setAttribute("resolveLater", "true");
                }

                $groups[$groupName]->appendChild($field);
            } else {
                // Flat, non-grouped field
                $field = $doc->createElement($xmlTag, htmlspecialchars($value));
                if ($isReference) {
                    $field->setAttribute("resolveLater", "true");
                }

                $element->appendChild($field);
            }
        }

        /**
         * Handle looped structures like diagnosis[], item[]
         */
        foreach ($loopMappings as $arrayField => $fieldMappings) {
            if (!isset($fhir[$arrayField]) || !is_array($fhir[$arrayField])) {
                error_log("Expected array for $arrayField, but not found.");
                continue;
            }

            foreach ($fhir[$arrayField] as $i => $entry) {
                $parentTag = null;
                $container = $doc->createElement("Loop$i"); // fallback tag name

                foreach ($fieldMappings as [$subPath, $xmlTag]) {
                    $value = $this->extractValue($entry, $subPath);
                    if ($value === null) continue;

                    // Format dates
                    if (
                        preg_match('/(?:date|birthDate|start|end)$/i', $subPath) ||
                        preg_match('/(?:date|birthDate|start|end)$/i', $xmlTag)
                    ) {
                        $value = $this->formatDate((string) $value);
                    }

                    $isReference = str_ends_with(strtolower($subPath), 'reference') || str_ends_with(strtolower($xmlTag), 'reference');

                    // Parent tag is e.g. "Charge" or "Diagnosis"
                    $parts = explode('.', $xmlTag, 2);
                    if (!$parentTag) {
                        $parentTag = $parts[0];
                        $container = $doc->createElement($parentTag);
                    }

                    $childTag = $parts[1] ?? $xmlTag;
                    $field = $doc->createElement($childTag, htmlspecialchars($value));
                    if ($isReference) {
                        $field->setAttribute("resolveLater", "true");
                    }

                    $container->appendChild($field);
                }

                $element->appendChild($container);
            }
        }

        // Attach any <Address>, <Qualification>, etc. groups
        foreach ($groups as $groupElement) {
            $element->appendChild($groupElement);
        }

        return $element;
    }

    /**
     * Subclasses must define the root XML tag (e.g. "Patient", "Claim").
     */
    abstract protected function getRootElementName(): string;

    /**
     * Dotted/bracketed path parser for FHIR paths like "item[0].code"
     */
    protected function extractValue(array $data, string $path)
    {
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

    /**
     * Converts ISO/FHIR dates into MM/DD/YYYY format
     */
    protected function formatDate(string $input): string
    {
        try {
            $dt = new \DateTime($input);
            return $dt->format('m/d/Y');
        } catch (\Exception $e) {
            return $input;
        }
    }
}
