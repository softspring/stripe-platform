<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\CustomerBundle\Model\SourceInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;
use Softspring\PlatformBundle\Stripe\Adapter\SourceAdapter;

class SourceEntityListener
{
    /**
     * @var CustomerAdapter
     */
    protected $customerAdapter;

    /**
     * @var SourceAdapter
     */
    protected $sourceAdapter;

    /**
     * SourceEntityListener constructor.
     *
     * @param CustomerAdapter $customerAdapter
     * @param SourceAdapter   $sourceAdapter
     */
    public function __construct(CustomerAdapter $customerAdapter, SourceAdapter $sourceAdapter)
    {
        $this->customerAdapter = $customerAdapter;
        $this->sourceAdapter = $sourceAdapter;
    }

    /**
     * @param SourceInterface|PlatformObjectInterface $source
     * @param LifecycleEventArgs                      $eventArgs
     */
    public function prePersist(SourceInterface $source, LifecycleEventArgs $eventArgs)
    {
        if ($source->isPlatformWebhooked()) {
            return;
        }

        $this->sourceAdapter->create($source);
    }

    /**
     * @param SourceInterface|PlatformObjectInterface $source
     * @param PreUpdateEventArgs                      $eventArgs
     */
    public function preUpdate(SourceInterface $source, PreUpdateEventArgs $eventArgs)
    {
        if ($source->isPlatformWebhooked()) {
            return;
        }

//        if (!$source->getPlatformId()) {
//            $this->sourceAdapter->create($source);
//        } else {
//            $this->sourceAdapter->update($source);
//        }
    }

    /**
     * @param SourceInterface|PlatformObjectInterface $source
     * @param LifecycleEventArgs                      $eventArgs
     */
    public function preRemove(SourceInterface $source, LifecycleEventArgs $eventArgs)
    {
        if ($source->isPlatformWebhooked()) {
            return;
        }

//        if ($source->getPlatformId()) {
//            try {
//                $this->sourceAdapter->delete($source);
//            } catch (NotFoundInPlatform $e) {
//                // nothing to do, it's already deleted
//            }
//        }
    }
}