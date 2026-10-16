<?php

declare(strict_types=1);

namespace StampMitra\Tests\Unit;

use PHPUnit\Framework\TestCase;
use StampMitra\Exception\ValidationError;
use StampMitra\Model\LogEntry;
use StampMitra\Tests\Support\FakeClient;

final class PaginationTest extends TestCase
{
    public function testListPassesFiltersAndTypesPage(): void
    {
        $fake = new FakeClient([FakeClient::json(200, ['items' => [['id' => 'a', 'service' => 'pan.verify', 'newField' => 1]], 'nextCursor' => 'c2'])]);
        $page = $fake->client()->logs->list(['service' => 'pan.verify', 'status' => 'success', 'limit' => 50]);

        self::assertSame('c2', $page->nextCursor);
        self::assertInstanceOf(LogEntry::class, $page->items[0]);
        self::assertSame(1, $page->items[0]->raw()['newField']);
        self::assertSame('service=pan.verify&status=success&limit=50', $fake->requests[0]->getUri()->getQuery());
    }

    public function testIterateFollowsCursorsLazily(): void
    {
        $fake = new FakeClient([
            FakeClient::json(200, ['items' => [['id' => '1'], ['id' => '2']], 'nextCursor' => 'c2']),
            FakeClient::json(200, ['items' => [['id' => '3']], 'nextCursor' => null]),
        ]);
        $gen = $fake->client()->logs->iterate(['limit' => 2]);
        self::assertInstanceOf(\Generator::class, $gen);

        $ids = [];
        foreach ($gen as $entry) {
            $ids[] = $entry->id;
        }

        self::assertSame(['1', '2', '3'], $ids);
        self::assertCount(2, $fake->requests);
        self::assertStringContainsString('cursor=c2', $fake->requests[1]->getUri()->getQuery());
        self::assertStringNotContainsString('cursor', $fake->requests[0]->getUri()->getQuery());
    }

    public function testInvalidFilterRejected(): void
    {
        $this->expectException(ValidationError::class);
        (new FakeClient())->client()->logs->list(['status' => 'maybe']);
    }
}
