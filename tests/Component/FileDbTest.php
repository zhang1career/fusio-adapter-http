<?php

namespace Fusio\Adapter\Http\Tests\Component;

use PHPUnit\Framework\TestCase;
use Fusio\Adapter\Http\Component\FileDb;
use Fusio\Engine\Exception\NotFoundException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FileDbTest extends TestCase
{
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
    }

    public function testCreatesFileWhenMissing(): void
    {
        $path = vfsStream::url('root/fusio_filedb_test_' . uniqid() . '.txt');
        $this->assertFalse(file_exists($path));

        $db = new FileDb($path);

        $this->assertFileExists($path);
        $this->assertIsReadable($path);

        // query should return empty string for unknown key
        $this->assertSame('', $db->query('no_such_key'));
    }

    public function testReadsDataFromFile(): void
    {
        $path = vfsStream::url('root/fusio_filedb_test_' . uniqid() . '.txt');
        $contents = "service1\thttp://example.com\nservice2\thttp://a,b\n";
        file_put_contents($path, $contents);

        $db = new FileDb($path);

        $this->assertSame('http://example.com', $db->query('service1'));
        $this->assertSame('http://a,b', $db->query('service2'));
    }

    public function testThrowsOnEmptyPath(): void
    {
        $this->expectException(NotFoundException::class);
        new FileDb('');
    }

    public function testThrowsWhenDirectoryNotWritable(): void
    {
        $dir = vfsStream::newDirectory('unwritable', 0444)->at($this->root);
        $file = vfsStream::url('root/unwritable/file.txt');

        $this->expectException(NotFoundException::class);
        new FileDb($file);
    }

    public function testOverrideCreateDataFile(): void
    {
        // create a subclass to override the protected createDataFile method
        $mockPath = vfsStream::url('root/fusio_filedb_mock_' . uniqid() . '.txt');

        $subclass = new class($mockPath) extends FileDb {
            protected function createDataFile(string $path): void
            {
                // instead of actual creating, create with custom content
                file_put_contents($path, "serviceX\thttp://override\n");
            }
        };

        $this->assertFileExists($mockPath);
        $this->assertSame('http://override', $subclass->query('serviceX'));
    }
}
