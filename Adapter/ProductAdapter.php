<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Softspring\PlatformBundle\Adapter\ProductAdapterInterface;
use Softspring\SubscriptionBundle\Model\ProductInterface;
use Softspring\PlatformBundle\PlatformInterface;
use Stripe\Product as StripeProduct;

class ProductAdapter implements ProductAdapterInterface
{
    public function create(ProductInterface $product): void
    {
        // TODO: Implement create() method.
    }

    public function update(ProductInterface $product): void
    {
        // TODO: Implement update() method.
    }

    public function delete(ProductInterface $product): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function list(): array
    {
        try {
            $this->initStripe();
            return StripeProduct::all()->toArray();
        } catch (\Exception $e) {
            $this->attachStripeExceptions($e);
        }
    }
}