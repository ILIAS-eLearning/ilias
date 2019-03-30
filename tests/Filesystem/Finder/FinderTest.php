<?php declare(strict_types=1);

require_once 'libs/composer/vendor/autoload.php';

use ILIAS\Data\DataSize;
use ILIAS\Filesystem;
use ILIAS\Filesystem\Finder\Finder;
use ILIAS\Filesystem\MetadataType;
use PHPUnit\Framework\TestCase;

class FinderTest extends TestCase
{
	/**
	 * @return Filesystem\Filesystem
	 * @throws ReflectionException
	 */
	private function getFlatFileSystemStructure(): Filesystem\Filesystem
	{
		$fileSystem = $this->getMockBuilder(Filesystem\Filesystem::class)->getMock();

		$metadata = [
			new Filesystem\DTO\Metadata('file_1.txt', MetadataType::FILE),
			new Filesystem\DTO\Metadata('file_2.mp3', MetadataType::FILE),
			new Filesystem\DTO\Metadata('dir_1', MetadataType::DIRECTORY),
		];

		$fileSystem
			->expects($this->atLeast(1))
			->method('listContents')
			->will($this->returnCallback(function ($path) use ($metadata) {
				if ('/' === $path) {
					return $metadata;
				}

				return [];
			}));

		return $fileSystem;
	}

	/**
	 * @return Filesystem\Filesystem
	 * @throws ReflectionException
	 */
	private function getNestedFileSystemStructure(): Filesystem\Filesystem
	{
		$fileSystem = $this->getMockBuilder(Filesystem\Filesystem::class)->getMock();

		$rootMetadata = [
			new Filesystem\DTO\Metadata('file_1.txt', MetadataType::FILE),
			new Filesystem\DTO\Metadata('file_2.mp3', MetadataType::FILE),
			new Filesystem\DTO\Metadata('dir_1', MetadataType::DIRECTORY),
		];

		$level1Metadata = [
			new Filesystem\DTO\Metadata('dir_1/file_3.log', MetadataType::FILE),
			new Filesystem\DTO\Metadata('dir_1/file_4.php', MetadataType::FILE),
			new Filesystem\DTO\Metadata('dir_1/dir_1_1', MetadataType::DIRECTORY),
			new Filesystem\DTO\Metadata('dir_1/dir_1_2', MetadataType::DIRECTORY),
		];

		$level11Metadata = [
			new Filesystem\DTO\Metadata('dir_1/dir_1_1/file_5.cpp', MetadataType::FILE),
		];

		$level12Metadata = [
			new Filesystem\DTO\Metadata('dir_1/dir_1_2/file_6.py', MetadataType::FILE),
			new Filesystem\DTO\Metadata('dir_1/dir_1_2/file_7.cpp', MetadataType::FILE),
			new Filesystem\DTO\Metadata('dir_1/dir_1_2/dir_1_2_1', MetadataType::DIRECTORY),
		];

		$fileSystem
			->expects($this->atLeast(1))
			->method('listContents')
			->will($this->returnCallback(function ($path) use ($rootMetadata, $level1Metadata, $level11Metadata, $level12Metadata) {
				if ('/' === $path) {
					return $rootMetadata;
				} elseif ('dir_1' === $path) {
					return $level1Metadata;
				} elseif ('dir_1/dir_1_1' === $path) {
					return $level11Metadata;
				} elseif ('dir_1/dir_1_2' === $path) {
					return $level12Metadata;
				}

				return [];
			}));

		return $fileSystem;
	}

	/**
	 * @throws ReflectionException
	 */
	public function testFinderWillFindNoFilesOrFoldersInAnEmptyDirectory()
	{
		$fileSystem = $this->getMockBuilder(Filesystem\Filesystem::class)->getMock();

		$fileSystem
			->expects($this->any())
			->method('listContents')
			->willReturn([]);

		$finder = (new Finder($fileSystem))->in(['/']);

		$this->assertEmpty(iterator_count($finder));
	}

	/**
	 * @throws ReflectionException
	 */
	public function testFinderWillFindFilesAndFoldersInFlatStructure()
	{
		$finder = (new Finder($this->getFlatFileSystemStructure()))->in(['/']);

		$this->assertCount(3, $finder);
		$this->assertCount(1, $finder->directories());
		$this->assertCount(2, $finder->files());
	}

