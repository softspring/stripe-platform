<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\PaymentBundle\Model\ConceptInterface;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\ConceptAdapter;

class ConceptEntityListener
{
    /**
     * @var ConceptAdapter
     */
    protected $conceptAdapter;

    /**
     * ConceptEntityListener constructor.
     *
     * @param ConceptAdapter $conceptAdapter
     */
    public function __construct(ConceptAdapter $conceptAdapter)
    {
        $this->conceptAdapter = $conceptAdapter;
    }

    /**
     * @param ConceptInterface|PlatformObjectInterface $concept
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function prePersist(ConceptInterface $concept, LifecycleEventArgs $eventArgs)
    {
        $this->conceptAdapter->create($concept);
    }

    /**
     * @param ConceptInterface|PlatformObjectInterface $concept
     * @param PreUpdateEventArgs                        $eventArgs
     */
    public function preUpdate(ConceptInterface $concept, PreUpdateEventArgs $eventArgs)
    {
        if (!$concept->getPlatformId()) {
            $this->conceptAdapter->create($concept);
        } else {
            // $this->conceptAdapter->update($concept);
        }
    }

    /**
     * @param ConceptInterface|PlatformObjectInterface $concept
     * @param LifecycleEventArgs                        $eventArgs
     */
    public function preRemove(ConceptInterface $concept, LifecycleEventArgs $eventArgs)
    {
        if ($concept->getPlatformId()) {
            try {
                // $this->conceptAdapter->delete($concept);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}