<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Mapping\PatientMapper;

$json = file_get_contents(__DIR__ . '/../FHIR-Multi-Patient.json');
$bundle = json_decode($json, true);

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

$mapper = new PatientMapper();

$root = $doc->createElement("Patients");

foreach ($bundle['entry'] as $entry) {
    if (($entry['resource']['resourceType'] ?? '') === 'Patient') {
        $patientNode = $mapper->map($entry['resource'], $doc);
        $root->appendChild($patientNode);
    }
}

$doc->appendChild($root);
echo $doc->saveXML();
