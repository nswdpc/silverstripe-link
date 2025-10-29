<?php

namespace gorriecoe\Link\Tests;

use gorriecoe\Link\Models\Link;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Dev\SapphireTest;

class FileLinkTest extends SapphireTest
{
    protected $usesDatabase = true;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        TestAssetStore::activate('FileLinkTest');
    }

    public function testLink(): void
    {

        $file = File::create([
            'FileFilename' => 'FileTest.txt',
            'FileHash' => '55b443b60176235ef09801153cca4e6da7494a0c',
            'Name' => 'FileTest.txt'
        ]);
        $file->setFromString(str_repeat('x', 1000000), $file->getFilename());
        $file->write();
        $file->publishFile();

        $fileLink = $file->Link();
        $this->assertNotEmpty($fileLink);

        $link = Link::create([
            'Title' => 'Download "> file',
            'Type' => 'File',
            'FileID' => $file->ID,
            'OpenInNewWindow' => false
        ]);
        $link->write();

        $this->assertEquals(
            '<a href="' . $fileLink . '">Download &quot;&gt; file</a>',
            trim($link->forTemplate())
        );
    }

}
