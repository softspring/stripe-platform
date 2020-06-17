<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EventListener\Webhook;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\EventListener\Webhook\SubscriptionListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\SubscriptionSinglePlanExample;
use Softspring\PlatformBundle\Stripe\Transformer\SubscriptionTransformer;
use Softspring\SubscriptionBundle\Manager\SubscriptionManager;
use Softspring\SubscriptionBundle\Manager\SubscriptionManagerInterface;
use Stripe\Subscription;
use Stripe\Event;

class SubscriptionListenerTest extends TestCase
{
    /**
     * @var SubscriptionManagerInterface|MockObject
     */
    protected $subscriptionManager;

    /**
     * @var SubscriptionTransformer|MockObject
     */
    protected $subscriptionTransformer;

    protected function setUp(): void
    {
        $this->subscriptionManager = $this->createMock(SubscriptionManager::class);
        $this->subscriptionTransformer = $this->createMock(SubscriptionTransformer::class);
    }

    public function testSubscribedEvents()
    {
        $subscribedEvents = SubscriptionListener::getSubscribedEvents();

        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.subscription.created', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.subscription.deleted', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.subscription.pending_update_applied', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.subscription.pending_update_expired', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.subscription.trial_will_end', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.customer.subscription.updated', $subscribedEvents);
    }

    public function testOnSubscriptionCreateOrUpdate()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->subscriptionManager->method('getRepository')->willReturn($repository);
        $this->subscriptionManager->expects($this->once())->method('saveEntity');
        $this->subscriptionManager->expects($this->once())->method('createEntity')->willReturn(new SubscriptionSinglePlanExample());

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Subscription('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('subscription.created', $stripeEvent);

        $eventListener = new SubscriptionListener($this->subscriptionManager, $this->subscriptionTransformer);

        $eventListener->onSubscriptionCreateOrUpdate($event);
    }

    public function testOnSubscriptionDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(new SubscriptionSinglePlanExample());
        $this->subscriptionManager->method('getRepository')->willReturn($repository);
        $this->subscriptionManager->expects($this->once())->method('deleteEntity');

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Subscription('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('subscription.deleted', $stripeEvent);

        $eventListener = new SubscriptionListener($this->subscriptionManager, $this->subscriptionTransformer);

        $eventListener->onSubscriptionDeleted($event);
    }

    public function testOnSubscriptionDeletedAlreadyDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->subscriptionManager->method('getRepository')->willReturn($repository);

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Subscription('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('subscription.deleted', $stripeEvent);

        $eventListener = new SubscriptionListener($this->subscriptionManager, $this->subscriptionTransformer);

        $this->assertNull($eventListener->onSubscriptionDeleted($event));
    }
}
