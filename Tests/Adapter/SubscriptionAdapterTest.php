<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;
use Softspring\PlatformBundle\Stripe\Adapter\SubscriptionAdapter;
use Softspring\PlatformBundle\Stripe\Client\StripeClient;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerBaseExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PlanExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\SubscriptionExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\SubscriptionSinglePlanExample;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use Softspring\PlatformBundle\Stripe\Transformer\SubscriptionTransformer;
use Softspring\SubscriptionBundle\Manager\PlanManagerInterface;
use Softspring\SubscriptionBundle\Manager\SubscriptionItemManagerInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionInterface;
use Stripe\Collection;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Subscription;
use Stripe\SubscriptionItem;

class SubscriptionAdapterTest extends AbstractStripeAdapterTest
{
    /**
     * @var SubscriptionAdapter
     */
    protected $subscriptionAdapter;

    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var StripeClient
     */
    protected $stripeClient;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SubscriptionItemManagerInterface
     */
    protected $itemManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PlanManagerInterface
     */
    protected $planManager;

    protected function setUp(): void
    {
        $this->stripeClient = $this->createMock(StripeClient::class);

        $this->stripeClientProvider = $this->createMock(StripeClientProvider::class);
        $this->stripeClientProvider->method('getClient')->willReturn($this->stripeClient);

        $this->itemManager = $this->createMock(SubscriptionItemManagerInterface::class);
        $this->planManager = $this->createMock(PlanManagerInterface::class);

        $this->subscriptionAdapter = new SubscriptionAdapter($this->stripeClientProvider, new SubscriptionTransformer($this->itemManager, $this->planManager), null);
    }

