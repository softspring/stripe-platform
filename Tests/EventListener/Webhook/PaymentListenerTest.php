<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EventListener\Webhook;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PaymentBundle\Manager\PaymentManager;
use Softspring\PaymentBundle\Manager\PaymentManagerInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\EventListener\Webhook\PaymentListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PaymentExample;
use Softspring\PlatformBundle\Stripe\Transformer\PaymentTransformer;
use Stripe\Charge;
use Stripe\Event;

class PaymentListenerTest extends TestCase
{
    /**
     * @var PaymentManagerInterface|MockObject
     */
    protected $paymentManager;

    /**
     * @var PaymentTransformer|MockObject
     */
    protected $paymentTransformer;

    protected function setUp(): void
    {
        $this->paymentManager = $this->createMock(PaymentManager::class);
        $this->paymentTransformer = $this->createMock(PaymentTransformer::class);
    }

    public function testSubscribedEvents()
    {
        $subscribedEvents = PaymentListener::getSubscribedEvents();

        $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.captured', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.expired', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.failed', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.pending', $subscribedEvents);
        // $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.refunded', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.succeeded', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.charge.updated', $subscribedEvents);
    }

    public function testOnPaymentCreateOrUpdate()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->paymentManager->method('getRepository')->willReturn($repository);
        $this->paymentManager->expects($this->once())->method('saveEntity');
        $this->paymentManager->expects($this->once())->method('createEntity')->willReturn(new PaymentExample());

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Charge('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;
        $stripeEvent->data->object->status = 'open';
        $stripeEvent->data->object->currency = 'usd';
        $stripeEvent->data->object->total = 1111;

        $event = new StripeWebhookEvent('payment.created', $stripeEvent);

        $eventListener = new PaymentListener($this->paymentManager, $this->paymentTransformer);

        $eventListener->onPaymentCreateOrUpdate($event);
    }

    public function testOnPaymentDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(new PaymentExample());
        $this->paymentManager->method('getRepository')->willReturn($repository);
        $this->paymentManager->expects($this->once())->method('deleteEntity');

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Charge('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('payment.deleted', $stripeEvent);

        $eventListener = new PaymentListener($this->paymentManager, $this->paymentTransformer);

        $eventListener->onPaymentDeleted($event);
    }

    public function testOnPaymentDeletedAlreadyDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->paymentManager->method('getRepository')->willReturn($repository);

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Charge('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('payment.deleted', $stripeEvent);

        $eventListener = new PaymentListener($this->paymentManager, $this->paymentTransformer);

        $this->assertNull($eventListener->onPaymentDeleted($event));
    }
}
