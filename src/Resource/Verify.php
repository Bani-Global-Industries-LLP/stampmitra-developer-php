<?php

declare(strict_types=1);

namespace StampMitra\Resource;

use StampMitra\Http\RequestOptions;
use StampMitra\Model\BankAccountVerification;
use StampMitra\Model\CinVerification;
use StampMitra\Model\DrivingLicenseVerification;
use StampMitra\Model\GstinVerification;
use StampMitra\Model\PanToGstin;
use StampMitra\Model\PanToUdyam;
use StampMitra\Model\PanVerification;
use StampMitra\Model\PassportVerification;
use StampMitra\Model\RcVerification;
use StampMitra\Model\UdyamVerification;
use StampMitra\Model\VoterIdVerification;
use StampMitra\Support\Validators as V;

/**
 * Identity verification. Every call is metered, including negative results
 * (valid=false is still a success response and is billed). An Idempotency-Key
 * is generated per call and reused across automatic retries, so a retry
 * replays the original result instead of billing twice.
 *
 * Each method takes the documented fields and a trailing $options argument
 * (RequestOptions or array).
 */
final class Verify extends AbstractResource
{
    /** @param RequestOptions|array<string, mixed>|null $options */
    public function pan(string $pan, ?string $name = null, RequestOptions|array|null $options = null): PanVerification
    {
        if ($this->validateInput) {
            $pan = V::pan($pan);
            $name = $name === null ? null : V::name($name);
        }

        return $this->call(PanVerification::class, 'POST', '/v1/verify/pan', $this->compact(['pan' => $pan, 'name' => $name]), [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function gstin(string $gstin, ?string $businessName = null, RequestOptions|array|null $options = null): GstinVerification
    {
        if ($this->validateInput) {
            $gstin = V::gstin($gstin);
            $businessName = $businessName === null ? null : V::name($businessName, 'businessName');
        }

        return $this->call(GstinVerification::class, 'POST', '/v1/verify/gstin', $this->compact(['gstin' => $gstin, 'businessName' => $businessName]), [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function rc(string $vehicleNumber, RequestOptions|array|null $options = null): RcVerification
    {
        if ($this->validateInput) {
            $vehicleNumber = V::vehicleNumber($vehicleNumber);
        }

        return $this->call(RcVerification::class, 'POST', '/v1/verify/rc', ['vehicleNumber' => $vehicleNumber], [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function voterId(string $voterId, ?string $name = null, RequestOptions|array|null $options = null): VoterIdVerification
    {
        if ($this->validateInput) {
            $voterId = V::voterId($voterId);
            $name = $name === null ? null : V::name($name);
        }

        return $this->call(VoterIdVerification::class, 'POST', '/v1/verify/voter-id', $this->compact(['voterId' => $voterId, 'name' => $name]), [], $options, true);
    }

    /**
     * Needs an IP-allowlisted key; otherwise a PermissionError carries the server message.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function drivingLicense(string $dlNumber, string $dob, RequestOptions|array|null $options = null): DrivingLicenseVerification
    {
        if ($this->validateInput) {
            $dlNumber = V::drivingLicense($dlNumber);
            $dob = V::date($dob);
        }

        return $this->call(DrivingLicenseVerification::class, 'POST', '/v1/verify/driving-license', ['dlNumber' => $dlNumber, 'dob' => $dob], [], $options, true);
    }

    /**
     * Needs an IP-allowlisted key; otherwise a PermissionError carries the server message.
     *
     * @param RequestOptions|array<string, mixed>|null $options
     */
    public function passport(string $fileNumber, string $dob, ?string $name = null, RequestOptions|array|null $options = null): PassportVerification
    {
        if ($this->validateInput) {
            $fileNumber = V::passportFileNumber($fileNumber);
            $dob = V::date($dob);
            $name = $name === null ? null : V::name($name);
        }

        return $this->call(PassportVerification::class, 'POST', '/v1/verify/passport', $this->compact(['fileNumber' => $fileNumber, 'dob' => $dob, 'name' => $name]), [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function cin(string $cin, RequestOptions|array|null $options = null): CinVerification
    {
        if ($this->validateInput) {
            $cin = V::cin($cin);
        }

        return $this->call(CinVerification::class, 'POST', '/v1/verify/cin', ['cin' => $cin], [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function udyam(string $udyamNumber, RequestOptions|array|null $options = null): UdyamVerification
    {
        if ($this->validateInput) {
            $udyamNumber = V::udyam($udyamNumber);
        }

        return $this->call(UdyamVerification::class, 'POST', '/v1/verify/udyam', ['udyamNumber' => $udyamNumber], [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function panToGstin(string $pan, RequestOptions|array|null $options = null): PanToGstin
    {
        if ($this->validateInput) {
            $pan = V::pan($pan);
        }

        return $this->call(PanToGstin::class, 'POST', '/v1/verify/pan-to-gstin', ['pan' => $pan], [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function panToUdyam(string $pan, RequestOptions|array|null $options = null): PanToUdyam
    {
        if ($this->validateInput) {
            $pan = V::pan($pan);
        }

        return $this->call(PanToUdyam::class, 'POST', '/v1/verify/pan-to-udyam', ['pan' => $pan], [], $options, true);
    }

    /** @param RequestOptions|array<string, mixed>|null $options */
    public function bankAccount(string $accountNumber, string $ifsc, ?string $name = null, ?string $phone = null, RequestOptions|array|null $options = null): BankAccountVerification
    {
        if ($this->validateInput) {
            $accountNumber = V::bankAccount($accountNumber);
            $ifsc = V::ifsc($ifsc);
            $name = $name === null ? null : V::name($name);
            $phone = $phone === null ? null : V::phone($phone);
        }

        return $this->call(BankAccountVerification::class, 'POST', '/v1/verify/bank-account', $this->compact(['accountNumber' => $accountNumber, 'ifsc' => $ifsc, 'name' => $name, 'phone' => $phone]), [], $options, true);
    }
}
