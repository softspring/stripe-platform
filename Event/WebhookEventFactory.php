<?php

namespace Softspring\PlatformBundle\Stripe\Event;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Event\WebhookEvent;
use Softspring\PlatformBundle\Event\WebhookEventFactoryInterface;
use Softspring\PlatformBundle\Provider\CredentialsProviderInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeCredentials;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;

class WebhookEventFactory implements WebhookEventFactoryInterface
{
    /**
     * @var CredentialsProviderInterface
     */
    protected $credentialsProvider;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * WebhookEventFactory constructor.
     *
     * @param CredentialsProviderInterface $credentialsProvider
     * @param LoggerInterface|null         $logger
     */
    public function __construct(CredentialsProviderInterface $credentialsProvider, ?LoggerInterface $logger)
    {
        $this->credentialsProvider = $credentialsProvider;
        $this->logger = $logger;
    }

    public function support(Request $request): bool
    {
        return true;
    }

    public function create(Request $request): WebhookEvent
    {
        try {
            /** @var StripeCredentials $credentials */
            $credentials = $this->credentialsProvider->getCredentialsFromWebhook($request);

            if (null === ($payload = json_decode($request->getContent(), true))) {
                throw new \UnexpectedValueException('Bad JSON body from Stripe!');
            }
            $sig_header = $request->server->get('HTTP_STRIPE_SIGNATURE');
            $event = Webhook::constructEvent($request->getContent(), $sig_header, $credentials->getWebhookSigningSecret());
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            throw $e;
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            throw $e;
        }

        $this->logger && $this->logger->info(sprintf('Stripe webhook received: %s, event: %s', $event->id, $event->type));

        return new StripeWebhookEvent($event->type, $event);
    }
}