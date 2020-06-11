<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\PlanAdapter;
use Softspring\SubscriptionBundle\Model\PlanInterface;

class PlanEntityListener
{
    /**
     * @var PlanAdapter
     */
    protected $planAdapter;

    /**
     * PlanEntityListener constructor.
     *
     * @param PlanAdapter $planAdapter
     */
    public function __construct(PlanAdapter $planAdapter)
    {
        $this->planAdapter = $planAdapter;
    }

    /**
     * @param PlanInterface|PlatformObjectInterface $plan
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(PlanInterface $plan, LifecycleEventArgs $eventArgs)
    {
        if ($plan->isPlatformWebhooked()) {
            return;
        }

        $this->planAdapter->create($plan);
    }

    /**
     * @param PlanInterface|PlatformObjectInterface $plan
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(PlanInterface $plan, PreUpdateEventArgs $eventArgs)
    {
        if ($plan->isPlatformWebhooked()) {
            return;
        }

        if (!$plan->getPlatformId()) {
            $this->planAdapter->create($plan);
        } else {
            $this->planAdapter->update($plan);
        }
    }

    /**
     * @param PlanInterface|PlatformObjectInterface $plan
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(PlanInterface $plan, LifecycleEventArgs $eventArgs)
    {
        if ($plan->isPlatformWebhooked()) {
            return;
        }

        if ($plan->getPlatformId()) {
            try {
                $this->planAdapter->delete($plan);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}