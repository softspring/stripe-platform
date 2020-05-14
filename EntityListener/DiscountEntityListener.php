<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\PaymentBundle\Model\DiscountInterface;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\DiscountAdapter;

class DiscountEntityListener
{
    /**
     * @var DiscountAdapter
     */
    protected $discountAdapter;

    /**
     * DiscountEntityListener constructor.
     *
     * @param DiscountAdapter $discountAdapter
     */
    public function __construct(DiscountAdapter $discountAdapter)
    {
        $this->discountAdapter = $discountAdapter;
    }

    /**
     * @param DiscountInterface|PlatformObjectInterface $discount
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(DiscountInterface $discount, LifecycleEventArgs $eventArgs)
    {
        if ($discount->isPlatformWebhooked()) {
            return;
        }

        $this->discountAdapter->create($discount);
    }

    /**
     * @param DiscountInterface|PlatformObjectInterface $discount
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(DiscountInterface $discount, PreUpdateEventArgs $eventArgs)
    {
        if ($discount->isPlatformWebhooked()) {
            return;
        }

        if (!$discount->getPlatformId()) {
            $this->discountAdapter->create($discount);
        } else {
            // $this->discountAdapter->update($discount);
        }
    }

    /**
     * @param DiscountInterface|PlatformObjectInterface $discount
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(DiscountInterface $discount, LifecycleEventArgs $eventArgs)
    {
        if ($discount->isPlatformWebhooked()) {
            return;
        }

        if ($discount->getPlatformId()) {
            try {
                // $this->discountAdapter->delete($discount);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}