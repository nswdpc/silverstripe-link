<?php

namespace gorriecoe\Link\Models;

use gorriecoe\Link\View\Phone;
use gorriecoe\Link\Extensions\LinkSiteTree;
use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Control\Director;
use SilverStripe\CMS\Controllers\ContentController;
use UncleCheese\DisplayLogic\Forms\Wrapper;
use SilverStripe\Assets\Folder;

/**
 * Link
 *
 * @package silverstripe-link
 *
 * @property string $Title
 * @property ?string $Type
 * @property ?string $URL
 * @property ?string $Email
 * @property ?string $Phone
 * @property bool $OpenInNewWindow
 * @property ?string $SelectedStyle
 * @property int $FileID
 * @method File File()
 * @mixin LinkSiteTree
 */
class Link extends DataObject
{
    /**
     * Defines the database table name
     */
    private static string $table_name = 'Link';

    /**
     * Database fields
     */
    private static array $db = [
        'Title' => 'Varchar',
        'Type' => 'Varchar(50)',
        'URL' => 'Text',
        'Email' => 'Varchar',
        'Phone' => 'Varchar(30)',
        'OpenInNewWindow' => 'Boolean',
        'SelectedStyle' => 'Varchar'
    ];

    /**
     * Has_one relationship
     */
    private static array $has_one = [
        'File' => File::class
    ];

