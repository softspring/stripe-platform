<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Softspring\SubscriptionBundle\Manager\PlanManagerInterface;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Stripe\Plan;

class PlanTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    const INTERVAL_STATUSES = [
        'day' => PlanInterface::INTERVAL_DAY,
        'week' => PlanInterface::INTERVAL_WEEK,
        'month' => PlanInterface::INTERVAL_MONTH,
        'year' => PlanInterface::INTERVAL_YEAR,
    ];

    /**
     * @var PlanManagerInterface
     */
    protected $planManager;

    /**
     * PlanTransformer constructor.
     *
     * @param PlanManagerInterface $planManager
     */
    public function __construct(PlanManagerInterface $planManager)
    {
        $this->planManager = $planManager;
    }

    public function supports($plan): bool
    {
        return $plan instanceof PlanInterface;
    }

    /**
     * @param PlanInterface|PlatformObjectInterface $plan
     * @param string                                $action
     *
     * @return array
     * @throws TransformException
     */
    public function transform($plan, string $action = ''): array
    {
        $this->checkSupports($plan);

        $data = [
            'plan' => [
            ],
        ];

        if ($action === 'create') {
            if ($plan->getPlatformId()) {
                $data['plan']['id'] = $plan->getPlatformId();
            }

            $data['plan']['nickname'] = $plan->getName();
            $data['plan']['active'] = $plan->isActive();
            $data['plan']['amount'] = (int) round($plan->getAmount()*100);
            $data['plan']['currency'] = strtolower($plan->getCurrency());
            $data['plan']['interval'] = array_search($plan->getInterval(), self::INTERVAL_STATUSES);
            $data['plan']['interval_count'] = $plan->getIntervalCount();
        }

        return $data;
    }

    /**
     * @param Plan                                       $stripePlan
     * @param PlanInterface|PlatformObjectInterface|null $plan
     * @param string                                     $action
     *
     * @return PlanInterface
     * @throws TransformException
     */
    public function reverseTransform($stripePlan, $plan = null, string $action = ''): PlanInterface
    {
        if (null === $plan) {
            $plan = $this->planManager->createEntity();
        }

        $this->checkSupports($plan);
        $this->reverseTransformPlatformObject($plan, $stripePlan);

        $plan->setName($stripePlan->nickname);
        $plan->setCurrency(strtoupper($stripePlan->currency));
        $plan->setAmount($stripePlan->amount/100);
        $plan->setInterval(self::INTERVAL_STATUSES[$stripePlan->interval]);
        $plan->setIntervalCount($stripePlan->interval_count);
        $plan->setActive($stripePlan->active);

        // aggregate_usage
        // billing_scheme
        // created
        // product
        // tiers
        // tiers_mode
        // transform_usage
        // trial_period_days
        // usage_type

        return $plan;
    }
}