<?php

namespace App\Mapping;

use DOMDocument;
use DOMElement;
use DateTime;

/**
 * Assembles a full <Claim> structure using all resource mappers.
 */
class CompositeClaimMapper
{
    private PatientMapper $patientMapper;
    private PractitionerMapper $practitionerMapper;
    private OrganizationMapper $organizationMapper;
    private CoverageMapper $coverageMapper;
    private ClaimMapper $claimMapper;

    public function __construct()
    {
        $this->patientMapper = new PatientMapper();
        $this->practitionerMapper = new PractitionerMapper();
        $this->organizationMapper = new OrganizationMapper();
        $this->coverageMapper = new CoverageMapper();
        $this->claimMapper = new ClaimMapper();
    }

    /**
     * Main entry point to build the complete ns2:Claim.
     */
    public function mapAll(
        array $claimData,
        array $patientData,
        array $coverageData,
        array $practitionerData,
        array $organizationData,
        DOMDocument $doc
    ): DOMElement {
        $claimElement = $doc->createElement("ns2:Claim");
        $claimElement->setAttribute("xmlns:ns2", "http://www.collaboratemd.com/api/v1/");

        // Add Claim-level identifier
        $otherId = $claimData["id"] ?? "CLM-UNSPECIFIED";
        $claimElement->appendChild($doc->createElement("OtherIdentifier", $otherId));

        // Build and attach <Patient> + embedded <Policy> blocks
        $patientElement = $this->patientMapper->map($patientData, $doc);

        $insuranceEntries = $claimData["insurance"] ?? [];
        $index = 1;
        foreach ($insuranceEntries as $insurance) {
            $policyElement = $this->buildPolicy(
                $coverageData,
                $patientData,
                $organizationData,
                $doc,
                $index++
            );
            $patientElement->appendChild($policyElement);
        }

        $claimElement->appendChild($patientElement);

        // Append the remaining mapped blocks
        $claimElement->appendChild($this->coverageMapper->map($coverageData, $doc));
        $claimElement->appendChild($this->practitionerMapper->map($practitionerData, $doc));
        $claimElement->appendChild($this->organizationMapper->map($organizationData, $doc));
        $claimElement->appendChild($this->claimMapper->map($claimData, $doc));

        return $claimElement;
    }

    /**
     * Builds a single <Policy> block to embed under <Patient>.
     */
    private function buildPolicy(
        array $coverage,
        array $patient,
        array $organization,
        DOMDocument $doc,
        int $index
    ): DOMElement {
        $policy = $doc->createElement("Policy");

        $policy->appendChild($doc->createElement("Index", (string) $index));
        $policy->appendChild($doc->createElement("MemberID", "MEM183468"));
        $policy->appendChild($doc->createElement("GroupID", "GRP1238946"));
        $policy->appendChild($doc->createElement("Authorization", "X12307383"));

        // Build <Insured>
        $insured = $doc->createElement("Insured");
        $insured->appendChild($doc->createElement("Relationship", "1"));
        $insured->appendChild($doc->createElement("LastName", $patient["name"][0]["family"] ?? ""));
        $insured->appendChild($doc->createElement("FirstName", $patient["name"][0]["given"][0] ?? ""));
        $insured->appendChild($doc->createElement("Gender", $patient["gender"] ?? ""));
        $insured->appendChild($doc->createElement("BirthDate", $this->formatDate($patient["birthDate"] ?? "")));

        // Optional address
        if (!empty($patient["address"][0])) {
            $addr = $patient["address"][0];
            $address = $doc->createElement("Address");
            $address->appendChild($doc->createElement("Line1", $addr["line"][0] ?? ""));
            $address->appendChild($doc->createElement("City", $addr["city"] ?? ""));
            $address->appendChild($doc->createElement("State", $addr["state"] ?? ""));
            $address->appendChild($doc->createElement("Zipcode", $addr["postalCode"] ?? ""));
            $insured->appendChild($address);
        }

        $policy->appendChild($insured);

        // Build <Payer>
        $payer = $doc->createElement("Payer");
        $payer->appendChild($doc->createElement("Name", $organization["name"] ?? ""));

        if (!empty($organization["address"][0])) {
            $addr = $organization["address"][0];
            $address = $doc->createElement("Address");
            $address->appendChild($doc->createElement("Line1", $addr["line"][0] ?? ""));
            $address->appendChild($doc->createElement("City", $addr["city"] ?? ""));
            $address->appendChild($doc->createElement("State", $addr["state"] ?? ""));
            $address->appendChild($doc->createElement("Zipcode", $addr["postalCode"] ?? ""));
            $payer->appendChild($address);
        }

        $policy->appendChild($payer);

        // Effective and Termination Dates (formatted)
        if (isset($coverage["period"])) {
            $policy->appendChild($doc->createElement("EffectiveDate", $this->formatDate($coverage["period"]["start"] ?? "")));
            $policy->appendChild($doc->createElement("TerminationDate", $this->formatDate($coverage["period"]["end"] ?? "")));
        }

        return $policy;
    }

    /**
     * Converts a date string (YYYY-MM-DD or ISO) to MM/DD/YYYY.
     */
    private function formatDate(string $input): string
    {
        try {
            $dt = new DateTime($input);
            return $dt->format("m/d/Y");
        } catch (\Exception $e) {
            return $input; // Return as-is if parsing fails
        }
    }
}
