<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;

class CustomerEntityListener
{
    /**
     * @var CustomerAdapter
     */
    protected $customerAdapter;

    /**
     * CustomerEntityListener constructor.
     *
     * @param CustomerAdapter $customerAdapter
     */
    public function __construct(CustomerAdapter $customerAdapter)
    {
        $this->customerAdapter = $customerAdapter;
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(CustomerInterface $customer, LifecycleEventArgs $eventArgs)
    {
        $this->customerAdapter->create($customer);
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(CustomerInterface $customer, PreUpdateEventArgs $eventArgs)
    {
        if (!$customer->getPlatformId()) {
            $this->customerAdapter->create($customer);
        } else {
            $this->customerAdapter->update($customer);
        }
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(CustomerInterface $customer, LifecycleEventArgs $eventArgs)
    {
        if ($customer->getPlatformId()) {
            try {
                $this->customerAdapter->delete($customer);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}