    private static array $owns = [
        'File',
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     */
    private static array $summary_fields = [
        'Title' => 'Title',
        'TypeLabel' => 'Type',
        'LinkURL' => 'Link'
    ];

    /**
     * Defines a default list of filters for the search context
     */
    private static array $searchable_fields = [
        'Title',
        'URL',
        'Email',
        'Phone'
    ];

    /**
     * A map of styles that are available in the cms for
     * users to select from.
     */
    private static array $styles = [];

    /**
     * A map of object types that can be linked to
     * Custom dataobjects can be added to this
     */
    private static array $types = [
        'URL' => 'URL',
        'Email' => 'Email address',
        'Phone' => 'Phone number',
        'File' => 'File on this website',
    ];

    /**
     * List the allowed included link types.  If null all are allowed.
     *
     * @var array
     */
    private static $allowed_types;

    /**
     * @config
     */
    private static string $linking_mode_default = 'link';

    /**
     * @config
     */
    private static string $linking_mode_current = 'current';

    /**
     * @config
     */
    private static string $linking_mode_section = 'section';

    /**
     * If false, when Type is "File", folders in the TreeDropdownField will not be selectable.
     * @config
     */
    private static bool $link_to_folders = false;

    /**
     * Custom CSS classes for template
     */
    protected array $classes = [];

    /**
     * @var string custom style for template typically defined by the template.
     */
    protected $template_style;


    /**
     * CMS Fields
     * @return FieldList
     */
    #[\Override]
    public function getCMSFields()
    {
        $fields = FieldList::create(
            TabSet::create(
                'Root',
                Tab::create('Main')
            )
            ->setTitle(_t('SiteTree.TABMAIN', 'Main')),
            TabSet::create(
                'Root',
                Tab::create('Settings')
            )
            ->setTitle(_t('SiteTree.TABSETTINGS', 'Settings'))
        );

        if ($styles = $this->i18nStyles) {
            $fields->addFieldToTab(
                'Root.Settings',
                DropdownField::create(
                    'SelectedStyle',
                    _t(self::class . '.STYLE', 'Style'),
                    $styles
                )
                ->setEmptyString(_t(self::class . '.DEFAULT', 'Default')),
                'Type'
            );
        }

        $fields->addFieldsToTab(
            'Root.Main',
            $this->getCMSMainFields()
        );

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * CMS Main fields
     * This is so other modules can access these fields without other tabs etc.
     */
    public function getCMSMainFields(): array
    {
        $fields = [
            TextField::create(
                'Title',
                _t(self::class . '.TITLE', 'Title')
            )
            ->setDescription(_t(self::class . '.OPTIONALTITLE', 'Optional. Will be auto-generated from link if left blank.')),
            OptionsetField::create(
                'Type',
                _t(self::class . '.LINKTYPE', 'Type'),
                $this->i18nTypes
            )
            ->setValue('URL'),
            Wrapper::create(
                $fileDropdown = TreeDropdownField::create(
                    'FileID',
                    _t(self::class . '.FILE', 'File'),
                    File::class,
                    'ID',
                    'Title'
                )
            )
            ->displayIf('Type')->isEqualTo('File')->end(),
            Wrapper::create(
                TextField::create(
                    'URL',
                    _t(self::class . '.URL', 'URL')
                )
            )
            ->displayIf('Type')->isEqualTo('URL')->end(),
            Wrapper::create(
                TextField::create(
                    'Email',
                    _t(self::class . '.EMAILADDRESS', 'Email Address')
                )
            )
            ->displayIf('Type')->isEqualTo('Email')->end(),
            Wrapper::create(
                TextField::create(
                    'Phone',
                    _t(self::class . '.PHONENUMBER', 'Phone Number')
                )
            )
            ->displayIf('Type')->isEqualTo('Phone')->end(),
            CheckboxField::create(
                'OpenInNewWindow',
                _t(self::class . '.OPENINNEWWINDOW', 'Open link in a new window')
            )
            ->displayIf('Type')->isEqualTo('URL')
            ->orIf()->isEqualTo('File')
            ->orIf()->isEqualTo('SiteTree')->end()
        ];

        // Disable folders in dropdown if linking to folders is not allowed.
        if (!$this->config()->get('link_to_folders')) {
            $fileDropdown->setDisableFunction(fn ($item): bool => is_a($item, Folder::class));
        }

        $this->extend('updateCMSMainFields', $fields);

        return $fields;
    }

    /**
     * Validate
     */
    #[\Override]
    public function validate(): ValidationResult
    {
        $valid = true;
        $message = null;
        $type = $this->Type;

        // Check if empty strings
        switch ($type) {
            case 'URL':
            case 'Email':
            case 'Phone':
                if ($this->{$type} == '') {
                    $valid = false;
                    $message = _t(
                        self::class . '.VALIDATIONERROR_EMPTY'.strtoupper((string) $type),
                        'You must enter a {TypeLabel}',
                        [
                            'TypeLabel' => $this->TypeLabel
                        ]
                    );
                }

                break;
            case 'File':
            case 'SiteTree':
                if (empty($this->{$type.'ID'})) {
                    $valid = false;
                    $message = _t(
                        self::class . '.VALIDATIONERROR_OBJECT',
                        'Please select a {TypeLabel}',
                        [
                            'TypeLabel' => $this->TypeLabel
                        ]
                    );
                }

                break;
        }

        // if its already failed don't bother checking the rest
        if ($valid) {
            switch ($type) {
                case 'URL':
                    $allowedFirst = ['#', '/'];
                    if (!in_array(substr((string) $this->URL, 0, 1), $allowedFirst, true) && !filter_var($this->URL, FILTER_VALIDATE_URL)) {
                        $valid = false;
                        $message = _t(
                            self::class . '.VALIDATIONERROR_VALIDURL',
                            'Please enter a valid URL.  Be sure to include http:// for an external URL. or begin your internal url/anchor with a "/" character'
                        );
                    }

                    break;
                case 'Email':
                    if (!filter_var($this->Email, FILTER_VALIDATE_EMAIL)) {
                        $valid = false;
                        $message = _t(
                            self::class . '.VALIDATIONERROR_VALIDEMAIL',
                            'Please enter a valid Email address'
                        );
                    }

                    break;
                case 'Phone':
                    if (!preg_match("/^\+?[0-9a-zA-Z\-\s]*[\,\#]?[0-9\-\s]*$/", (string) $this->Phone)) {
                        $valid = false;
                        $message = _t(
                            self::class . '.VALIDATIONERROR_VALIDPHONE',
                            'Please enter a valid Phone number'
                        );
                    }

                    break;
            }
        }

        $result = ValidationResult::create();
        if (!$valid) {
            $result->addError($message);
        }

        $this->extend('updateValidate', $result);

        return $result;
    }

    /**
     * Event handler called before writing to the database.
     * If the title is empty, set a default based on the link.
     */
    #[\Override]
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (empty($this->Title)) {
            $type = $this->Type;
            switch ($type) {
                case 'URL':
                case 'Email':
                case 'Phone':
                    $this->Title = $this->getField($type);
                    break;
                case 'SiteTree':
                    if (class_exists(SiteTree::class) && $this->hasMethod('SiteTree')) {
                        $siteTree = $this->SiteTree();
                        if ($siteTree instanceof SiteTree) {
                            $this->Title = $siteTree->MenuTitle;
                        }
                    }

                    break;
                default:
                    if ($this->getRelationType($type) == 'has_one' && $component = $this->getComponent($type)) {
                        $this->Title = $component->Title;
                    } else {
                        $this->Title = 'Link-' . $this->ID;
                    }

                    break;
            }
        }
    }

    /**
     * Set CSS classes for templates
     * @param string $class CSS classes.
     */
    public function addExtraClass($class): static
    {
        $classes = ($class) ? explode(' ', $class) : [];
        foreach ($classes as $value) {
            $this->classes[$value] = $value;
        }

        return $this;
    }

    /**
     * This is an alias to {@link addExtraClass()}
     * @param string $class CSS classes.
     */
    public function setClass($class): static
    {
        return $this->addExtraClass($class);
    }

    /**
     * Set style class used in the class attribute.
     * This is not used as an inline style attribute.
     * @param string $style
     */
    public function setStyle($style): static
    {
        $this->template_style = $style;
        return $this;
    }

    /**
     * Get style defined by the template or admin
     */
    public function getStyle(): ?string
    {
        return $this->SelectedStyle ?: $this->template_style;
    }

    /**
     * Sets allowed link types
     *
     * @param array $types Allowed type names
     */
    public function setAllowedTypes($types = []): static
    {
        $this->allowed_types = $types;
        return $this;
    }

    /**
     * Returns allowed link types
     * @return array
     */
    public function getTypes()
    {
        $types = $this->config()->get('types');

        $allowed_types = $this->config()->get('allowed_types');
        if ($this->allowed_types) {
            // Prioritise local field over global settings
            $allowed_types = $this->allowed_types;
        }

        if ($allowed_types) {
            foreach ($allowed_types as $type) {
                if (!array_key_exists($type, $types)) {
                    user_error("{$type} is not a valid link type");
                }
            }

            foreach (array_keys(array_diff_key($types, array_flip($allowed_types))) as $key) {
                unset($types[$key]);
            }
        }

        $this->extend('updateTypes', $types);
        return $types;
    }

    /**
     * Returns allowed link types with translations
     */
    public function geti18nTypes(): array
    {
        $i18nTypes = [];
        // Get translatable labels
        foreach ($this->Types as $key => $label) {
            $i18nTypes[$key] = _t(self::class . '.TYPE'.strtoupper($key), $label);
        }

        $this->extend('updatei18nTypes', $i18nTypes);
        return $i18nTypes;
    }

    /**
     * Returns available styles
     * @return array
     */
    public function getStyles()
    {
        $styles = $this->config()->get('styles');
        $this->extend('updateStyles', $styles);
        return $styles;
    }

    /**
     * Returns available styles with translations
     */
    public function geti18nStyles(): array
    {
        $i18nStyles = [];
        foreach ($this->styles as $key => $label) {
            $i18nStyles[$key] = _t(self::class . '.STYLE' . strtoupper($key), $label);
        }

        $this->extend('updatei18nStyles', $i18nStyles);
        return $i18nStyles;
    }

    public function getFormattedPhoneLink(): string
    {
        $phone = $this->obj('Phone')->PhoneFriendly();
        if ($phone instanceof Phone) {
            return $phone->RFC3966()->forTemplate();
        } else {
            return '';
        }
    }

    /**
     * Works out what the URL for this link should be based on it's Type
     */
    public function getLinkURL(): ?string
    {
        if (!$this->ID) {
            return null;
        }

        $type = $this->Type;
        switch ($type) {
            case 'URL':
                $LinkURL = $this->URL;
                break;
            case 'Email':
                $LinkURL = $this->Email ? 'mailto:' . $this->Email : null;
                break;
            case 'Phone':
                $LinkURL = $this->getFormattedPhoneLink();
                break;
            case 'File':
            case 'SiteTree':
                if ($component = $this->getComponent($type)) {
                    if (!$component->exists()) {
                        $LinkURL = null;
                    }

                    if ($component->hasMethod('Link')) {
                        $LinkURL = $component->Link() . $this->Anchor;
                    } else {
                        $LinkURL = _t(
                            self::class . '.LINKMETHODMISSING',
                            'Please implement a Link() method on your dataobject "{type}"',
                            [
                                'type' => $type
                            ]
                        );
                    }
                }

                break;
            default:
                $LinkURL = null;
                break;
        }

        $this->extend('updateLinkURL', $LinkURL);
        return $LinkURL;
    }

    /**
     * Returns value for the template variable $LinkURL
     */
    public function LinkURL(): string
    {
        return $this->getLinkURL();
    }

    /**
     * Returns the css classes
     */
    public function getClass(): string
    {
        if ($this->SelectedStyle) {
            $this->setClass($this->SelectedStyle);
        } elseif ($this->template_style) {
            $this->setClass($this->template_style);
        }

        $classes = $this->classes;
        $this->extend('updateClasses', $classes);
        if ($classes !== []) {
            return implode(' ', $classes);
        }

        return '';
    }

    /**
     * Returns the html class attribute value for the template variable $Class
     */
    public function Class(): string
    {
        return $this->getClass();
    }

    /**
     * Returns the html class attribute
     */
    public function getClassAttr(): string
    {
        $class = trim($this->getClass());
        if ($class !== '') {
            return ' class="' . Convert::raw2htmlatt($class) . '"';
        } else {
            return '';
        }
    }

    /**
     * Returns the html class attribute for the template variable $ClassAttr
     */
    public function ClassAttr(): DBHTMLText
    {
        // @phpstan-ignore return.type
        return DBField::create_field('HTMLFragment', $this->getClassAttr());
    }

    /**
     * Returns the html target attribute
     */
    public function getTarget(): string
    {
        return $this->OpenInNewWindow ? "_blank" : '';
    }

    /**
     * Returns the html target attribute value for the template variable $Target
     */
    public function Target(): string
    {
        return $this->getTarget();
    }

    /**
     * Returns the html target attribute
     */
    public function getTargetAttr(): string
    {
        return $this->OpenInNewWindow ? ' target="_blank" rel="noopener"' : '';
    }

    /**
     * Returns the html target attribute for the template variable $TargetAttr
     */
    public function TargetAttr(): DBHTMLText
    {
        // @phpstan-ignore return.type
        return DBField::create_field('HTMLFragment', $this->getTargetAttr());
    }

    /**
     * Returns the html id attribute
     */
    public function getIDValue(): ?string
    {
        $id = '';
        $this->extend('updateIDValue', $id);
        return $id;
    }

    /**
     * Returns the html id attribute value for the template variable $IDValue
     */
    public function IDValue(): ?string
    {
        return $this->getIDValue();
    }

    /**
     * Renders an HTML ID attribute
     */
    public function getIDAttr(): string
    {
        $idValue = trim($this->getIDValue() ?? '');
        if ($idValue !== '') {
            return ' id="' . Convert::raw2htmlatt($idValue) . '"';
        } else {
            return '';
        }
    }

    /**
     * Returns the html id attribute for the template variable $IDAttr
     */
    public function IDAttr(): DBHTMLText
    {
        // @phpstan-ignore return.type
        return DBField::create_field('HTMLFragment', $this->getIDAttr());
    }

    /**
     * Returns the current page scope
     */
    public function getCurrentPage()
    {
        $currentPage = Director::get_current_page();
        if (class_exists(SiteTree::class) && class_exists(ContentController::class) && ($currentPage instanceof ContentController)) {
            $currentPage = $currentPage->data();
        }

        return $currentPage;
    }

    /**
     * Returns true if this is the currently active page being used to handle this request.
     *
     * @return bool
     */
    public function isCurrent()
    {
        $isCurrent = false;
        $this->extend('UpdateIsCurrent', $isCurrent);
        return $isCurrent;
    }

    /**
     * Check if this page is in the currently active section (e.g. it is either current or one of its children is
     * currently being viewed).
     *
     * @return bool
     */
    public function isSection()
    {
        $isSection = false;
        $this->extend('updateIsSection', $isSection);
        return $isSection;
    }

    /**
     * Check if the parent of this page has been removed (or made otherwise unavailable), and is still referenced by
     * this child. Any such orphaned page may still require access via the CMS, but should not be shown as accessible
     * to external users.
     *
     * @return bool
     */
    public function isOrphaned()
    {
        $isOrphaned = false;
        $this->extend('updateIsOrphaned', $isOrphaned);
        return $isOrphaned;
    }

    /**
     * Return "link" or "current" depending on if this is the {@link Link::isCurrent()} current page.
     *
     * @return string
     */
    public function LinkOrCurrent()
    {
        $isCurrent = null;
        $this->extend('updateLinkOrCurrent', $isCurrent);
        // @phpstan-ignore if.alwaysFalse
        if ($isCurrent) {
            return $this->config()->get('linking_mode_current');
        } else {
            return $this->config()->get('linking_mode_default');
        }
    }

    /**
     * Return "link" or "section" depending on if this is the {@link Link::isSection()} current section.
     *
     * @return string
     */
    public function LinkOrSection()
    {
        $isSection = null;
        $this->extend('updateLinkOrSection', $isSection);
        // @phpstan-ignore if.alwaysFalse
        if ($isSection) {
            return $this->config()->get('linking_mode_section');
        } else {
            return $this->config()->get('linking_mode_default');
        }
    }

    /**
     * Return "link", "current" or "section" depending on if this page is the current page, or not on the current page
     * but in the current section.
     *
     * @return string
     */
    public function LinkingMode()
    {
        if ($this->isCurrent()) {
            return $this->config()->get('linking_mode_current');
        } elseif ($this->isSection()) {
            return $this->config()->get('linking_mode_section');
        } else {
            return $this->config()->get('linking_mode_default');
        }
    }

    /**
     * Returns the description label of this links type
     * @return string
     */
    public function getTypeLabel()
    {
        $types = $this->config()->get('types');
        return isset($types[$this->Type]) ? _t(self::class . '.TYPE' . strtoupper((string) $this->Type), $types[$this->Type]) : null;
    }

    /**
     * Returns the base class without namespacing
     * @param  string $class
     */
    public function baseClassName($class): string
    {
        $class = explode('\\', $class);
        return array_pop($class);
    }

    /**
     * Renders an HTML anchor attribute for this link
     */
    #[\Override]
    public function forTemplate(): string
    {
        $link = '';
        if ($this->getLinkURL()) {
            $link = $this->renderWith($this->getRenderTemplates());
        }

        $this->extend('updateTemplate', $link);
        return $link;
    }

    /**
     * Renders an HTML anchor tag for this link
     * This is an alias to {@link forTemplate()}
     */
    public function getLayout(): string
    {
        return $this->forTemplate();
    }

    /**
     * Returns a list of rendering templates
     */
    public function getRenderTemplates(): array
    {
        $ClassName = $this->ClassName;

        if (is_object($ClassName)) {
            $ClassName = $ClassName::class;
        }

        if (!is_subclass_of($ClassName, DataObject::class)) {
            throw new InvalidArgumentException($ClassName . ' is not a subclass of DataObject');
        }

        $templates = [
            'type' => 'Includes'
        ];
        while ($next = get_parent_class($ClassName)) {
            $baseClassName = $this->baseClassName($ClassName);
            if ($this->Style) {
                $templates[] = $baseClassName . '_' . $this->style;
            }

            $templates[] = $baseClassName;
            if ($next == DataObject::class) {
                return $templates;
            }

            $ClassName = $next;
        }

        return [];
    }

    /**
     * @param \SilverStripe\Security\Member|null $member
     * @return bool
     */
    #[\Override]
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param \SilverStripe\Security\Member|null $member
     * @return bool
     */
    #[\Override]
    public function canEdit($member = null)
    {
        return true;
    }

    /**
     * @param \SilverStripe\Security\Member|null $member
     * @return bool
     */
    #[\Override]
    public function canDelete($member = null)
    {
        return true;
    }

    /**
     * @param \SilverStripe\Security\Member|null $member
     * @param array $context
     * @return bool
     */
    #[\Override]
    public function canCreate($member = null, $context = [])
    {
        return true;
    }
}
