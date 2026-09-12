<?php

declare(strict_types=1);

namespace Pubvana\Tests\Support;

use flight\Engine;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

/**
 * Base test case for Pubvana.
 *
 * Provides two conveniences shared across the suite:
 *
 *  - invoke(): call a private/protected method from a test without
 *    changing production visibility. Keeps production code untouched and
 *    lets tests reach pure-logic methods directly.
 *  - app(): build a fresh Flight Engine with a given service map()ed, so
 *    services that read other services off the engine (via $app->name())
 *    can be exercised with a lightweight stand-in instead of the full
 *    application bootstrap.
 */
abstract class TestCase extends PhpUnitTestCase
{
    /**
     * Invoke a private/protected instance method reflectively.
     *
     * @param object               $object   Instance to invoke on
     * @param string               $method   Method name
     * @param array<int, mixed>    $args     Positional arguments
     *
     * @return mixed The method's return value
     */
    protected function invoke(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * Read a private/protected instance property reflectively.
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed The property's current value
     */
    protected function property(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    /**
     * Build a fresh Flight Engine ready to resolve mapped services.
     *
     * The engine is initialized and only $services is mapped onto it, so
     * a test can contrl exactly what $app->name()() returns.
     *
     * @param array<string, callable> $services map() name => value-provider
     *
     * @return Engine<object>
     */
    protected function app(array $services = []): Engine
    {
        $app = new Engine();
        $app->init();

        foreach ($services as $name => $provider) {
            if (is_callable($provider)) {
                $app->map($name, $provider);
            } else {
                $app->set($name, $provider);
            }
        }

        return $app;
    }
}
