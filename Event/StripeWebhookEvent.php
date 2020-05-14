<?php

namespace Softspring\PlatformBundle\Stripe\Event;

use Softspring\PlatformBundle\Event\WebhookEvent;

class StripeWebhookEvent extends WebhookEvent
{
    /**
     * StripeWebhookEvent constructor.
     *
     * @param string $eventName
     * @param        $platformData
     */
    public function __construct(string $eventName, $platformData)
    {
        parent::__construct('stripe', $eventName, $platformData);
    }
}