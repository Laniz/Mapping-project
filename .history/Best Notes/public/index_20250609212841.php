<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;

$mapper = new PatientMapper();
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

$inputPath = __DIR__ . '/../FHIR-Multi-Patient.json'; // or FHIR-Patient-example.json
$json = file_get_contents($inputPath);
$data = json_decode($json, true);

$root = $doc->createElement("Patients");

if (!is_array($data) || !isset($data['resourceType'])) {
    die("Invalid FHIR input file.\n");
}

switch ($data['resourceType']) {
    case 'Patient':
        $patientNode = $mapper->map($data, $doc);
        $root->appendChild($patientNode);
        break;

    case 'Bundle':
        foreach ($data['entry'] ?? [] as $entry) {
            $resource = $entry['resource'] ?? null;
            if ($resource && $resource['resourceType'] === 'Patient') {
                $patientNode = $mapper->map($resource, $doc);
                $root->appendChild($patientNode);
            }
        }
        break;

    default:
        die("Unsupported resourceType: " . $data['resourceType'] . "\n");
}

$doc->appendChild($root);
echo $doc->saveXML();
