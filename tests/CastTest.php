<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Tests;

use Hampel\XenForo\Api\Support\Cast;
use PHPUnit\Framework\TestCase as BaseTestCase;

final class CastTest extends BaseTestCase
{
    public function test_it_reads_integers_however_they_arrive(): void
    {
        $this->assertSame(12, Cast::int(12));
        $this->assertSame(12, Cast::int('12'));
        $this->assertSame(-5, Cast::int('-5'));
        $this->assertNull(Cast::int('twelve'));
        $this->assertNull(Cast::int(null));
        $this->assertNull(Cast::int([]));
    }

    /**
     * XenForo's JSON uses real booleans, but a value coming through a filter or an older
     * add-on can be 1/0.
     */
    public function test_it_reads_booleans_however_they_arrive(): void
    {
        $this->assertTrue(Cast::bool(true));
        $this->assertTrue(Cast::bool(1));
        $this->assertTrue(Cast::bool('1'));
        $this->assertFalse(Cast::bool(false));
        $this->assertFalse(Cast::bool(0));
        $this->assertFalse(Cast::bool('0'));
        $this->assertNull(Cast::bool('yes'));
        $this->assertNull(Cast::bool(null));
    }

    public function test_it_turns_a_xenforo_timestamp_into_a_date(): void
    {
        $date = Cast::timestamp(1757030400);

        $this->assertNotNull($date);
        $this->assertSame('2025-09-05', $date->format('Y-m-d'));
        $this->assertSame('UTC', $date->getTimezone()->getName());
    }

    /**
     * XenForo writes 0 for "never", which is not a date in 1970.
     */
    public function test_a_zero_timestamp_is_not_a_date(): void
    {
        $this->assertNull(Cast::timestamp(0));
        $this->assertNull(Cast::timestamp(null));
    }

    public function test_an_absent_value_is_never_an_error(): void
    {
        $this->assertNull(Cast::string(null));
        $this->assertNull(Cast::float(null));
        $this->assertSame([], Cast::array(null));
        $this->assertSame([], Cast::array('not an array'));
    }
}
