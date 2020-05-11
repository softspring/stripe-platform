<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Manager\AdapterManagerInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;

class CustomerEntityListener
{
    /**
     * @var AdapterManagerInterface
     */
    protected $adapterManager;

    /**
     * CustomerEntityListener constructor.
     *
     * @param AdapterManagerInterface $adapterManager
     */
    public function __construct(AdapterManagerInterface $adapterManager)
    {
        $this->adapterManager = $adapterManager;
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(CustomerInterface $customer, LifecycleEventArgs $eventArgs)
    {
        if (! ($adapter = $this->adapterManager->get('stripe', 'customer'))) {
            return;
        }

        $adapter->create($customer);
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(CustomerInterface $customer, PreUpdateEventArgs $eventArgs)
    {
        if (! ($adapter = $this->adapterManager->get('stripe', 'customer'))) {
            return;
        }

        if (!$customer->getPlatformId()) {
            $adapter->create($customer);
        } else {
            $adapter->update($customer);
        }
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(CustomerInterface $customer, LifecycleEventArgs $eventArgs)
    {
        if (! ($adapter = $this->adapterManager->get('stripe', 'customer'))) {
            return;
        }

        if ($customer->getPlatformId()) {
            try {
                $adapter->delete($customer);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}