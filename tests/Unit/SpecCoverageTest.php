<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\TestCase;
use StampMitra\Tests\Support\SpecCoverage;

final class SpecCoverageTest extends TestCase
{
    public function testEverySpecOperationHasAnSdkMethod(): void
    {
        $root = dirname(__DIR__, 2);
        self::assertCount(31, SpecCoverage::specOperations($root));
        self::assertSame([], SpecCoverage::missing($root), 'Spec operations missing from the SDK');
    }
}
