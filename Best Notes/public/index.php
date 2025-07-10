<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\CompositeClaimMapper;

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

// Initialize composite mapper
$compositeMapper = new CompositeClaimMapper();

// Build single <ns2:Claim> block
$combinedClaim = $compositeMapper->mapAll($data3, $data1, $data6, $data4, $data5, $doc);

// Append to XML root
$doc->appendChild($combinedClaim);

// Output to console
echo $doc->saveXML();

// Save to file
$outputPath = __DIR__ . '/../output/claim-output.xml';

if (!is_dir(dirname($outputPath))) {
    mkdir(dirname($outputPath), 0777, true);
}

// $doc->save($outputPath);
// echo " XML saved to: $outputPath\n";

// Save to file
$outputPath = __DIR__ . '/../output/claim-output.xml';

if (!is_dir(dirname($outputPath))) {
    mkdir(dirname($outputPath), 0777, true); // Create output/ directory if it doesn't exist
}

$doc->save($outputPath);

echo "XML saved to: $outputPath\n";