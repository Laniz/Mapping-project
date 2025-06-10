<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/Mapping/MapperInterface.php';
require_once __DIR__ . '/../src/Mapping/PatientMapper.php';

use App\Mapping\PatientMapper;

$json = file_get_contents(__DIR__ . '/../FHIR-Patient-example.json');
$fhir = json_decode($json, true);

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

$mapper = new PatientMapper();
$patientElement = $mapper->map($fhir, $doc);

$doc->appendChild($patientElement);
echo $doc->saveXML();
