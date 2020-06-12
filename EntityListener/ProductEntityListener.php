<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\ProductAdapter;
use Softspring\SubscriptionBundle\Model\ProductInterface;

class ProductEntityListener
{
    /**
     * @var ProductAdapter
     */
    protected $productAdapter;

    /**
     * ProductEntityListener constructor.
     *
     * @param ProductAdapter $productAdapter
     */
    public function __construct(ProductAdapter $productAdapter)
    {
        $this->productAdapter = $productAdapter;
    }

    /**
     * @param ProductInterface|PlatformObjectInterface $product
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(ProductInterface $product, LifecycleEventArgs $eventArgs)
    {
        if ($product->isPlatformWebhooked()) {
            return;
        }

        $this->productAdapter->create($product);
    }

    /**
     * @param ProductInterface|PlatformObjectInterface $product
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(ProductInterface $product, PreUpdateEventArgs $eventArgs)
    {
        if ($product->isPlatformWebhooked()) {
            return;
        }

        if (!$product->getPlatformId()) {
            $this->productAdapter->create($product);
        } else {
            $this->productAdapter->update($product);
        }
    }

    /**
     * @param ProductInterface|PlatformObjectInterface $product
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(ProductInterface $product, LifecycleEventArgs $eventArgs)
    {
        if ($product->isPlatformWebhooked()) {
            return;
        }

        if ($product->getPlatformId()) {
            try {
                $this->productAdapter->delete($product);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}