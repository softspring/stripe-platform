<?php

namespace Softspring\PlatformBundle\Stripe\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Adapter\PaymentAdapter;

class PaymentEntityListener
{
    /**
     * @var PaymentAdapter
     */
    protected $paymentAdapter;

    /**
     * PaymentEntityListener constructor.
     *
     * @param PaymentAdapter $paymentAdapter
     */
    public function __construct(PaymentAdapter $paymentAdapter)
    {
        $this->paymentAdapter = $paymentAdapter;
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     * @param LifecycleEventArgs                       $eventArgs
     */
    public function prePersist(PaymentInterface $payment, LifecycleEventArgs $eventArgs)
    {
        if ($payment->isPlatformWebhooked()) {
            return;
        }

        $this->paymentAdapter->create($payment);
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     * @param PreUpdateEventArgs                       $eventArgs
     */
    public function preUpdate(PaymentInterface $payment, PreUpdateEventArgs $eventArgs)
    {
        if ($payment->isPlatformWebhooked()) {
            return;
        }

        if (!$payment->getPlatformId()) {
            $this->paymentAdapter->create($payment);
        } else {
            // $this->paymentAdapter->update($payment);
        }
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     * @param LifecycleEventArgs                       $eventArgs
     */
    public function preRemove(PaymentInterface $payment, LifecycleEventArgs $eventArgs)
    {
        if ($payment->isPlatformWebhooked()) {
            return;
        }

        if ($payment->getPlatformId()) {
            try {
                // $this->paymentAdapter->delete($payment);
            } catch (NotFoundInPlatform $e) {
                // nothing to do, it's already deleted
            }
        }
    }
}