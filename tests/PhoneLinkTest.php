<?php

namespace gorriecoe\Link\Tests;

use gorriecoe\Link\Models\Link;
use gorriecoe\Link\View\Phone;
use SilverStripe\Dev\SapphireTest;

class PhoneLinkTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testLinkWithNoAttributes(): void
    {
        $link = Link::create([
            'Title' => "Phone \"> me",
            'Type' => 'Phone',
            'Phone' => '+6480074992488',
            'OpenInNewWindow' => false
        ]);
        $link->write();

        $this->assertEquals(
            '<a href="tel:+64-80074992488">Phone &quot;&gt; me</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithTargetAttribute(): void
    {
        $link = Link::create([
            'Type' => 'Phone',
            'Phone' => '+6480074992488',
            'OpenInNewWindow' => true
        ]);
        $link->write();

        $this->assertEquals(
            '<a href="tel:+64-80074992488" target="_blank" rel="noopener">+6480074992488</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithClassAttribute(): void
    {
        $link = Link::create([
            'Type' => 'Phone',
            'Phone' => '+6480074992488',
            'OpenInNewWindow' => false
        ]);
        $link->write();
        // a style class
        $link->setStyle("link-set-style");
        // a class
        $link->setClass("link-set-class");
        // multi classes via extra class
        $link->addExtraClass("link-extra-class-one link-extra-class-two");

        $this->assertEquals(
            '<a class="link-set-class link-extra-class-one link-extra-class-two link-set-style" href="tel:+64-80074992488">+6480074992488</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithEscapedClassAttribute(): void
    {
        $link = Link::create([
            'Type' => 'Phone',
            'Phone' => '+6480074992488',
            'OpenInNewWindow' => false
        ]);
        $link->write();
        $link->setClass("\"><strong>strong</strong><a");

        $this->assertEquals(
            '<a class="&quot;&gt;&lt;strong&gt;strong&lt;/strong&gt;&lt;a" href="tel:+64-80074992488">+6480074992488</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithAttributes(): void
    {
        $link = Link::create([
            'Type' => 'Phone',
            'Phone' => '+6480074992488',
            'OpenInNewWindow' => true
        ]);
        $link->write();
        // a style class
        $link->setStyle("link-set-style");
        // a class
        $link->setClass("link-set-class");
        // multi classes via extra class
        $link->addExtraClass("link-extra-class-one link-extra-class-two");

        $this->assertEquals(
            '<a class="link-set-class link-extra-class-one link-extra-class-two link-set-style" href="tel:+64-80074992488" target="_blank" rel="noopener">+6480074992488</a>',
            trim($link->forTemplate())
        );
    }

    public function testPhoneFormats(): void
    {
        $link = Link::create([
            'Type' => 'Phone',
            'Phone' => '+6480074992488',
            'OpenInNewWindow' => false
        ]);
        $link->write();

        $obj = $link->obj('Phone');
        $phoneFriendly = $obj->PhoneFriendly();
        $this->assertInstanceof(Phone::class, $phoneFriendly);

        $e164 = $phoneFriendly->E164();
        $this->assertEquals('+6480074992488', $e164->forTemplate());

        $national = $phoneFriendly->National();
        $this->assertEquals('80074992488', $national->forTemplate());

        $international = $phoneFriendly->International();
        $this->assertEquals('+64 80074992488', $international->forTemplate());

        $rfc3966 = $phoneFriendly->RFC3966();
        $this->assertEquals('tel:+64-80074992488', $rfc3966->forTemplate());

    }
}
