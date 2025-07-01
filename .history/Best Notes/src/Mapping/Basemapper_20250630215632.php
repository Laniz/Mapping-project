<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;

abstract class BaseMapper implements MapperInterface
{
    protected array $mapping;

    public function __construct(string $mappingFile)
    {
        $mappingPath = __DIR__ . '/../../' . $mappingFile;
        $json = file_get_contents($mappingPath);
        $this->mapping = json_decode($json, true);
    }

    public function map(array $fhir, DOMDocument $doc): DOMElement
    {
        $element = $doc->createElement($this->getRootElementName());

        foreach ($this->mapping as $fhirPath => $xmlTag) {
            $value = $this->extractValue($fhir, $fhirPath);
            if (!is_null($value)) {
                $child = $doc->createElement($xmlTag, htmlspecialchars($value));
                $element->appendChild($child);
            }
        }

        return $element;
    }

    abstract protected function getRootElementName(): string;

    protected function extractValue(array $data, string $path)
    {
        $segments = preg_split('/\.(?![^\[]*\])/', $path);
        foreach ($segments as $segment) {
            if (preg_match('/(\\w+)\\[(\\d+)\\]/', $segment, $matches)) {
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
