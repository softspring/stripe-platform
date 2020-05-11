<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\CustomerBundle\Model\AddressInterface;
use Softspring\CustomerBundle\Model\CustomerBillingAddressInterface;
use Softspring\PlatformBundle\Adapter\CustomerAdapterInterface;
use Softspring\PlatformBundle\Adapter\AddressAdapterInterface;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;

class AddressEntityListener
{
    /**
     * @var CustomerAdapterInterface
     */
    protected $customerAdapter;

    /**
     * @var AddressAdapterInterface
     */
    protected $addressAdapter;

    /**
     * StripeAddressEntityListener constructor.
     *
     * @param CustomerAdapterInterface $customerAdapter
     * @param AddressAdapterInterface  $addressAdapter
     */
    public function __construct(CustomerAdapterInterface $customerAdapter, AddressAdapterInterface $addressAdapter)
    {
        $this->customerAdapter = $customerAdapter;
        $this->addressAdapter = $addressAdapter;
    }

    /**
     * @param AddressInterface   $address
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(AddressInterface $address, LifecycleEventArgs $eventArgs)
    {

    }

    /**
     * @param AddressInterface   $address
     * @param PreUpdateEventArgs $eventArgs
     */
    public function preUpdate(AddressInterface $address, PreUpdateEventArgs $eventArgs)
    {
        $customer = $address->getCustomer();

        if (!  $customer instanceof CustomerBillingAddressInterface) {
            return;
        }

        if ($customer->getBillingAddress() === $address) {
            $this->customerAdapter->update($customer);
        }

//        if (!$address->getPlatformId()) {
//            $this->addressAdapter->create($address);
//        } else {
//            $this->addressAdapter->update($address);
//        }
    }

    /**
     * @param AddressInterface   $address
     * @param LifecycleEventArgs $eventArgs
     */
    public function preRemove(AddressInterface $address, LifecycleEventArgs $eventArgs)
    {
//        if ($address->getPlatformId()) {
//            try {
//                $this->addressAdapter->delete($address);
//            } catch (NotFoundInPlatform $e) {
//                // nothing to do, it's already deleted
//            }
//        }
    }
}