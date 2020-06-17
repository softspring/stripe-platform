<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Transformer;

use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PlanExample;
use Softspring\PlatformBundle\Stripe\Transformer\PlanTransformer;
use PHPUnit\Framework\TestCase;
use Softspring\SubscriptionBundle\Manager\PlanManager;
use Softspring\SubscriptionBundle\Manager\PlanManagerInterface;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Stripe\Plan;

class PlanTransformerTest extends TestCase
{
    /**
     * @var MockObject|PlanManagerInterface
     */
    protected $planManager;

    protected function setUp(): void
    {
        $this->planManager = $this->createMock(PlanManager::class);
    }

    public function testSupports()
    {
        $transformer = new PlanTransformer($this->planManager);

        $this->assertFalse($transformer->supports(new \stdClass()));
        $this->assertTrue($transformer->supports($this->createMock(PlanInterface::class)));
    }

    public function testTransform()
    {
        $transformer = new PlanTransformer($this->planManager);

        $plan = new PlanExample();
        $plan->setPlatformId('plan_example');
        $plan->setName('Plan example');
        $plan->setCurrency('EUR');
        $plan->setAmount(15.00);
        $plan->setActive(true);
        $plan->setInterval(PlanInterface::INTERVAL_YEAR);
        $plan->setIntervalCount(2);

        $this->assertEquals([
            'plan' => [
                'currency' => 'eur',
                'nickname' => 'Plan example',
                'active' => true,
                'amount' => 1500,
                'interval' => 'year',
                'interval_count' => 2,
                'id' => 'plan_example',
            ],
        ], $transformer->transform($plan, 'create'));
    }

    public function testReverseTransform()
    {
        $this->planManager->method('createEntity')->willReturn(new PlanExample());

        $transformer = new PlanTransformer($this->planManager);

        $stripePlan = new Plan('plan_xxxxxxx');
        $stripePlan->nickname = 'Test plan';
        $stripePlan->amount = 1099;
        $stripePlan->currency = 'usd';
        $stripePlan->interval = 'month';
        $stripePlan->interval_count = 3;
        $stripePlan->active = true;

        $plan = $transformer->reverseTransform($stripePlan, null);

        $this->assertEquals('plan_xxxxxxx', $plan->getPlatformId());
        $this->assertEquals('Test plan', $plan->getName());
        $this->assertEquals('USD', $plan->getCurrency());
        $this->assertEquals(10.99, $plan->getAmount());
        $this->assertEquals(PlanInterface::INTERVAL_MONTH, $plan->getInterval());
        $this->assertEquals(3, $plan->getIntervalCount());
    }
}
