<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Softspring\PlatformBundle\Model\PlatformByObjectTrait;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;
use Softspring\SubscriptionBundle\Model\Plan;

class PlanExample extends Plan implements PlatformObjectInterface
{
    use PlatformObjectTrait;
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