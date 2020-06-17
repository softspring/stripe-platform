<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EventListener\Webhook;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\CustomerBundle\Manager\CustomerManager;
use Softspring\CustomerBundle\Manager\CustomerManagerInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\EventListener\Webhook\CustomerListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use Stripe\Customer;
use Stripe\Event;

class CustomerListenerTest extends TestCase
{
    /**
     * @var CustomerManagerInterface|MockObject
     */
    protected $customerManager;

    protected function setUp(): void
    {
        $this->customerManager = $this->createMock(CustomerManager::class);
    }

    public function testSubscribedEvents()
    {
        $subscribedEvents = CustomerListener::getSubscribedEvents();

        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.created', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.deleted', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.updated', $subscribedEvents);
    }

    public function testOnCustomerCreateOrUpdate()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->customerManager->method('getRepository')->willReturn($repository);
        $this->customerManager->expects($this->once())->method('saveEntity');
        $this->customerManager->expects($this->once())->method('createEntity')->willReturn(new CustomerFullExample());

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Customer('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('customer.created', $stripeEvent);

        $eventListener = new CustomerListener($this->customerManager, new CustomerTransformer());

        $eventListener->onCustomerCreateOrUpdate($event);
    }

    public function testOnCustomerDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(new CustomerFullExample());
        $this->customerManager->method('getRepository')->willReturn($repository);
        $this->customerManager->expects($this->once())->method('deleteEntity');

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Customer('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('customer.deleted', $stripeEvent);

        $eventListener = new CustomerListener($this->customerManager, new CustomerTransformer());

        $eventListener->onCustomerDeleted($event);
    }

    public function testOnCustomerDeletedAlreadyDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->customerManager->method('getRepository')->willReturn($repository);

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Customer('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('customer.deleted', $stripeEvent);

        $eventListener = new CustomerListener($this->customerManager, new CustomerTransformer());

        $this->assertNull($eventListener->onCustomerDeleted($event));
    }
}
