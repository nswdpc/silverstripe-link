<?php

namespace gorriecoe\Link\Tests;

use gorriecoe\Link\Models\Link;
use SilverStripe\Dev\SapphireTest;

class EmailLinkTest extends SapphireTest
{

    protected $usesDatabase = true;

    public function testLinkWithNoAttributes(): void
    {
        $link = Link::create([
            'Title' => "Email \"> me",
            'Type' => 'Email',
            'Email' => 'test@example.com',
            'OpenInNewWindow' => false
        ]);
        $link->write();

        $this->assertEquals(
            '<a href="mailto:test@example.com">Email &quot;&gt; me</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithTargetAttribute(): void
    {
        $link = Link::create([
            'Type' => 'Email',
            'Email' => 'test@example.com',
            'OpenInNewWindow' => true
        ]);
        $link->write();

        $this->assertEquals(
            '<a href="mailto:test@example.com" target="_blank" rel="noopener">test@example.com</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithClassAttribute(): void
    {
        $link = Link::create([
            'Type' => 'Email',
            'Email' => 'test@example.com',
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
            '<a class="link-set-class link-extra-class-one link-extra-class-two link-set-style" href="mailto:test@example.com">test@example.com</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithEscapedClassAttribute(): void
    {
        $link = Link::create([
            'Type' => 'Email',
            'Email' => 'test@example.com',
            'OpenInNewWindow' => false
        ]);
        $link->write();
        $link->setClass("\"><strong>strong</strong><a");

        $this->assertEquals(
            '<a class="&quot;&gt;&lt;strong&gt;strong&lt;/strong&gt;&lt;a" href="mailto:test@example.com">test@example.com</a>',
            trim($link->forTemplate())
        );
    }

    public function testLinkWithAttributes(): void
    {
        $link = Link::create([
            'Type' => 'Email',
            'Email' => 'test@example.com',
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
            '<a class="link-set-class link-extra-class-one link-extra-class-two link-set-style" href="mailto:test@example.com" target="_blank" rel="noopener">test@example.com</a>',
            trim($link->forTemplate())
        );
    }
}