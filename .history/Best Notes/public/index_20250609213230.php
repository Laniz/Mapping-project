<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;

// Initialize the PatientMapper (modular class that converts FHIR to XML)
$mapper = new PatientMapper();

// Create a new XML document that will hold the output
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

// Path to the input JSON file (can be a single Patient or a Bundle)
$inputPath = __DIR__ . '/../FHIR-Multi-Patient.json'; // or change to FHIR-Patient-example.json

// Load FHIR data from file as JSON text
$json = file_get_contents($inputPath);

// this makes an array if true, an object if false
$data = json_decode($json, true);

// Create the root XML node that will wrap all patients
$root = $doc->createElement("Patients");

// Validate and branch depending on input type
if (!is_array($data) || !isset($data['resourceType'])) {
    // Basic file/data validation
    die("Invalid FHIR input file: missing or malformed 'resourceType'\n");
}

// Detect whether we're dealing with a single Patient or a Bundle of Patients
switch ($data['resourceType']) {
    case 'Patient':
        // Map the single patient directly to XML and append
        $patientNode = $mapper->map($data, $doc);
        $root->appendChild($patientNode);
        break;

    case 'Bundle':
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

// Append root node to document and print the resulting XML
$doc->appendChild($root);
echo $doc->saveXML();
