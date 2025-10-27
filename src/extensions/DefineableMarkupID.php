<?php

namespace gorriecoe\Link\Extensions;

use gorriecoe\Link\Models\Link;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Convert;

/**
 * Add sitetree type to link field
 *
 * @package silverstripe-link
 * @property ?string $IDCustomValue
 * @extends \SilverStripe\Core\Extension<static>
 */
class DefineableMarkupID extends Extension
{
    /**
     * Database fields
     */
    private static array $db = [
        'IDCustomValue' => 'Text'
    ];

    /**
     * Update Fields
     */
    public function updateCMSFields(FieldList $fields): FieldList
    {
        $fields->addFieldToTab(
            'Root.Main',
            TextField::create(
                'IDCustomValue',
                _t(self::class . '.ID', 'ID')
            )
            ->setDescription(_t(self::class . '.IDCUSTOMVALUE', 'Define an ID for the link.  This is particularly useful for google tracking.'))
        );
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        $owner = $this->getOwner();
        if ($owner instanceof Link) {
            $owner->IDCustomValue = Convert::raw2url($owner->IDCustomValue ?? '');
        }
    }

    /**
     * Renders an HTML ID attribute for this link
     */
    public function updateIDValue(&$id): void
    {
        $owner = $this->getOwner();
        if (($owner instanceof Link) && $owner->IDCustomValue) {
            $id = $owner->IDCustomValue;
        }
    }
}