	/**
	 * @throws ReflectionException
	 */
	public function testFinderWillFindFilesAndFoldersInNestedStructure()
	{
		$finder = (new Finder($this->getNestedFileSystemStructure()))->in(['/']);

		$this->assertCount(11, $finder);
		$this->assertCount(4, $finder->directories());
		$this->assertCount(7, $finder->files());
	}

	/**
	 * @throws ReflectionException
	 */
	public function testFinderWillFindFilesAndFoldersForACertainDirectoryDepth()
	{
		$finder = (new Finder($this->getNestedFileSystemStructure()))->in(['/']);

		$level0Finder = $finder->depth(0);
		$this->assertCount(3, $level0Finder);
		$this->assertCount(1, $level0Finder->directories());
		$this->assertCount(2, $level0Finder->files());

		$greaterLevel0Finder = $finder->depth('> 0');
		$this->assertCount(8, $greaterLevel0Finder);
		$this->assertCount(3, $greaterLevel0Finder->directories());
		$this->assertCount(5, $greaterLevel0Finder->files());

		$greaterOrEqualLevel0Finder = $finder->depth('>= 0');
		$this->assertCount(11, $greaterOrEqualLevel0Finder);
		$this->assertCount(4, $greaterOrEqualLevel0Finder->directories());
		$this->assertCount(7, $greaterOrEqualLevel0Finder->files());

		$lowerOrEqualLevel1Finder = $finder->depth('<= 1');
		$this->assertCount(7, $lowerOrEqualLevel1Finder);
		$this->assertCount(3, $lowerOrEqualLevel1Finder->directories());
		$this->assertCount(4, $lowerOrEqualLevel1Finder->files());

		$lowerLevel2Finder = $finder->depth('< 2');
		$this->assertCount(7, $lowerLevel2Finder);
		$this->assertCount(3, $lowerLevel2Finder->directories());
		$this->assertCount(4, $lowerLevel2Finder->files());

		$exactlyLevel2Finder = $finder->depth(2);
		$this->assertCount(4, $exactlyLevel2Finder);
		$this->assertCount(1, $exactlyLevel2Finder->directories());
		$this->assertCount(3, $exactlyLevel2Finder->files());
	}

	/**
	 * @throws ReflectionException
	 */
	public function testFinderWillNotSearchInExcludedFolders()
	{
		$finder = (new Finder($this->getNestedFileSystemStructure()))->in(['/']);

		$finderWithExcludedDir = $finder->exclude(['dir_1/dir_1_1']);
		$this->assertCount(9, $finderWithExcludedDir);
		$this->assertCount(3, $finderWithExcludedDir->directories());
		$this->assertCount(6, $finderWithExcludedDir->files());

		$finderWithMultipleExcludedDirs = $finder->exclude(['dir_1/dir_1_1', 'dir_1/dir_1_2/dir_1_2_1']);
		$this->assertCount(8, $finderWithMultipleExcludedDirs);
		$this->assertCount(2, $finderWithMultipleExcludedDirs->directories());
		$this->assertCount(6, $finderWithMultipleExcludedDirs->files());
	}

