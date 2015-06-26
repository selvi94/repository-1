<?php

/*
 * This file is part of the puli/repository package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Repository\Tests;

use Puli\Repository\Api\EditableRepository;
use Puli\Repository\Api\Resource\Resource;
use Puli\Repository\OptimizedPathMappingRepository;
use Puli\Repository\Resource\Collection\ArrayResourceCollection;
use Puli\Repository\Resource\DirectoryResource;
use Puli\Repository\Resource\FileResource;
use Puli\Repository\Tests\Resource\TestFilesystemDirectory;
use Puli\Repository\Tests\Resource\TestFilesystemFile;
use Webmozart\KeyValueStore\ArrayStore;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class OptimizedPathMappingRepositoryTest extends AbstractEditableRepositoryTest
{
    /**
     * @var ArrayStore
     */
    protected $store;

    /**
     * @var OptimizedPathMappingRepository
     */
    protected $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->store = new ArrayStore();
        $this->repo = new OptimizedPathMappingRepository($this->store);
    }

    protected function createPrefilledRepository(Resource $root)
    {
        $repo = new OptimizedPathMappingRepository(new ArrayStore());
        $repo->add('/', $root);

        return $repo;
    }

    protected function createWriteRepository()
    {
        return new OptimizedPathMappingRepository(new ArrayStore());
    }

    protected function createReadRepository(EditableRepository $writeRepo)
    {
        return $writeRepo;
    }

    protected function createFile($path = null, $body = TestFilesystemFile::BODY)
    {
        return new TestFilesystemFile($path, $body);
    }

    protected function createDirectory($path = null, array $children = array())
    {
        return new TestFilesystemDirectory($path, $children);
    }

    public function testCreateWithFilledStore()
    {
        $store = new ArrayStore();
        $store->set('/webmozart', new DirectoryResource(__DIR__ . '/Fixtures/dir5'));
        $store->set('/webmozart/file1', new FileResource(__DIR__ . '/Fixtures/dir5/file1'));

        $repo = new OptimizedPathMappingRepository($store);

        $this->assertTrue($repo->contains('/webmozart'));
        $this->assertTrue($repo->contains('/webmozart/file1'));
    }

    public function testAddDirectoryCompletelyResolveChildren()
    {
        $this->writeRepo->add('/webmozart', new DirectoryResource(__DIR__ . '/Fixtures/dir5'));

        $this->assertTrue($this->readRepo->contains('/webmozart'));
        $this->assertTrue($this->readRepo->contains('/webmozart/file1'));
        $this->assertTrue($this->readRepo->contains('/webmozart/file2'));
        $this->assertTrue($this->readRepo->contains('/webmozart/sub'));
        $this->assertTrue($this->readRepo->contains('/webmozart/sub/file3'));
        $this->assertTrue($this->readRepo->contains('/webmozart/sub/file4'));
    }

    public function testAddClonesResourcesAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $file = $this->createFile('/file');
        $file->attachTo($otherRepo);

        $this->repo->add('/webmozart/puli/file', $file);

        $this->assertNotSame($file, $this->repo->get('/webmozart/puli/file'));
        $this->assertSame('/file', $file->getPath());

        $clone = clone $file;
        $clone->attachTo($this->repo, '/webmozart/puli/file');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file'));
    }

    public function testAddCollectionClonesChildrenAttachedToAnotherRepository()
    {
        $otherRepo = $this->getMock('Puli\Repository\Api\ResourceRepository');

        $file1 = $this->createFile('/file1');
        $file2 = $this->createFile('/file2');

        $file2->attachTo($otherRepo);

        $this->repo->add('/webmozart/puli', new ArrayResourceCollection(array($file1, $file2)));

        $this->assertSame($file1, $this->repo->get('/webmozart/puli/file1'));
        $this->assertNotSame($file2, $this->repo->get('/webmozart/puli/file2'));
        $this->assertSame('/file2', $file2->getPath());

        $clone = clone $file2;
        $clone->attachTo($this->repo, '/webmozart/puli/file2');

        $this->assertEquals($clone, $this->repo->get('/webmozart/puli/file2'));
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testContainsFailsIfLanguageNotGlob()
    {
        $this->readRepo->contains('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testFindFailsIfLanguageNotGlob()
    {
        $this->readRepo->find('/*', 'foobar');
    }

    /**
     * @expectedException \Puli\Repository\Api\UnsupportedLanguageException
     * @expectedExceptionMessage foobar
     */
    public function testRemoveFailsIfLanguageNotGlob()
    {
        $this->writeRepo->remove('/*', 'foobar');
    }
}
