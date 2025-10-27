<?php

namespace gorriecoe\Link\Extensions;

use gorriecoe\Link\Models\Link;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Core\Extension;
use UncleCheese\DisplayLogic\Forms\Wrapper;

if (!class_exists(SiteTree::class)) {
    return;
}

/**
 * Add sitetree type to link object
 *
 * @package silverstripe-link
 *
 * @property int $SiteTreeID
 * @property ?string $Anchor
 * @method mixed SiteTree()
 * @extends \SilverStripe\Core\Extension<static>
 */
class LinkSiteTree extends Extension
{
    /**
     * Database fields
     */
    private static array $db = [
        'Anchor' => 'Varchar(255)',
    ];

    /**
     * Has_one relationship
     */
    private static array $has_one = [
        // @phpstan-ignore class.notFound
        'SiteTree' => SiteTree::class,
    ];

    /**
     * A map of object types that can be linked to
     * Custom dataobjects can be added to this
     **/
    private static array $types = [
        'SiteTree' => 'Page on this website',
    ];

    /**
     * Defines the label used in the sitetree dropdown.
     * @param string $sitetree_field_label
     */
    private static string $sitetree_field_label = 'MenuTitle';

    /**
     * Update Fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        if (class_exists(SiteTree::class) && ($owner instanceof Link)) {

            $sitetree_field_label = Config::inst()->get($owner::class, 'sitetree_field_label') ?: 'MenuTitle';

            // Insert site tree field after the file selection field
            $fields->insertAfter(
                'Type',
                Wrapper::create(
                    $sitetreeField = TreeDropdownField::create(
                        'SiteTreeID',
                        _t(self::class . '.PAGE', 'Page'),
                        SiteTree::class
                    )
                    ->setTitleField($sitetree_field_label),
                    TextField::create(
                        'Anchor',
                        _t(self::class . '.ANCHOR', 'Anchor/Querystring')
                    )
                    ->setDescription(_t(self::class . '.ANCHORINFO', 'Include # at the start of your anchor name or, ? at the start of your querystring'))
                )
                ->displayIf('Type')->isEqualTo('SiteTree')->end()
            );

            // Display warning if the selected page is deleted or unpublished
            $siteTree = $owner->SiteTree();
            if ($siteTree->isInDB() && !$siteTree->isPublished()) {
                $sitetreeField->setDescription(_t(self::class . '.DELETEDWARNING', 'Warning: The selected page appears to have been deleted or unpublished. This link may not appear or may be broken in the frontend'));
            }
        }
    }

    public function updateIsCurrent(&$status): void
    {
        $owner = $this->getOwner();
        if (
            class_exists(SiteTree::class) &&
            ($owner instanceof Link) &&
            $owner->Type == 'SiteTree'
        ) {
            $currentPage = $owner->getCurrentPage();
            if ($currentPage instanceof SiteTree) {
                $status = $currentPage === $owner->SiteTree() || $currentPage->ID === $owner->SiteTreeID;
            }
        }
    }

    public function updateIsSection(&$status): void
    {
        $owner = $this->getOwner();
        if (
            class_exists(SiteTree::class) &&
            ($owner instanceof Link) &&
            $owner->Type == 'SiteTree'
        ) {
            $currentPage = $owner->getCurrentPage();
            if ($currentPage instanceof SiteTree) {
                $status = $owner->isCurrent() || in_array($owner->SiteTreeID, $currentPage->getAncestors()->column());
            }
        }
    }

    public function updateIsOrphaned(&$status): void
    {
        $owner = $this->getOwner();
        if (
            class_exists(SiteTree::class) &&
            ($owner instanceof Link) &&
            $owner->Type == 'SiteTree'
        ) {
            $currentPage = $owner->getCurrentPage();
            if ($currentPage instanceof SiteTree) {
                // Always false for root pages
                if (empty($owner->SiteTree()->ParentID)) {
                    $status = false;
                } else {
                    // Parent must exist and not be an orphan itself
                    $parent = $owner->Parent();
                    $status = !$parent || !$parent->exists() || $parent->isOrphaned();
                }
            }
        }
    }
}