    public function testGetExisting()
    {
        $subscription = new SubscriptionSinglePlanExample();

        $this->stripeClient->method('subscriptionRetrieve')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'src_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at' => null,
            'cancel_at_period_end' => false,
        ])));

        $this->subscriptionAdapter->get($subscription);
        $this->assertEquals('src_test', $subscription->getPlatformId());
        $this->assertEquals(true, $subscription->isTestMode());
        $this->assertEquals(false, $subscription->isPlatformConflict());
        $this->assertEquals($startDate->format('H:i:s d-m-Y'), $subscription->getStartDate()->format('H:i:s d-m-Y'));
        $this->assertEquals($endDate->format('H:i:s d-m-Y'), $subscription->getEndDate()->format('H:i:s d-m-Y'));
        $this->assertEquals(SubscriptionInterface::STATUS_ACTIVE, $subscription->getStatus());
        $this->assertEquals('active', $subscription->getStatusString());
    }

    public function testCreate()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $plan = new PlanExample();
        $plan->setPlatformId('plan_test');

        $subscription = new SubscriptionSinglePlanExample();
        $subscription->setCustomer($customer);
        $subscription->setPlan($plan);

        $this->stripeClient->method('subscriptionCreate')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at' => null,
            'cancel_at_period_end' => false,
        ])));

        $this->subscriptionAdapter->create($subscription);

        $this->assertEquals('sub_test', $subscription->getPlatformId());
        $this->assertEquals(true, $subscription->isTestMode());
        $this->assertEquals(false, $subscription->isPlatformConflict());
        $this->assertEquals($startDate->format('H:i:s d-m-Y'), $subscription->getStartDate()->format('H:i:s d-m-Y'));
        $this->assertEquals($endDate->format('H:i:s d-m-Y'), $subscription->getEndDate()->format('H:i:s d-m-Y'));
        $this->assertEquals(SubscriptionInterface::STATUS_ACTIVE, $subscription->getStatus());
        $this->assertEquals('active', $subscription->getStatusString());
    }

    public function testCancel()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $plan = new PlanExample();
        $plan->setPlatformId('plan_test');

        $subscription = new SubscriptionSinglePlanExample();
        $subscription->setCustomer($customer);
        $subscription->setPlan($plan);

        $this->stripeClient->method('subscriptionRetrieve')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at' => null,
            'cancel_at_period_end' => false,
        ])));

        $this->stripeClient->method('subscriptionCancel')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'canceled',
            'cancel_at' => null,
            'cancel_at_period_end' => false,
        ])));

        $this->subscriptionAdapter->cancel($subscription);

        $this->assertEquals('sub_test', $subscription->getPlatformId());
        $this->assertEquals(true, $subscription->isTestMode());
        $this->assertEquals(false, $subscription->isPlatformConflict());
        $this->assertEquals($startDate->format('H:i:s d-m-Y'), $subscription->getStartDate()->format('H:i:s d-m-Y'));
        $this->assertEquals($endDate->format('H:i:s d-m-Y'), $subscription->getEndDate()->format('H:i:s d-m-Y'));
        $this->assertEquals(SubscriptionInterface::STATUS_EXPIRED, $subscription->getStatus());
        $this->assertEquals('expired', $subscription->getStatusString());
    }

    public function testCancelRenovation()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $plan = new PlanExample();
        $plan->setPlatformId('plan_test');

        $subscription = new SubscriptionSinglePlanExample();
        $subscription->setCustomer($customer);
        $subscription->setPlan($plan);

        $this->stripeClient->method('subscriptionRetrieve')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at' => null,
            'cancel_at_period_end' => false,
        ])));

        $this->stripeClient->method('save')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at_period_end' => true,
            'cancel_at' => ($endDate = new \DateTime('+1 month'))->format('U'),
        ])));

        $this->subscriptionAdapter->cancelRenovation($subscription);

        $this->assertEquals('sub_test', $subscription->getPlatformId());
        $this->assertEquals(true, $subscription->isTestMode());
        $this->assertEquals(false, $subscription->isPlatformConflict());
        $this->assertEquals($startDate->format('H:i:s d-m-Y'), $subscription->getStartDate()->format('H:i:s d-m-Y'));
        $this->assertEquals($endDate->format('H:i:s d-m-Y'), $subscription->getEndDate()->format('H:i:s d-m-Y'));
        $this->assertEquals(SubscriptionInterface::STATUS_CANCELED, $subscription->getStatus());
        $this->assertEquals('canceled', $subscription->getStatusString());
    }

    public function testUncancelRenovation()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $plan = new PlanExample();
        $plan->setPlatformId('plan_test');

        $subscription = new SubscriptionSinglePlanExample();
        $subscription->setCustomer($customer);
        $subscription->setPlan($plan);
        $subscription->setStatus(SubscriptionInterface::STATUS_CANCELED);

        $this->stripeClient->method('subscriptionRetrieve')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at_period_end' => true,
            'cancel_at' => ($endDate = new \DateTime('+1 month'))->format('U'),
        ])));

        $this->stripeClient->method('save')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at' => null,
            'cancel_at_period_end' => false,
        ])));

        $this->subscriptionAdapter->uncancelRenovation($subscription);

        $this->assertEquals('sub_test', $subscription->getPlatformId());
        $this->assertEquals(true, $subscription->isTestMode());
        $this->assertEquals(false, $subscription->isPlatformConflict());
        $this->assertEquals($startDate->format('H:i:s d-m-Y'), $subscription->getStartDate()->format('H:i:s d-m-Y'));
        $this->assertEquals($endDate->format('H:i:s d-m-Y'), $subscription->getEndDate()->format('H:i:s d-m-Y'));
        $this->assertEquals(SubscriptionInterface::STATUS_ACTIVE, $subscription->getStatus());
        $this->assertEquals('active', $subscription->getStatusString());
    }

    public function testUpgradePlan()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $fromPlan = new PlanExample();
        $fromPlan->setPlatformId('plan_test_from');

        $toPlan = new PlanExample();
        $toPlan->setPlatformId('plan_test_to');

        $subscription = new SubscriptionSinglePlanExample();
        $subscription->setCustomer($customer);
        $subscription->setPlan($toPlan);
        $subscription->setStatus(SubscriptionInterface::STATUS_ACTIVE);

        $this->stripeClient->method('subscriptionRetrieve')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at_period_end' => false,
            'cancel_at' => null,
            'items' => $this->createStripeCollectionObject([$this->createStripeObject(SubscriptionItem::class, ['id' => 'si_test', 'plan' => 'plan_test_from' ])])
        ])));

        $this->stripeClient->method('save')->will($this->returnValue($this->createStripeObject(Subscription::class, [
            'id' => 'sub_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'current_period_start' => ($startDate = new \DateTime('now'))->format('U'),
            'current_period_end' => ($endDate = new \DateTime('+1 month'))->format('U'),
            'status' => 'active',
            'cancel_at_period_end' => false,
            'cancel_at' => null,
            'items' => $this->createStripeCollectionObject([$this->createStripeObject(SubscriptionItem::class, ['id' => 'si_test', 'plan' => 'plan_test_to' ])])
        ])));

        $this->subscriptionAdapter->upgradePlan($subscription, $fromPlan, $toPlan);

        $this->assertEquals('sub_test', $subscription->getPlatformId());
        $this->assertEquals(true, $subscription->isTestMode());
        $this->assertEquals(false, $subscription->isPlatformConflict());
        $this->assertEquals($startDate->format('H:i:s d-m-Y'), $subscription->getStartDate()->format('H:i:s d-m-Y'));
        $this->assertEquals($endDate->format('H:i:s d-m-Y'), $subscription->getEndDate()->format('H:i:s d-m-Y'));
        $this->assertEquals(SubscriptionInterface::STATUS_ACTIVE, $subscription->getStatus());
        $this->assertEquals('active', $subscription->getStatusString());
    }
}
