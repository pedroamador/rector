<?php

declare(strict_types=1);

namespace Rector\Renaming\Tests\Rector\Name\RenameClassRector;

use Iterator;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\Core\Configuration\Option;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Tests\Rector\Name\RenameClassRector\Source\NewClass;
use Rector\Renaming\Tests\Rector\Name\RenameClassRector\Source\OldClass;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;
use Symplify\SmartFileSystem\SmartFileInfo;

/**
 * @see \Rector\PostRector\Rector\NameImportingPostRector
 */
final class AutoImportNamesParameter74Test extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideData()
     * @requires PHP 7.4
     */
    public function test(SmartFileInfo $fileInfo): void
    {
        $this->setParameter(Option::AUTO_IMPORT_NAMES, true);

        $this->doTestFileInfo($fileInfo);
    }

    public function provideData(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/FixtureAutoImportNames74');
    }

    /**
     * @return array<string, mixed[]>
     */
    protected function getRectorsWithConfiguration(): array
    {
        return [
            # this class causes to "partial_expression.php.inc" to fail
            SimplifyEmptyArrayCheckRector::class => [],
            RenameClassRector::class => [
                RenameClassRector::OLD_TO_NEW_CLASSES => [
                    OldClass::class => NewClass::class,
                ],
            ],
        ];
    }
}
