<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EventListener\Webhook;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\EventListener\Webhook\PlanListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PlanExample;
use Softspring\PlatformBundle\Stripe\Transformer\PlanTransformer;
use Softspring\SubscriptionBundle\Manager\PlanManager;
use Softspring\SubscriptionBundle\Manager\PlanManagerInterface;
use Stripe\Plan;
use Stripe\Event;

class PlanListenerTest extends TestCase
{
    /**
     * @var PlanManagerInterface|MockObject
     */
    protected $planManager;

    /**
     * @var PlanTransformer|MockObject
     */
    protected $planTransformer;

    protected function setUp(): void
    {
        $this->planManager = $this->createMock(PlanManager::class);
        $this->planTransformer = $this->createMock(PlanTransformer::class);
    }

    public function testSubscribedEvents()
    {
        $subscribedEvents = PlanListener::getSubscribedEvents();

        $this->assertArrayHasKey('sfs_platform.stripe_webhook.plan.created', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.plan.deleted', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.plan.updated', $subscribedEvents);
    }

    public function testOnPlanCreateOrUpdate()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->planManager->method('getRepository')->willReturn($repository);
        $this->planManager->expects($this->once())->method('saveEntity');
        $this->planManager->expects($this->once())->method('createEntity')->willReturn(new PlanExample());

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Plan('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('plan.created', $stripeEvent);

        $eventListener = new PlanListener($this->planManager, $this->planTransformer);

        $eventListener->onPlanCreateOrUpdate($event);
    }

    public function testOnPlanDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(new PlanExample());
        $this->planManager->method('getRepository')->willReturn($repository);
        $this->planManager->expects($this->once())->method('deleteEntity');

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Plan('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('plan.deleted', $stripeEvent);

        $eventListener = new PlanListener($this->planManager, $this->planTransformer);

        $eventListener->onPlanDeleted($event);
    }

    public function testOnPlanDeletedAlreadyDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->planManager->method('getRepository')->willReturn($repository);

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Plan('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('plan.deleted', $stripeEvent);

        $eventListener = new PlanListener($this->planManager, $this->planTransformer);

        $this->assertNull($eventListener->onPlanDeleted($event));
    }
}
