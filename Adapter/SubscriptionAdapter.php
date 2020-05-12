<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Adapter\SubscriptionAdapterInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\SubscriptionTransformer;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionInterface;
use Stripe\Subscription;

class SubscriptionAdapter implements SubscriptionAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var SubscriptionTransformer
     */
    protected $subscriptionTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * SubscriptionAdapter constructor.
     *
     * @param StripeClientProvider    $stripeClientProvider
     * @param SubscriptionTransformer $subscriptionTransformer
     * @param LoggerInterface|null    $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, SubscriptionTransformer $subscriptionTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->subscriptionTransformer = $subscriptionTransformer;
        $this->logger = $logger;
    }

    /**
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     *
     * @return Subscription
     * @throws PlatformException
     */
    public function create(SubscriptionInterface $subscription)
    {
        $data = $this->subscriptionTransformer->transform($subscription, 'create');

        $subscriptionStripe = $this->stripeClientProvider->getClient($subscription)->subscriptionCreate($data['subscription']);

        $this->logger && $this->logger->info(sprintf('Stripe created subscription %s', $subscriptionStripe->id));

        $this->subscriptionTransformer->reverseTransform($subscriptionStripe, $subscription);

        return $subscriptionStripe;
    }

    /**
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     *
     * @return Subscription
     * @throws PlatformException
     */
    public function get(SubscriptionInterface $subscription)
    {
        $subscriptionStripe = $this->stripeClientProvider->getClient($subscription)->subscriptionRetrieve([
            'id' => $subscription->getPlatformId(),
        ]);

        $this->subscriptionTransformer->reverseTransform($subscriptionStripe, $subscription);

        return $subscriptionStripe;
    }

    /**
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     * @param PlanInterface|PlatformObjectInterface         $fromPlan
     * @param PlanInterface|PlatformObjectInterface         $toPlan
     *
     * @return Subscription
     */
    public function upgradePlan(SubscriptionInterface $subscription, PlanInterface $fromPlan, PlanInterface $toPlan)
    {
        $stripeSubscription = $this->get($subscription);

        $data = $this->subscriptionTransformer->transform($subscription, 'upgrade', [
            'stripeSubscription' => $stripeSubscription,
            'fromPlan' => $fromPlan,
            'toPlan' => $toPlan,
        ]);

        $stripeSubscription->updateAttributes($data['subscription']);
        $stripeSubscription = $this->stripeClientProvider->getClient($subscription)->save($stripeSubscription);

        $this->logger && $this->logger->info(sprintf('Stripe %s subscription upgraded plan to %s', $subscription->getPlatformId(), $toPlan->getPlatformId()));

        return $stripeSubscription;
    }

    /**
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     *
     * @return Subscription
     * @throws PlatformException
     */
    public function cancelRenovation(SubscriptionInterface $subscription)
    {
        $subscriptionStripe = $this->get($subscription);
        $subscriptionStripe->updateAttributes(['cancel_at_period_end' => true]);
        $subscriptionStripe = $this->stripeClientProvider->getClient($subscription)->save($subscriptionStripe);
        $this->subscriptionTransformer->reverseTransform($subscriptionStripe, $subscription);

        $this->logger && $this->logger->info(sprintf('Stripe cancel renewal for %s', $subscription->getPlatformId()));

        return $subscriptionStripe;
    }

    /**
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     *
     * @return Subscription
     * @throws PlatformException
     */
    public function uncancelRenovation(SubscriptionInterface $subscription)
    {
        $subscriptionStripe = $this->get($subscription);
        $subscriptionStripe->updateAttributes(['cancel_at_period_end' => false]);
        $subscriptionStripe = $this->stripeClientProvider->getClient($subscription)->save($subscriptionStripe);
        $this->subscriptionTransformer->reverseTransform($subscriptionStripe, $subscription);

        $this->logger && $this->logger->info(sprintf('Stripe un-cancel renewal for %s', $subscription->getPlatformId()));

        return $subscriptionStripe;
    }

    /**
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     *
     * @return mixed|Subscription
     * @throws PlatformException
     */
    public function cancel(SubscriptionInterface $subscription)
    {
        $subscriptionStripe = $this->get($subscription);
        $subscriptionStripe = $this->stripeClientProvider->getClient($subscription)->subscriptionCancel($subscriptionStripe);
        $this->subscriptionTransformer->reverseTransform($subscriptionStripe, $subscription);

        $this->logger && $this->logger->info(sprintf('Stripe cancel subscription %s', $subscription->getPlatformId()));

        return $subscriptionStripe;
    }

    /**
     * @inheritDoc
     */
    public function trial(SubscriptionInterface $subscription)
    {
        // TODO
    }

    /**
     * @inheritDoc
     */
    public function finishTrial(SubscriptionInterface $subscription)
    {
        // TODO
    }
}