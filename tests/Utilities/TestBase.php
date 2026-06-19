<?php

namespace Osec\Tests\Utilities;

// sys_get_temp_dir() . /wordpress-tests-lib/includes/abstract-testcase.php
use ReflectionClass;
use WP_UnitTestCase_Base;

/**
 * Base test.
 *
 * @group osec
 */
abstract class TestBase extends WP_UnitTestCase_Base
{
    protected static function getPrivateMethod($class, $name) {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        return $method;
    }
}
