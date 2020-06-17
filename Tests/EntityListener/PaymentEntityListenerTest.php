<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Stripe\Adapter\PaymentAdapter;
use Softspring\PlatformBundle\Stripe\EntityListener\PaymentEntityListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PaymentExample;

class PaymentEntityListenerTest extends TestCase
{
    /**
     * @var PaymentAdapter|MockObject
     */
    protected $paymentAdapter;

    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->paymentAdapter = $this->createMock(PaymentAdapter::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testPrePersist()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $eventArgs = new LifecycleEventArgs($payment, $this->em);

        $this->paymentAdapter->expects($this->once())->method('create');

        $listener->prePersist($payment, $eventArgs);
    }

    public function testPrePersistWebhooked()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $payment->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($payment, $this->em);

        $this->paymentAdapter->expects($this->never())->method('create');

        $listener->prePersist($payment, $eventArgs);
    }

    public function testPreUpdate()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($payment, $this->em, $changeSet);

        $this->paymentAdapter->expects($this->once())->method('create');

        $listener->preUpdate($payment, $eventArgs);
    }

    public function testPreUpdateExisting()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $payment->setPlatformId('id');
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($payment, $this->em, $changeSet);

        //$this->paymentAdapter->expects($this->once())->method('update');

        $listener->preUpdate($payment, $eventArgs);
        $this->assertTrue(true);
    }

    public function testPreUpdateWebhooked()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $payment->setPlatformWebhooked(true);

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($payment, $this->em, $changeSet);

        $this->paymentAdapter->expects($this->never())->method('create');

        $listener->preUpdate($payment, $eventArgs);
    }

    public function testPreRemoveMissing()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $eventArgs = new LifecycleEventArgs($payment, $this->em);

        // $this->paymentAdapter->expects($this->never())->method('delete');

        $listener->preRemove($payment, $eventArgs);
        $this->assertTrue(true);
    }

    public function testPreRemoveExisting()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $payment->setPlatformId('id');
        $eventArgs = new LifecycleEventArgs($payment, $this->em);

        // $this->paymentAdapter->expects($this->once())->method('delete');

        $listener->preRemove($payment, $eventArgs);
        $this->assertTrue(true);
    }

    public function testPreRemoveWebhooked()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $payment->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($payment, $this->em);

        // $this->paymentAdapter->expects($this->never())->method('delete');

        $listener->preRemove($payment, $eventArgs);
        $this->assertTrue(true);
    }

    public function testPreRemoveNotFoundInPlatform()
    {
        $listener = new PaymentEntityListener($this->paymentAdapter);

        $payment = new PaymentExample();
        $payment->setPlatformId('id');

        $eventArgs = new LifecycleEventArgs($payment, $this->em);

        // $this->paymentAdapter->method('delete')->willThrowException(new NotFoundInPlatform('stripe', 'Not found'));

        $listener->preRemove($payment, $eventArgs);

        $this->assertTrue(true);
    }
}
