<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Stripe\Adapter\InvoiceAdapter;
use Softspring\PlatformBundle\Stripe\EntityListener\InvoiceEntityListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\InvoiceExample;

class InvoiceEntityListenerTest extends TestCase
{
    /**
     * @var InvoiceAdapter|MockObject
     */
    protected $invoiceAdapter;

    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->invoiceAdapter = $this->createMock(InvoiceAdapter::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testPrePersist()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $eventArgs = new LifecycleEventArgs($invoice, $this->em);

        $this->invoiceAdapter->expects($this->once())->method('create');

        $listener->prePersist($invoice, $eventArgs);
    }

    public function testPrePersistWebhooked()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $invoice->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($invoice, $this->em);

        $this->invoiceAdapter->expects($this->never())->method('create');

        $listener->prePersist($invoice, $eventArgs);
    }

    public function testPreUpdate()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($invoice, $this->em, $changeSet);

        $this->invoiceAdapter->expects($this->once())->method('create');

        $listener->preUpdate($invoice, $eventArgs);
    }

    public function testPreUpdateExisting()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $invoice->setPlatformId('id');
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($invoice, $this->em, $changeSet);

        // $this->invoiceAdapter->expects($this->once())->method('update');

        $listener->preUpdate($invoice, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreUpdateWebhooked()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $invoice->setPlatformWebhooked(true);

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($invoice, $this->em, $changeSet);

        $this->invoiceAdapter->expects($this->never())->method('create');

        $listener->preUpdate($invoice, $eventArgs);
    }

    public function testPreRemoveMissing()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $eventArgs = new LifecycleEventArgs($invoice, $this->em);

        // $this->invoiceAdapter->expects($this->never())->method('delete');

        $listener->preRemove($invoice, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreRemoveExisting()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $invoice->setPlatformId('id');
        $eventArgs = new LifecycleEventArgs($invoice, $this->em);

        // //$this->invoiceAdapter->expects($this->once())->method('delete');
        // $this->invoiceAdapter->expects($this->never())->method('delete');

        $listener->preRemove($invoice, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreRemoveWebhooked()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $invoice->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($invoice, $this->em);

        // $this->invoiceAdapter->expects($this->never())->method('delete');

        $listener->preRemove($invoice, $eventArgs);

        $this->assertTrue(true);
    }

    public function testPreRemoveNotFoundInPlatform()
    {
        $listener = new InvoiceEntityListener($this->invoiceAdapter);

        $invoice = new InvoiceExample();
        $invoice->setPlatformId('id');

        $eventArgs = new LifecycleEventArgs($invoice, $this->em);

        // $this->invoiceAdapter->method('delete')->willThrowException(new NotFoundInPlatform('stripe', 'Not found'));

        $listener->preRemove($invoice, $eventArgs);

        $this->assertTrue(true);
    }
}
