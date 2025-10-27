<?php

namespace gorriecoe\Link\Extensions;

use gorriecoe\Link\Models\Link;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;

/**
 * Add sitetree type to link field
 *
 * @package silverstripe-link
 * @extends \SilverStripe\Core\Extension<static>
 */
class AutomaticMarkupID extends Extension
{
    /**
     * Renders an HTML ID attribute for this link
     */
    public function updateIDValue(&$id)
    {
        $owner = $this->getOwner();
        if (($owner instanceof Link) && $owner->Title) {
            $id = Convert::raw2url($owner->Title ?? '');
        }
    }
}
