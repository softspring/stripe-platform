<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\PlatformBundle\Model\PlatformByObjectTrait;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;
use Softspring\SubscriptionBundle\Model\Subscription;
use Softspring\SubscriptionBundle\Model\SubscriptionSinglePlanInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionSinglePlanTrait;

class SubscriptionSinglePlanExample extends Subscription implements SubscriptionSinglePlanInterface, PlatformObjectInterface
{
    use PlatformObjectTrait;
    use SubscriptionSinglePlanTrait;
    // use PlatformByObjectTrait;
    public function getPlatform(): ?int
    {
        // TODO: Implement getPlatform() method.
    }

    public function setPlatform(?int $platform): void
    {
        // TODO: Implement setPlatform() method.
    }
}