	/**
	 * @throws ReflectionException
	 * @return Filesystem\Filesystem
	 */
	public function testFinderWillFilterFilesAndFoldersByCreationTimestamp(): Filesystem\Filesystem
	{
		// 30.03.2019 13:00:00 Europe/Berlin
		$now = 1553947200;

		$fs = $this->getNestedFileSystemStructure();
		$fs
			->expects($this->any())->method('has')->willReturn(true);

		$fs
			->expects($this->atLeast(1))
			->method('getTimestamp')
			->will($this->returnCallback(function ($path) use ($now) {
				switch ($path) {
					case'file_1.txt':
						return new \DateTimeImmutable('@' . $now);

					case 'file_2.mp3':
						return new \DateTimeImmutable('@' . ($now + 60 * 60 * 1));

					case 'dir_1/file_3.log':
						return new \DateTimeImmutable('@' . ($now + 60 * 60 * 2));

					case 'dir_1/file_4.php':
						return new \DateTimeImmutable('@' . ($now + 60 * 60 * 3));

					case 'dir_1/dir_1_1/file_5.cpp':
						return new \DateTimeImmutable('@' . ($now + 60 * 60 * 4));

					case 'dir_1/dir_1_2/file_6.py':
						return new \DateTimeImmutable('@' . ($now + 60 * 60 * 5));

					case 'dir_1/dir_1_2/file_7.cpp':
						return new \DateTimeImmutable('@' . ($now + 60 * 60 * 6));

					default:
						return new \DateTimeImmutable('now');
				}
			}));

		$finder = (new Finder($fs))->in(['/']);

		for ($i = 1; $i <= 7; $i++) {
			$this->assertCount(8 - $i, $finder->date('>= 2019-03-30 1' . (string) (2 + $i) . ':00')->files());
		}
		$this->assertCount(3, $finder->date('>= 2019-03-30 15:00 + 2hours')->files());
		$this->assertCount(2, $finder->date('> 2019-03-30 15:00 + 2hours')->files());
		$this->assertCount(1, $finder->date('2019-03-30 15:00 + 2hours')->files());
		$this->assertCount(2, $finder->date('< 2019-03-30 15:00')->files());
		$this->assertCount(3, $finder->date('<= 2019-03-30 15:00')->files());
		$this->assertCount(2, $finder->date('<= 2019-03-30 15:00 - 1minute')->files());

		return $fs;
	}

	/**
	 * @throws ReflectionException
	 */
	public function testFinderWillFilterFilesBySize()
	{
		$fs = $this->getNestedFileSystemStructure();
		$fs->expects($this->any())->method('has')->willReturn(true);

		$fs->expects($this->atLeast(1))
			->method('getSize')
			->will($this->returnCallback(function ($path) {
				switch ($path) {
					case'file_1.txt':
						return new DataSize(PHP_INT_MAX, DataSize::Byte);

					case 'file_2.mp3':
						return new DataSize(1024, DataSize::Byte);

					case 'dir_1/file_3.log':
						return new DataSize(1024 * 1024 * 1024, DataSize::Byte);

					case 'dir_1/file_4.php':
						return new DataSize(1024 * 1024 * 127, DataSize::Byte);

					case 'dir_1/dir_1_1/file_5.cpp':
						return new DataSize(1024 * 7, DataSize::Byte);

					case 'dir_1/dir_1_2/file_6.py':
						return new DataSize(1024 * 100, DataSize::Byte);

					case 'dir_1/dir_1_2/file_7.cpp':
						return new DataSize(1, DataSize::Byte);

					default:
						return new DataSize(0, DataSize::Byte);
				}
			}));

		$finder = (new Finder($fs))->in(['/']);

		$this->assertCount(1, $finder->size('< 1Ki')->files());
		$this->assertCount(2, $finder->size('<= 1Ki')->files());
		$this->assertCount(6, $finder->size('>= 1Ki')->files());
		$this->assertCount(5, $finder->size('> 1Ki')->files());
		$this->assertCount(1, $finder->size('1Ki')->files());

		$this->assertCount(3, $finder->size('> 1Mi')->files());
		$this->assertCount(2, $finder->size('>= 1Gi')->files());
	}

	/**
	 * @param Filesystem\Filesystem $fs
	 * @depends testFinderWillFilterFilesAndFoldersByCreationTimestamp
	 */
	public function testSortingWorksAsExpected(Filesystem\Filesystem $fs)
	{
		$finder = (new Finder($fs))->in(['/']);

		$this->assertEquals('file_1.txt', current($finder->sortByTime()->getIterator())->getPath());
		$this->assertEquals(
			'dir_1/dir_1_2/file_7.cpp',
			current($finder->sortByTime()->reverseSorting()->getIterator())->getPath()
		);
		$this->assertEquals('dir_1', current($finder->sortByName()->getIterator())->getPath());
		$this->assertEquals('file_2.mp3', current($finder->sortByName()->reverseSorting()->getIterator())->getPath());
		$this->assertEquals('dir_1', current($finder->sortByType()->getIterator())->getPath());
		$this->assertEquals('file_2.mp3', current($finder->sortByType()->reverseSorting()->getIterator())->getPath());
	}
}