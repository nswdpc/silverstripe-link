<?php

namespace gorriecoe\Link\View;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use SilverStripe\Model\ModelData;

/**
 * @package silverstripe-link
 */
class Phone extends ModelData
{
    
    protected \libphonenumber\PhoneNumberUtil $library;

    protected \libphonenumber\PhoneNumber $instance;

    protected $phoneNumberFormat = PhoneNumberFormat::E164;

    /**
     * The country the user is dialing from.
     */
    protected string $fromCountry;

    private static string $default_country = 'NZ';

    public function __construct($phone)
    {
        $this->library = PhoneNumberUtil::getInstance();
        $country = $this->config()->get('default_country');
        $this->instance = $this->library->parse($phone, $country);
        parent::__construct();
    }

    /**
     * Format the phone number in international format.
     */
    public function International(): self
    {
        $this->phoneNumberFormat = PhoneNumberFormat::INTERNATIONAL;
        return $this;
    }

    /**
     * Format the phone number in national format.
     */
    public function National(): self
    {
        $this->phoneNumberFormat = PhoneNumberFormat::NATIONAL;
        return $this;
    }

    /**
     * Format the phone number in E164 format
     */
    public function E164(): self
    {
        $this->phoneNumberFormat = PhoneNumberFormat::E164;
        return $this;
    }

    /**
     * Format the phone number in RFC3966 format.
     */
    public function RFC3966(): self
    {
        $this->phoneNumberFormat = PhoneNumberFormat::RFC3966;
        return $this;
    }

    /**
     * Set the country to which the phone number belongs to.
     */
    public function To(string $country): self
    {
        $country = $this->library->getMetadataForRegion($country);
        $this->instance->setCountryCode($country->getCountryCode());
        return $this;
    }

    /**
     * Set the country the user is dialing from.
     */
    public function From(string $country): self
    {
        $this->fromCountry = $country;
        return $this;
    }

    /**
     * Sets whether this phone number uses a leading zero.
     *
     * @param bool $value True to use italian leading zero, false otherwise.
     */
    public function LeadingZero(bool $value = true): self
    {
        $this->instance->setItalianLeadingZero($value);
        return $this;
    }

    public function Render(): string
    {
        if ($this->fromCountry) {
            return $this->library->formatOutOfCountryCallingNumber(
                $this->instance,
                $this->fromCountry
            );
        } else {
            return $this->library->format(
                $this->instance,
                $this->phoneNumberFormat
            );
        }
    }

    public function forTemplate(): string
    {
        return $this->Render();
    }
}
