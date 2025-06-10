<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;
use App\Mapping\MapperInterface;

class PatientMapper implements MapperInterface
{
    public function map(array $fhir, DOMDocument $doc): DOMElement
    {
        $patient = $doc->createElement("Patient");

        $firstName = $fhir['name'][0]['given'][0] ?? '';
        $lastName = $fhir['name'][0]['family'] ?? '';
        $gender = strtoupper(substr($fhir['gender'] ?? 'U', 0, 1));
        $birthDate = $fhir['birthDate'] ?? null;
        $birthDateFormatted = $birthDate ? date("m/d/Y", strtotime($birthDate)) : '';

        $patient->appendChild($doc->createElement("FirstName", $firstName));
        $patient->appendChild($doc->createElement("LastName", $lastName));
        $patient->appendChild($doc->createElement("Gender", $gender));
        $patient->appendChild($doc->createElement("BirthDate", $birthDateFormatted));

        return $patient;
    }
}
