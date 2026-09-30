<?php

namespace Osec\Tests\Unit;

use Osec\Bootstrap\MemoryCheck;
use Osec\Tests\Utilities\TestBase;

/**
 * Available memory check, used before compiling LESS.
 *
 * @group osec
 */
class MemoryCheckTest extends TestBase
{
    private string|false $memory_limit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memory_limit = ini_get('memory_limit');
    }

    protected function tearDown(): void
    {
        if (false !== $this->memory_limit) {
            ini_set('memory_limit', $this->memory_limit);
        }
        parent::tearDown();
    }

    public function test_no_requirement_always_passes()
    {
        $this->assertTrue(MemoryCheck::check_available_memory());
    }

    public function test_unlimited_memory_passes()
    {
        ini_set('memory_limit', '-1');

        $this->assertTrue(MemoryCheck::check_available_memory('24M'));
    }

    public function test_enough_memory_passes()
    {
        ini_set('memory_limit', '512M');

        $this->assertTrue(MemoryCheck::check_available_memory('24M'));
    }

    public function test_too_little_memory_fails()
    {
        // The limit cannot be set below the memory already in use, so ask for more than it offers.
        ini_set('memory_limit', '512M');

        $this->assertFalse(MemoryCheck::check_available_memory('4096M'));
    }
}
