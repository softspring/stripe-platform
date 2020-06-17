<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Stripe\Adapter\PlanAdapter;
use Softspring\PlatformBundle\Stripe\EntityListener\PlanEntityListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PlanExample;

class PlanEntityListenerTest extends TestCase
{
    /**
     * @var PlanAdapter|MockObject
     */
    protected $planAdapter;

    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->planAdapter = $this->createMock(PlanAdapter::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testPrePersist()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $eventArgs = new LifecycleEventArgs($plan, $this->em);

        $this->planAdapter->expects($this->once())->method('create');

        $listener->prePersist($plan, $eventArgs);
    }

    public function testPrePersistWebhooked()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $plan->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($plan, $this->em);

        $this->planAdapter->expects($this->never())->method('create');

        $listener->prePersist($plan, $eventArgs);
    }

    public function testPreUpdate()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($plan, $this->em, $changeSet);

        $this->planAdapter->expects($this->once())->method('create');

        $listener->preUpdate($plan, $eventArgs);
    }

    public function testPreUpdateExisting()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $plan->setPlatformId('id');
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($plan, $this->em, $changeSet);

        $this->planAdapter->expects($this->once())->method('update');

        $listener->preUpdate($plan, $eventArgs);
    }

    public function testPreUpdateWebhooked()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $plan->setPlatformWebhooked(true);

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($plan, $this->em, $changeSet);

        $this->planAdapter->expects($this->never())->method('create');

        $listener->preUpdate($plan, $eventArgs);
    }

    public function testPreRemoveMissing()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $eventArgs = new LifecycleEventArgs($plan, $this->em);

        $this->planAdapter->expects($this->never())->method('delete');

        $listener->preRemove($plan, $eventArgs);
    }

    public function testPreRemoveExisting()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $plan->setPlatformId('id');
        $eventArgs = new LifecycleEventArgs($plan, $this->em);

        $this->planAdapter->expects($this->once())->method('delete');

        $listener->preRemove($plan, $eventArgs);
    }

    public function testPreRemoveWebhooked()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $plan->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($plan, $this->em);

        $this->planAdapter->expects($this->never())->method('delete');

        $listener->preRemove($plan, $eventArgs);
    }

    public function testPreRemoveNotFoundInPlatform()
    {
        $listener = new PlanEntityListener($this->planAdapter);

        $plan = new PlanExample();
        $plan->setPlatformId('id');

        $eventArgs = new LifecycleEventArgs($plan, $this->em);

        $this->planAdapter->method('delete')->willThrowException(new NotFoundInPlatform('stripe', 'Not found'));

        $listener->preRemove($plan, $eventArgs);

        $this->assertTrue(true);
    }
}
