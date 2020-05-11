<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Softspring\CustomerBundle\Event\NotifyEvent;
use Softspring\PlatformBundle\Adapter\NotifyAdapterInterface;
use Softspring\PlatformBundle\PlatformInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;

class NotifyAdapter extends AbstractStripeAdapter implements NotifyAdapterInterface
{
    /**
     * @param Request $request
     *
     * @return NotifyEvent
     * @throws SignatureVerificationException
     * @throws \Softspring\PlatformBundle\Exception\PlatformException
     */
    public function createEvent(Request $request): NotifyEvent
    {
        $this->initStripe();


    }

}