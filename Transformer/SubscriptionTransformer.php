<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\SubscriptionBundle\Manager\PlanManagerInterface;
use Softspring\SubscriptionBundle\Manager\SubscriptionItemManagerInterface;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionItemInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionMultiPlanInterface;
use Softspring\SubscriptionBundle\Model\SubscriptionSinglePlanInterface;
use Stripe\Subscription;
use Stripe\SubscriptionItem as StripeSubscriptionItem;

class SubscriptionTransformer extends AbstractPlatformTransformer
{
    const MAPPING_STATUSES = [
        'active' => SubscriptionInterface::STATUS_ACTIVE,
        'incomplete' => SubscriptionInterface::STATUS_ACTIVE,
        'trialing' => SubscriptionInterface::STATUS_TRIALING,
        'unpaid' => SubscriptionInterface::STATUS_UNPAID,
        'past_due' => SubscriptionInterface::STATUS_UNPAID,
        'incomplete_expired' => SubscriptionInterface::STATUS_EXPIRED,
        'canceled' => SubscriptionInterface::STATUS_EXPIRED,
    ];

    /**
     * @var SubscriptionItemManagerInterface
     */
    protected $itemManager;

    /**
     * @var PlanManagerInterface
     */
    protected $planManager;

    /**
     * SubscriptionTransformer constructor.
     *
     * @param SubscriptionItemManagerInterface $itemManager
     * @param PlanManagerInterface             $planManager
     */
    public function __construct(SubscriptionItemManagerInterface $itemManager, PlanManagerInterface $planManager)
    {
        $this->itemManager = $itemManager;
        $this->planManager = $planManager;
    }

    public function supports($subscription): bool
    {
        return $subscription instanceof SubscriptionInterface;
    }

    public function transform($subscription, string $action = '', array $options = []): array
    {
        $data = [
            'subscription' => [
                'items' => [],
            ],
        ];

        if ($action == 'create') {
            $data['subscription']['customer'] = $subscription->getCustomer()->getPlatformId();
        }

        if ($subscription instanceof SubscriptionMultiPlanInterface) {
            foreach ($subscription->getItems() as $item) {
                if ($action == 'create' || ($action == 'update' && !$item->getPlatformId())) {
                    $data['subscription']['items'][] = [
                        'plan' => $item->getPlan()->getPlatformId(),
                        'quantity' => $item->getQuantity(),
                    ];
                } elseif ($action == 'update' && $item->getPlatformId()) {
                    $data['subscription']['items'][] = [
                        'id' => $item->getPlatformId(),
                        'plan' => $item->getPlan()->getPlatformId(),
                        'quantity' => $item->getQuantity(),
                    ];
                }
            }
        } elseif ($subscription instanceof SubscriptionSinglePlanInterface) {
            if ($action == 'create') {
                $data['subscription']['items'][] = [
                    'plan' => $subscription->getPlan()->getPlatformId(),
                ];
            } elseif ($action == 'upgrade') {
                $data['subscription'] = [
                    'proration_behavior' => 'create_prorations',
                    'items' => [
                        [
                            'id' => $options['stripeSubscription']->items->data[0]->id,
                            'plan' => $options['toPlan']->getPlatformId(),
                        ],
                    ],
                ];
            }
        }

        return $data;
    }

    /**
     * @param Subscription                                  $stripeSubscription
     * @param SubscriptionInterface|PlatformObjectInterface $subscription
     * @param string                                        $action
     *
     * @return object|void
     */
    public function reverseTransform($stripeSubscription, $subscription = null, string $action = '')
    {
        if (null === $subscription) {
            // TODO CALL MANAGER TO CREATE ONE CUSTOMER OBJECT
        }

        $this->checkSupports($subscription);

        $this->reverseTransformPlatformObject($subscription, $stripeSubscription);

        $subscription->setStartDate(\DateTime::createFromFormat('U', $stripeSubscription->current_period_start));
        $subscription->setEndDate(\DateTime::createFromFormat('U', $stripeSubscription->current_period_end));
        $subscription->setStatus(self::MAPPING_STATUSES[$stripeSubscription->status]);

        if (!empty($stripeSubscription->cancel_at)) {
            // FIX STATUS
            if (in_array($subscription->getStatus(), [SubscriptionInterface::STATUS_ACTIVE, SubscriptionInterface::STATUS_TRIALING])) {
                $subscription->setStatus(SubscriptionInterface::STATUS_CANCELED);
            }

            $subscription->setCancelScheduled(\DateTime::createFromFormat('U', $stripeSubscription->cancel_at));
        } else {
            $subscription->setCancelScheduled(null);
        }

        if ($subscription instanceof SubscriptionMultiPlanInterface) {
            foreach ($stripeSubscription->items as $itemStripe) {
                /** @var SubscriptionItemInterface|PlatformObjectInterface $subscriptionItem */
                $subscriptionItem = $this->getSubscriptionItem($subscription, $itemStripe);
                $this->reverseTransformPlatformObject($subscriptionItem, $itemStripe);
            }
        }
    }

    protected function getSubscriptionItem(SubscriptionInterface $subscription, StripeSubscriptionItem $itemStripe): SubscriptionItemInterface
    {
        if (!$subscription instanceof SubscriptionMultiPlanInterface) {
            throw new PlatformException('stripe', 'invalid_subscription_mapping_configuration');
        }

        foreach ($subscription->getItems() as $item) {
            if ($item->getPlatformId() == $itemStripe->id) {
                return $item;
            }
        }

        foreach ($subscription->getItems() as $item) {
            if ($item->getPlatformId()) {
                continue;
            }

            /** @var PlanInterface|PlatformObjectInterface $plan */
            $plan = $item->getPlan();
            if ($plan->getPlatformId() != $itemStripe->plan->id) {
                continue;
            }

            if ($item->getQuantity() != $itemStripe->quantity) {
                continue;
            }

            return $item;
        }

        $subscription->addItem($newItem = $this->itemManager->createEntity());
        $newItem->setQuantity($itemStripe->quantity);
        $newItem->setPlan($this->planManager->getRepository()->findOneByPlatformId($itemStripe->plan));

        return $newItem;
    }
}