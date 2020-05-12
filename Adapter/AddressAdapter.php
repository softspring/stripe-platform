<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Softspring\CustomerBundle\Model\AddressInterface;
use Softspring\PlatformBundle\Adapter\AddressAdapterInterface;

class AddressAdapter implements AddressAdapterInterface
{
    /**
     * @inheritDoc
     */
    public function create(AddressInterface $address)
    {
        // nothing to do in stripe
        return null;
    }

    /**
     * @inheritDoc
     */
    public function get(AddressInterface $address): array
    {
        // nothing to do in stripe
        return [];
    }
}