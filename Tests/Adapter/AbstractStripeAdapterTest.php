<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\InvoiceItem;
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
     * @return InvoiceItem|MockObject
     */
    protected function createStripeInvoiceItemObject(array $properties)
    {
        return $this->createStripeObject(InvoiceItem::class, $properties);
    }

    /**
     * @param array $properties
     *
     * @return InvoiceItem|MockObject
     */
    protected function createStripeInvoiceObject(array $properties)
    {
        return $this->createStripeObject(Invoice::class, $properties);
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
        $collection = $this->createStripeObject(Collection::class, ['has_more' => $hasMore, 'data' => $objects]);

        $collection->method('getIterator')->willReturn(new \ArrayIterator($objects));

        return $collection;
    }
}