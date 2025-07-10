<?php

use Symfony\Component\Serializer\Encoder\JsonEncoder;

class PatientMapper
{
    public function mapToHL7Pid(array $fhirPatient): string
    {
        // PID-3: Patient Identifier (MRN preferred)
        $mrn = $this->getIdentifier($fhirPatient, 'MR');
        $ssn = $this->getIdentifier($fhirPatient, 'SSN');

        // PID-5: Patient Name
        $name = $fhirPatient['name'][0] ?? [];
        $family = $name['family'] ?? '';
        $given = $name['given'][0] ?? '';
        $prefix = $name['prefix'][0] ?? '';
        $fullName = "$family^$given^^$prefix";

        // PID-7: Date of Birth
        $dob = $fhirPatient['birthDate'] ?? '';
        $dob = str_replace('-', '', $dob); // Format as YYYYMMDD

        // PID-8: Gender
        $gender = $this->mapGender($fhirPatient['gender'] ?? '');

        // PID-11: Address
        $address = $fhirPatient['address'][0] ?? [];
        $street = implode(' ', $address['line'] ?? []);
        $city = $address['city'] ?? '';
        $state = $address['state'] ?? '';
        $zip = $address['postalCode'] ?? '';
        $country = $address['country'] ?? '';
        $pid11 = "$street^^$city^$state^$zip^$country";

        // PID-13: Home Phone
        $homePhone = $this->getTelecom($fhirPatient, 'phone', 'home');

        // PID-14: Work Email as fallback
        $workEmail = $this->getTelecom($fhirPatient, 'email', 'work');

        // PID-16: Marital Status
        $maritalStatus = $fhirPatient['maritalStatus']['coding'][0]['code'] ?? '';

        // PID-19: SSN
        $pid19 = $ssn;

        // Build PID segment
        $pid = [
            'PID',                // Segment ID
            '',                   // PID-1 (Set ID)
            '',                   // PID-2 (External ID)
            $mrn,                 // PID-3 (MRN)
            '',                   // PID-4
            $fullName,            // PID-5
            '',                   // PID-6 (Mother's maiden name)
            $dob,                 // PID-7
            $gender,              // PID-8
            '', '', '',           // PID-9 to PID-11
            $pid11,               // PID-11 (Address)
            $homePhone,           // PID-13
            $workEmail,           // PID-14
            '', '',               // PID-15-16
            $maritalStatus,       // PID-16
            '', '', '',           // PID-17 to PID-19
            $pid19                // PID-19 (SSN)
        ];

        return implode('|', $pid);
    }

    private function getIdentifier(array $patient, string $type): string
    {
        foreach ($patient['identifier'] ?? [] as $id) {
            if (($type === 'MR' && ($id['type']['coding'][0]['code'] ?? '') === 'MR') ||
                ($type === 'SSN' && str_contains($id['system'] ?? '', 'ssn'))) {
                return $id['value'] ?? '';
            }
        }
        return '';
    }

    private function getTelecom(array $patient, string $system, string $use): string
    {
        foreach ($patient['telecom'] ?? [] as $contact) {
            if (($contact['system'] ?? '') === $system && ($contact['use'] ?? '') === $use) {
                return $contact['value'] ?? '';
            }
        }
        return '';
    }

    private function mapGender(string $gender): string
    {
        return match(strtolower($gender)) {
            'male' => 'M',
            'female' => 'F',
            'other' => 'O',
            'unknown' => 'U',
            default => ''
        };
    }
}
