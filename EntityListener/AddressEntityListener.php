<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\CustomerBundle\Model\AddressInterface;
use Softspring\CustomerBundle\Model\CustomerBillingAddressInterface;
use Softspring\PlatformBundle\Stripe\Adapter\AddressAdapter;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;

class AddressEntityListener
{
    /**
     * @var CustomerAdapter
     */
    protected $customerAdapter;

    /**
     * @var AddressAdapter
     */
    protected $addressAdapter;

    /**
     * StripeAddressEntityListener constructor.
     *
     * @param CustomerAdapter $customerAdapter
     * @param AddressAdapter  $addressAdapter
     */
    public function __construct(CustomerAdapter $customerAdapter, AddressAdapter $addressAdapter)
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

        if (! $customer instanceof CustomerBillingAddressInterface) {
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