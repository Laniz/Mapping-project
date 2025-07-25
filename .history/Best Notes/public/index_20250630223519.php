<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;
use App\Mapping\EncounterMapper;
use App\Mapping\ClaimMapper;

// Initialize only the mappers you’re using
$patientMapper = new PatientMapper();
$encounterMapper = new EncounterMapper();
$claimMapper = new ClaimMapper();

// Create new XML document
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Input paths
$inputPath = __DIR__ . '/../FHIR-Patient-Full.json'; 
$inputPath2 = __DIR__ . '/../Encounter-example.json';
$inputPath3 = __DIR__ . '/../FHIR-CLAIM.json'; 

// Load and decode files
$data = json_decode(file_get_contents($inputPath), true);
$data2 = json_decode(file_get_contents($inputPath2), true);
$data3 = json_decode(file_get_contents($inputPath3), true);

// Create root XML element
$root = $doc->createElement("AllMaps");

// Validate input files
foreach (['Patient' => $data, 'Encounter' => $data2, 'Claim' => $data3] as $type => $resource) {
    if (!is_array($resource) || !isset($resource['resourceType'])) {
        die("Invalid FHIR input for $type: missing or malformed 'resourceType'\\n");
    }
}

// Map and append all resources
$root->appendChild($patientMapper->map($data, $doc));
$root->appendChild($encounterMapper->map($data2, $doc));
$root->appendChild($claimMapper->map($data3, $doc));

// Output final XML
$doc->appendChild($root);
echo $doc->saveXML();
