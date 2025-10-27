<?php

namespace gorriecoe\Link\Extensions;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use gorriecoe\Link\View\Phone;

/**
 * Adds methods to DBString to help manipulate the output suitable for links
 *
 * @package silverstripe-link
 */
class DBStringLink extends Extension
{
    /**
     * Provides string replace to allow link friendly urls
     */
    public function LinkFriendly(): string
    {
        return Convert::raw2url($this->owner->value);
    }

    /**
     * @alias LinkFriendly
     */
    public function URLFriendly(): string
    {
        return $this->LinkFriendly();
    }

    /**
     * Provides string replace to allow phone number friendly urls
     */
    public function PhoneFriendly(): string
    {
        $value = $this->owner->value;
        if ($value) {
            return Phone::create($value);
        } else {
            return '';
        }
    }
}
