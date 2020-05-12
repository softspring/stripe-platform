<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\TaxId;

abstract class AbstractStripeAdapterTest extends TestCase
{
    protected function createStripeObject(string $class, array $properties = [], array $callbacks = [])
    {
        $object = $this->getMockBuilder($class)->getMock();

        $properties['object'] = constant("$class::OBJECT_NAME");
        $object->method('__get')->willReturnCallback(function ($prop) use ($properties) {
            return $properties[$prop];
        });
        $object->method('__isset')->willReturnCallback(function ($prop) use ($properties) {
            return isset($properties[$prop]);
        });

        foreach ($callbacks as $function => $callback) {
            $object->method($function)->willReturnCallback($callback);
        }

        return $object;
    }

    /**
     * @param array $properties
     *
     * @return Customer|MockObject
     */
    protected function createStripeCustomerObject(array $properties)
    {
        return $this->createStripeObject(Customer::class, $properties);
    }

    /**
     * @param array $properties
     *
     * @return Customer|MockObject
     */
    protected function createStripeTaxIdObject(array $properties)
    {
        return $this->createStripeObject(TaxId::class, $properties);
    }

    protected function createStripeCollectionObject(array $objects, bool $hasMore = false)
    {
        $collection = $this->createStripeObject(Collection::class, ['has_more' => $hasMore]);

        $collection->method('getIterator')->willReturn(new \ArrayIterator($objects));
        $collection->method('__get')->with('data')->willReturn($objects);
        $collection->method('offsetGet')->willReturnCallback(function ($k) use ($objects) {
            return $objects[$k];
        });

        return $collection;
    }
}