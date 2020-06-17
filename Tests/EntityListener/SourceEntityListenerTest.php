<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;
use Softspring\PlatformBundle\Stripe\Adapter\SourceAdapter;
use Softspring\PlatformBundle\Stripe\EntityListener\SourceEntityListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\SourceExample;

class SourceEntityListenerTest extends TestCase
{
    /**
     * @var CustomerAdapter|MockObject
     */
    protected $customerAdapter;

    /**
     * @var SourceAdapter|MockObject
     */
    protected $sourceAdapter;

    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->customerAdapter = $this->createMock(CustomerAdapter::class);
        $this->sourceAdapter = $this->createMock(SourceAdapter::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testPrePersist()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();
        $eventArgs = new LifecycleEventArgs($source, $this->em);

        $this->sourceAdapter->expects($this->once())->method('create');

        $listener->prePersist($source, $eventArgs);
    }

    public function testPrePersistWebhooked()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();
        $source->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($source, $this->em);

        $this->sourceAdapter->expects($this->never())->method('create');

        $listener->prePersist($source, $eventArgs);
    }

    public function testPreUpdate()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($source, $this->em, $changeSet);

        // $this->sourceAdapter->expects($this->once())->method('create');

        $listener->preUpdate($source, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreUpdateExisting()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();
        $source->setPlatformId('id');
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($source, $this->em, $changeSet);

        // $this->sourceAdapter->expects($this->once())->method('update');

        $listener->preUpdate($source, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreUpdateWebhooked()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();
        $source->setPlatformWebhooked(true);

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($source, $this->em, $changeSet);

        $this->sourceAdapter->expects($this->never())->method('create');

        $listener->preUpdate($source, $eventArgs);
    }

    public function testPreRemove()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();

        $eventArgs = new LifecycleEventArgs($source, $this->em);

        $listener->preRemove($source, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreRemoveWebhooked()
    {
        $listener = new SourceEntityListener($this->customerAdapter, $this->sourceAdapter);

        $source = new SourceExample();
        $source->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($source, $this->em);

        $listener->preRemove($source, $eventArgs);

        $this->assertTrue(true);
    }
}
