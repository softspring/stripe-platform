<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Softspring\PlatformBundle\Adapter\PlanAdapterInterface;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Softspring\PlatformBundle\PlatformInterface;
use Stripe\Plan as StripePlan;

class PlanAdapter implements PlanAdapterInterface
{
    public function create(PlanInterface $plan): void
    {
        // TODO: Implement create() method.
    }

    public function update(PlanInterface $plan): void
    {
        // TODO: Implement update() method.
    }

    public function delete(PlanInterface $plan): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function list(): array
    {
        try {
            $this->initStripe();
            return StripePlan::all()->toArray();
        } catch (\Exception $e) {
            $this->attachStripeExceptions($e);
        }
    }
}