<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;

// Load FHIR bundle with multiple patients
$json = file_get_contents(__DIR__ . '/../FHIR-Multi-Patient.json');
// this makes an array if true, an object if false
$bundle = json_decode($json, true);

// Create a new XML document
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Initialize the PatientMapper
$mapper = new PatientMapper();

// Create a root element to hold all patients
$root = $doc->createElement("Patients");

// Loop through each entry in the FHIR Bundle
foreach ($bundle['entry'] as $entry) {
    $resource = $entry['resource'] ?? [];
    if (($resource['resourceType'] ?? '') === 'Patient') {
        // Convert Patient resource to XML and append to root
        $patientNode = $mapper->map($resource, $doc);
        $root->appendChild($patientNode);
    }
}

// Append root to document and output the result
$doc->appendChild($root);
echo $doc->saveXML();
