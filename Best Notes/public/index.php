<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;
use App\Mapping\EncounterMapper;
use App\Mapping\ClaimMapper;
use App\Mapping\PractitionerMapper;
use App\Mapping\OrganizationMapper;
use App\Mapping\CoverageMapper;

// Initialize all required mappers
$patientMapper = new PatientMapper();
$encounterMapper = new EncounterMapper();
$claimMapper = new ClaimMapper();
$coverageMapper = new CoverageMapper();
$practitionerMapper = new PractitionerMapper();
$organizationMapper = new OrganizationMapper();

// Create new XML document
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Input paths to actual FHIR data
$inputPath1 = __DIR__ . '/../FHIR-Patient-Full.json';
$inputPath2 = __DIR__ . '/../Encounter-example.json';
$inputPath3 = __DIR__ . '/../FHIR-CLAIM.json';
$inputPath4 = __DIR__ . '/../FHIR-Practitioner-example-behavioral-health.json';
$inputPath5 = __DIR__ . '/../FHIR-Organization-example.json';
$inputPath6 = __DIR__ . '/../FHIR-Coverage-example.json';

// Load and decode FHIR JSON files
$data1 = json_decode(file_get_contents($inputPath1), true); // Patient
$data2 = json_decode(file_get_contents($inputPath2), true); // Encounter
$data3 = json_decode(file_get_contents($inputPath3), true); // Claim
$data4 = json_decode(file_get_contents($inputPath4), true); // Practitioner
$data5 = json_decode(file_get_contents($inputPath5), true); // Organization
$data6 = json_decode(file_get_contents($inputPath6), true); // Coverage


// Create root XML element
$root = $doc->createElement("AllMaps");

// Map and append all resources using their mappers
$root->appendChild($patientMapper->map($data1, $doc));
$root->appendChild($encounterMapper->map($data2, $doc));
$root->appendChild($claimMapper->map($data3, $doc));
$root->appendChild($practitionerMapper->map($data4, $doc));
$root->appendChild($organizationMapper->map($data5, $doc));
$root->appendChild($coverageMapper->map($data6, $doc));

// Append and output final XML
$doc->appendChild($root);
echo $doc->saveXML();
