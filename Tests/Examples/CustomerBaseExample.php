<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Examples;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Softspring\CustomerBundle\Model\Customer;
use Softspring\CustomerBundle\Model\PlatformObjectTrait;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionCustomerInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionInterface;

class CustomerBaseExample extends Customer implements SubscriptionCustomerInterface, PlatformObjectInterface
{
    use PlatformObjectTrait;

    public function getEmail(): ?string
    {
        return null;
    }

    public function getSubscriptions(): Collection
    {
        return new ArrayCollection();
    }

    public function addSubscription(SubscriptionInterface $subscription): void
    {

    }

    public function getActiveSubscriptions(): Collection
    {
        return new ArrayCollection();
    }
}