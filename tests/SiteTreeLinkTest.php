<?php

namespace gorriecoe\Link\Tests;

use gorriecoe\Link\Models\Link;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;

class SiteTreeLinkTest extends SapphireTest
{

    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(SiteTree::class)) {
            $this->markTestSkipped(
                'The silverstripe/cms module is required to run this test.'
            );
        }
    }

    public function testLink(): void
    {

        if (!class_exists(SiteTree::class)) {
            $this->markTestSkipped(
                'The silverstripe/cms module is required to run this test.'
            );
        }

        $siteTree = SiteTree::create([
            'Title' => 'Test page',
            'URLSegment' => 'test-page',
            'ParentID' => 0
        ]);
        $siteTree->write();
        $siteTree->publishSingle();
        $siteTreeLink = $siteTree->Link();
        $this->assertNotEmpty($siteTreeLink);

        $link = Link::create([
            'Title' => "Visit \"> page",
            'Type' => 'SiteTree',
            'SiteTreeID' => $siteTree->ID,
            'OpenInNewWindow' => false
        ]);
        $link->write();

        $this->assertEquals(
            '<a href="' . $siteTreeLink . '">Visit &quot;&gt; page</a>',
            trim($link->forTemplate())
        );
    }

}