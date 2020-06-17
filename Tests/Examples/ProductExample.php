<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Doctrine\Common\Collections\Collection;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectTrait;
use Softspring\SubscriptionBundle\Model\Product;

class ProductExample extends Product implements PlatformObjectInterface
{
    use PlatformObjectTrait;

    public function getPlans(): Collection
    {
        // TODO: Implement getPlans() method.
    }

    public function getPlatform(): ?int
    {
        // TODO: Implement getPlatform() method.
    }

    public function setPlatform(?int $platform): void
    {
        // TODO: Implement setPlatform() method.
    }
}