<?php

declare(strict_types=1);

namespace Version\Tests;

use PHPUnit\Framework\TestCase;
use Version\Exception\CollectionIsEmptyException;
use Version\Exception\InvalidVersionStringException;
use Version\Tests\TestAsset\VersionIsIdentical;
use Version\Tests\TestAsset\VersionsCollectionIsIdentical;
use Version\VersionsCollection;
use Version\Version;
use Version\Constraint\ComparisonConstraint;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
class VersionsCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created_via_constructor() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3')
        );

        $this->assertThat($versions, new VersionsCollectionIsIdentical([
            [1, 0, 0, null, null],
            [1, 1, 0, null, null],
            [2, 3, 3, null, null],
        ]));
    }

    /**
     * @test
     */
    public function it_can_be_created_from_version_strings() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3')
        );

        $this->assertThat($versions, new VersionsCollectionIsIdentical([
            [1, 1, 0, null, null],
            [2, 3, 3, null, null],
        ]));
    }

    /**
     * @test
     */
    public function it_forwards_invalid_version_string_exception() : void
    {
        try {
            new VersionsCollection(
                Version::fromString('1.1.0'),
                Version::fromString('invalid')
            );

            $this->fail('Exception should have been raised');
        } catch (InvalidVersionStringException $ex) {
            $this->assertSame('invalid', $ex->getVersionString());
        }
    }

    /**
     * @test
     */
    public function it_is_countable() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3')
        );

        $this->assertCount(3, $versions);
    }

    /**
     * @test
     */
    public function it_provides_is_empty_check() : void
    {
        $versions = new VersionsCollection();

        $this->assertTrue($versions->isEmpty());
    }

    /**
     * @test
     */
    public function it_gets_first_version() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3')
        );

        $version = $versions->first();

        $this->assertNotNull($version);
        $this->assertThat($version, new VersionIsIdentical(1, 0, 0));
    }

    /**
     * @test
     */
    public function it_raises_exception_when_getting_first_item_of_empty_collection() : void
    {
        $versions = new VersionsCollection();

        try {
            $versions->first();

            $this->fail('Exception should have been raised');
        } catch (CollectionIsEmptyException $ex) {
            $this->assertSame('Invoking first() on an empty collection', $ex->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_gets_last_version() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3')
        );

        $version = $versions->last();

        $this->assertNotNull($version);
        $this->assertThat($version, new VersionIsIdentical(2, 3, 3));
    }

    /**
     * @test
     */
    public function it_raises_exception_when_getting_last_item_of_empty_collection() : void
    {
        $versions = new VersionsCollection();

        try {
            $versions->last();

            $this->fail('Exception should have been raised');
        } catch (CollectionIsEmptyException $ex) {
            $this->assertSame('Invoking last() on an empty collection', $ex->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_is_iterable() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3')
        );

        foreach ($versions as $version) {
            $this->assertInstanceOf(Version::class, $version);
        }
    }

    /**
     * @test
     */
    public function it_is_sorted_in_ascending_order_by_default() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('2.3.3'),
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3-beta')
        );

        $versions = $versions->sortedAscending();

        $expectedOrder = [
            '1.0.0',
            '1.1.0',
            '2.3.3-beta',
            '2.3.3',
        ];

        foreach ($versions as $key => $version) {
            $this->assertSame($expectedOrder[$key], (string) $version);
        }
    }

    /**
     * @test
     */
    public function it_can_be_sorted_in_descending_order() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('2.3.3'),
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0')
        );

        $versions = $versions->sortedDescending();

        $expectedOrder = [
            '2.3.3',
            '1.1.0',
            '1.0.0',
        ];

        foreach ($versions as $key => $version) {
            $this->assertSame($expectedOrder[$key], (string) $version);
        }
    }

    /**
     * @test
     */
    public function it_can_be_sorted_via_deprecated_sort_method() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('2.3.3'),
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.3.3-beta')
        );

        $versions->sort();

        $expectedOrder = [
            '1.0.0',
            '1.1.0',
            '2.3.3-beta',
            '2.3.3',
        ];

        foreach ($versions as $key => $version) {
            $this->assertSame($expectedOrder[$key], (string) $version);
        }
    }

    /**
     * @test
     */
    public function it_filters_versions_that_match_constraint() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.0.1'),
            Version::fromString('2.0.0'),
            Version::fromString('2.0.1')
        );

        $versions2 = $versions->matching(ComparisonConstraint::fromString('>=2.0.0'));

        $this->assertThat($versions2, new VersionsCollectionIsIdentical([
            [2, 0, 0, null, null],
            [2, 0, 1, null, null],
        ]));
    }

    /**
     * @test
     */
    public function it_gets_first_last_items_after_filtering() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.0.1'),
            Version::fromString('2.0.0'),
            Version::fromString('2.0.1')
        );

        $versions2 = $versions->matching(ComparisonConstraint::fromString('>=2.0.0'));

        $this->assertThat($versions2->first(), new VersionIsIdentical(2, 0, 0));
        $this->assertThat($versions2->last(), new VersionIsIdentical(2, 0, 1));
    }

    /**
     * @test
     */
    public function it_can_become_empty_after_filtering_out_all_versions() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.0.1'),
            Version::fromString('1.1.0')
        );

        $versions2 = $versions->matching(ComparisonConstraint::fromString('>=2.0.0'));

        $this->assertCount(0, $versions2);
    }

    /**
     * @test
     */
    public function it_finds_major_releases() : void
    {
        $releases = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.0.0'),
            Version::fromString('2.1.0'),
            Version::fromString('3.0.0'),
            Version::fromString('3.0.1')
        );

        $majorReleases = $releases->majorReleases();

        $this->assertThat($majorReleases, new VersionsCollectionIsIdentical([
            [1, 0, 0, null, null],
            [2, 0, 0, null, null],
            [3, 0, 0, null, null],
        ]));
    }

    /**
     * @test
     */
    public function it_finds_minor_releases() : void
    {
        $releases = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.0.0'),
            Version::fromString('2.1.0'),
            Version::fromString('2.1.1')
        );

        $minorReleases = $releases->minorReleases();

        $this->assertThat($minorReleases, new VersionsCollectionIsIdentical([
            [1, 1, 0, null, null],
            [2, 1, 0, null, null],
        ]));
    }

    /**
     * @test
     */
    public function it_finds_patch_releases() : void
    {
        $releases = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.0.1'),
            Version::fromString('2.0.0'),
            Version::fromString('2.0.1')
        );

        $patchReleases = $releases->patchReleases();

        $this->assertThat($patchReleases, new VersionsCollectionIsIdentical([
            [1, 0, 1, null, null],
            [2, 0, 1, null, null],
        ]));
    }

    /**
     * @test
     */
    public function it_finds_latest_major_release() : void
    {
        $releases = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.1.0'),
            Version::fromString('2.0.0'),
            Version::fromString('2.1.0'),
            Version::fromString('3.0.0'),
            Version::fromString('3.0.1')
        );

        $latestMajorRelease = $releases
            ->majorReleases()
            ->sortedDescending()
            ->first();

        $this->assertThat($latestMajorRelease, new VersionIsIdentical(3, 0, 0));
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_an_array() : void
    {
        $versions = new VersionsCollection(
            Version::fromString('1.0.0'),
            Version::fromString('1.0.1'),
            Version::fromString('1.1.0')
        );

        $versionsArray = $versions->toArray();

        $this->assertContainsOnlyInstancesOf(Version::class, $versionsArray);
        $this->assertCount(3, $versionsArray);
    }
}
