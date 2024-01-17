<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration\Archive;

use Piwik\Archive;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\Config;
use Piwik\DataAccess\Model;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Log\NullLogger;
use Piwik\Segment;
use Piwik\Tests\Fixtures\OneVisitorTwoVisits;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group ArchiveStateMetadataTest
 * @group Core
 */
class ArchiveStateMetadataTest extends IntegrationTestCase
{
    /**
     * @var OneVisitorTwoVisits
     */
    public static $fixture;

    /**
     * @var string
     */
    private $archiveDate;

    /**
     * @var Segment|null
     */
    private $archiveSegment;

    /**
     * @var int
     */
    private $archiveSite;

    /**
     * @var ArchiveInvalidator
     */
    private $invalidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->archiveDate = Date::factory(self::$fixture->dateTime)->toString();
        $this->archiveSite = self::$fixture->idSite;

        $this->invalidator = new ArchiveInvalidator(new Model(), new NullLogger());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Date::$now = null;
    }

    /**
     * @dataProvider archiveStateData
     *
     * @param array<string> $pluginsToInvalidate
     */
    public function test_getDataTableFromNumeric_returnsArchiveStateInMetadata(
        int     $nowTimestamp,
        ?string $segment,
        ?array  $pluginsToInvalidate,
        string  $expectedInitialArchiveState,
        string  $expectedFinalArchiveState
    ): void
    {
        Date::$now = $nowTimestamp;

        if (null !== $segment) {
            $this->archiveSegment = new Segment($segment, [$this->archiveSite]);
        }

        $this->setUpInitialState($expectedInitialArchiveState);
        $this->invalidateArchives($pluginsToInvalidate);
        $this->disableArchiving();

        $dataTable = $this->getDataTable();

        self::assertSame(
            $expectedFinalArchiveState,
            $dataTable->getMetadata(DataTable::ARCHIVE_STATE_METADATA_NAME)
        );
    }

    /**
     * @return iterable<string, array>
     */
    public function archiveStateData(): iterable
    {
        $timestampToday = strtotime(self::$fixture->dateTime);
        $timestampTomorrow = $timestampToday + 86400;

        yield 'today, all ok' => [
            $timestampToday,
            null,
            null,
            DataTable::ID_ARCHIVE_STATE_INCOMPLETE,
            DataTable::ID_ARCHIVE_STATE_INCOMPLETE
        ];

        yield 'yesterday, all ok' => [
            $timestampTomorrow,
            null,
            null,
            DataTable::ID_ARCHIVE_STATE_COMPLETE,
            DataTable::ID_ARCHIVE_STATE_COMPLETE
        ];

        yield 'today, everything invalidated' => [
            $timestampToday,
            null,
            [],
            DataTable::ID_ARCHIVE_STATE_INCOMPLETE,
            DataTable::ID_ARCHIVE_STATE_INVALIDATED
        ];

        yield 'yesterday, everything invalidated' => [
            $timestampTomorrow,
            null,
            [],
            DataTable::ID_ARCHIVE_STATE_COMPLETE,
            DataTable::ID_ARCHIVE_STATE_INVALIDATED
        ];

        yield 'segmented, everything invalidated' => [
            $timestampTomorrow,
            'visitorType==new',
            [],
            DataTable::ID_ARCHIVE_STATE_COMPLETE,
            DataTable::ID_ARCHIVE_STATE_INVALIDATED
        ];

        yield 'segmented, partially invalidated' => [
            $timestampTomorrow,
            'visitorType==new',
            ['Goals'],
            DataTable::ID_ARCHIVE_STATE_COMPLETE,
            DataTable::ID_ARCHIVE_STATE_INVALIDATED
        ];
    }

    private function disableArchiving(): void
    {
        Config::getInstance()->General['enable_browser_archiving_triggering'] = 0;
        Config::getInstance()->General['browser_archiving_disabled_enforce'] = 1;
    }

    private function getDataTable(): DataTable
    {
        $archive = Archive::build(
            $this->archiveSite,
            'day',
            $this->archiveDate,
            null === $this->archiveSegment ? null : $this->archiveSegment->getString()
        );

        return $archive->getDataTableFromNumeric(['Goal_nb_conversions', 'Goals_nb_visits_converted', 'nb_visits']);
    }

    /**
     * @param array<string>|null $plugins
     */
    private function invalidateArchives(?array $plugins): void
    {
        if (null === $plugins) {
            // invalidate nothing
            return;
        }

        if ([] === $plugins) {
            // invalidate everything
            $plugins = [null];
        }

        foreach ($plugins as $plugin) {
            $this->invalidator->markArchivesAsInvalidated(
                [$this->archiveSite],
                [$this->archiveDate],
                'day',
                $this->archiveSegment,
                false,
                false,
                $plugin
            );
        }
    }

    private function setUpInitialState(string $expectedInitialArchiveState): void
    {
        $dataTable = $this->getDataTable();
        $archiveState = $dataTable->getMetadata(DataTable::ARCHIVE_STATE_METADATA_NAME);

        self::assertSame($expectedInitialArchiveState, $archiveState);
    }
}

ArchiveStateMetadataTest::$fixture = new OneVisitorTwoVisits();
