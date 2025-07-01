<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;
use App\Mapping\CoverageMapper;
use App\Mapping\EncounterMapper;
use

// Initialize the PatientMapper (modular class that converts FHIR to XML)
$patientMapper = new PatientMapper();

$coverageMapper = new CoverageMapper();
$encounterMapper = new EncounterMapper();

// Create a new XML document that will hold the output
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Path to the input JSON file (can be a single Patient or a Bundle)
$inputPath = __DIR__ . '/../FHIR-Patient-Full.json'; 

$inputPath2 = __DIR__ . '/../FHIR-Coverage-example.json';
$inputPath3 = __DIR__ . '/../Encounter-example.json';

// or change to FHIR-Patient-example.json, FHIR-Patient-example.json

// Load FHIR data from file as JSON text
$json = file_get_contents($inputPath);

$json2 = file_get_contents($inputPath2);
$json3 = file_get_contents($inputPath3);

// this makes an array if true, an object if false
$data = json_decode($json, true);

$data2 = json_decode($json2, true);
$data3 = json_decode($json3, true);

// Create the root XML node that will wrap all patients
$root = $doc->createElement("AllMaps");

// Validate and branch depending on input type
if (!is_array($data) || !isset($data['resourceType'])) {
    // Basic file/data validation
    die("Invalid FHIR input file: missing or malformed 'resourceType'\n");
}

if (!is_array($data2) || !isset($data2['resourceType'])) {
    // Basic file/data validation
    die("Invalid FHIR input file: missing or malformed 'resourceType'\n");
}
if (!is_array($data3) || !isset($data3['resourceType'])) {
    // Basic file/data validation
    die("Invalid FHIR input file: missing or malformed 'resourceType'\n");
}

// Detect whether we're dealing with a single Patient or a Bundle of Patients
//switch ($data['resourceType']) {
//    case 'Patient':
        // Map the single patient directly to XML and append
        $patientNode = $patientMapper->map($data, $doc);
        $root->appendChild($patientNode);

        $coverageNode = $coverageMapper->map($data2, $doc);
        $root->appendChild($coverageNode);
        $encounterNode = $encounterMapper->map($data3, $doc);
        $root->appendChild($encounterNode);
//        break;

/*    case 'Bundle':
        // Loop through all resources in the bundle
        foreach ($data['entry'] ?? [] as $entry) {
            $resource = $entry['resource'] ?? null;

            // Only process Patient resources
            if ($resource && $resource['resourceType'] === 'Patient') {
                // Convert Patient resource to XML and append to root
                $patientNode = $mapper->map($resource, $doc);
                $root->appendChild($patientNode);
            }
        }
        break;

    default:
        // Currently unsupported resource types
        die("Unsupported resourceType: " . $data['resourceType'] . "\n");
}
*/
// Append root node to document and print the resulting XML
$doc->appendChild($root);
echo $doc->saveXML();
