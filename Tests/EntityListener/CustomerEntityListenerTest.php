<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;
use Softspring\PlatformBundle\Stripe\EntityListener\CustomerEntityListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;

class CustomerEntityListenerTest extends TestCase
{
    /**
     * @var CustomerAdapter|MockObject
     */
    protected $customerAdapter;

    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->customerAdapter = $this->createMock(CustomerAdapter::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testPrePersist()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $eventArgs = new LifecycleEventArgs($customer, $this->em);

        $this->customerAdapter->expects($this->once())->method('create');

        $listener->prePersist($customer, $eventArgs);
    }

    public function testPrePersistWebhooked()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $customer->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($customer, $this->em);

        $this->customerAdapter->expects($this->never())->method('create');

        $listener->prePersist($customer, $eventArgs);
    }

    public function testPreUpdate()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($customer, $this->em, $changeSet);

        $this->customerAdapter->expects($this->once())->method('create');

        $listener->preUpdate($customer, $eventArgs);
    }

    public function testPreUpdateExisting()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $customer->setPlatformId('id');
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($customer, $this->em, $changeSet);

        $this->customerAdapter->expects($this->once())->method('update');

        $listener->preUpdate($customer, $eventArgs);
    }

    public function testPreUpdateWebhooked()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $customer->setPlatformWebhooked(true);

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($customer, $this->em, $changeSet);

        $this->customerAdapter->expects($this->never())->method('create');

        $listener->preUpdate($customer, $eventArgs);
    }

    public function testPreRemoveMissing()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $eventArgs = new LifecycleEventArgs($customer, $this->em);

        $this->customerAdapter->expects($this->never())->method('delete');

        $listener->preRemove($customer, $eventArgs);
    }

    public function testPreRemoveExisting()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $customer->setPlatformId('id');
        $eventArgs = new LifecycleEventArgs($customer, $this->em);

        $this->customerAdapter->expects($this->once())->method('delete');

        $listener->preRemove($customer, $eventArgs);
    }

    public function testPreRemoveWebhooked()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $customer->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($customer, $this->em);

        $this->customerAdapter->expects($this->never())->method('delete');

        $listener->preRemove($customer, $eventArgs);
    }

    public function testPreRemoveNotFoundInPlatform()
    {
        $listener = new CustomerEntityListener($this->customerAdapter);

        $customer = new CustomerFullExample();
        $customer->setPlatformId('id');

        $eventArgs = new LifecycleEventArgs($customer, $this->em);

        $this->customerAdapter->method('delete')->willThrowException(new NotFoundInPlatform('stripe', 'Not found'));

        $listener->preRemove($customer, $eventArgs);

        $this->assertTrue(true);
    }
}
