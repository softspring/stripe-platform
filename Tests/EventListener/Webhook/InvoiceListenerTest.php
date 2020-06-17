<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EventListener\Webhook;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PaymentBundle\Manager\InvoiceManager;
use Softspring\PaymentBundle\Manager\InvoiceManagerInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\EventListener\Webhook\InvoiceListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\InvoiceExample;
use Softspring\PlatformBundle\Stripe\Transformer\InvoiceTransformer;
use Stripe\Invoice;
use Stripe\Event;

class InvoiceListenerTest extends TestCase
{
    /**
     * @var InvoiceManagerInterface|MockObject
     */
    protected $invoiceManager;

    protected function setUp(): void
    {
        $this->invoiceManager = $this->createMock(InvoiceManager::class);
    }

    public function testSubscribedEvents()
    {
        $subscribedEvents = InvoiceListener::getSubscribedEvents();

        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.created', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.deleted', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.finalized', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.marked_uncollectible', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.payment_failed', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.payment_succeeded', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.sent', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.updated', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.invoice.voided', $subscribedEvents);
    }

    public function testOnInvoiceCreateOrUpdate()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->invoiceManager->method('getRepository')->willReturn($repository);
        $this->invoiceManager->expects($this->once())->method('saveEntity');
        $this->invoiceManager->expects($this->once())->method('createEntity')->willReturn(new InvoiceExample());

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Invoice('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;
        $stripeEvent->data->object->status = 'open';
        $stripeEvent->data->object->currency = 'usd';
        $stripeEvent->data->object->total = 1111;

        $event = new StripeWebhookEvent('invoice.created', $stripeEvent);

        $eventListener = new InvoiceListener($this->invoiceManager, new InvoiceTransformer());

        $eventListener->onInvoiceCreateOrUpdate($event);
    }

    public function testOnInvoiceDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(new InvoiceExample());
        $this->invoiceManager->method('getRepository')->willReturn($repository);
        $this->invoiceManager->expects($this->once())->method('deleteEntity');

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Invoice('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('invoice.deleted', $stripeEvent);

        $eventListener = new InvoiceListener($this->invoiceManager, new InvoiceTransformer());

        $eventListener->onInvoiceDeleted($event);
    }

    public function testOnInvoiceDeletedAlreadyDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->invoiceManager->method('getRepository')->willReturn($repository);

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Invoice('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('invoice.deleted', $stripeEvent);

        $eventListener = new InvoiceListener($this->invoiceManager, new InvoiceTransformer());

        $this->assertNull($eventListener->onInvoiceDeleted($event));
    }
}
