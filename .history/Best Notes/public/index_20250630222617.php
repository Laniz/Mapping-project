<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;
use App\Mapping\CoverageMapper;
use App\Mapping\EncounterMapper;
use App\Mapping\ClaimMapper;

// Initialize all mappers
$patientMapper = new PatientMapper();
$coverageMapper = new CoverageMapper();
$encounterMapper = new EncounterMapper();
$claimMapper = new ClaimMapper();

// Create new XML document
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Input paths
$inputPath = __DIR__ . '/../FHIR-Patient-Full.json'; 
$inputPath2 = __DIR__ . '/../coverage_mapping.json';
$inputPath3 = __DIR__ . '/../Encounter-example.json';
$inputPath4 = __DIR__ . '/../FHIR-CLAIM.json'; 

// Load files
$json = file_get_contents($inputPath);
$json2 = file_get_contents($inputPath2);
$json3 = file_get_contents($inputPath3);
$json4 = file_get_contents($inputPath4);

// Decode JSON into PHP arrays
$data = json_decode($json, true);
$data2 = json_decode($json2, true);
$data3 = json_decode($json3, true);
$data4 = json_decode($json4, true);

// Create root XML element
$root = $doc->createElement("AllMaps");

// Validate input files
foreach (['Patient' => $data, 'Coverage' => $data2, 'Encounter' => $data3, 'Claim' => $data4] as $type => $resource) {
    if (!is_array($resource) || !isset($resource['resourceType'])) {
        die("Invalid FHIR input for $type: missing or malformed 'resourceType'\n");
    }
}

// Map and append all resources
$root->appendChild($patientMapper->map($data, $doc));
$root->appendChild($coverageMapper->map($data2, $doc));
$root->appendChild($encounterMapper->map($data3, $doc));
$root->appendChild($claimMapper->map($data4, $doc));

// Output final XML
$doc->appendChild($root);
echo $doc->saveXML();
