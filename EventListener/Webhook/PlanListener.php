<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Transformer\PlanTransformer;
use Softspring\SubscriptionBundle\Manager\PlanManagerInterface;
use Softspring\SubscriptionBundle\Model\PlanInterface;
use Stripe\Event;
use Stripe\Plan;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PlanListener implements EventSubscriberInterface
{
    /**
     * @var PlanManagerInterface
     */
    protected $planManager;

    /**
     * @var PlanTransformer
     */
    protected $planTransformer;

    /**
     * PlanListener constructor.
     *
     * @param PlanManagerInterface $planManager
     * @param PlanTransformer      $planTransformer
     */
    public function __construct(PlanManagerInterface $planManager, PlanTransformer $planTransformer)
    {
        $this->planManager = $planManager;
        $this->planTransformer = $planTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [
            // @see https://stripe.com/docs/api/events/types#event_types-plan.created
            // data.object is a plan
            // Occurs whenever a plan is created.
            'sfs_platform.stripe_webhook.plan.created' => [['onPlanCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-plan.deleted
            // data.object is a plan
            // Occurs whenever a plan is deleted.
            'sfs_platform.stripe_webhook.plan.deleted' => [['onPlanDeleted', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-plan.updated
            // data.object is a plan
            // Occurs whenever a plan is updated.
            'sfs_platform.stripe_webhook.plan.updated' => [['onPlanCreateOrUpdate', 0]],
        ];
    }

    public function onPlanCreateOrUpdate(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Plan $stripePlan */
        $stripePlan = $stripeEvent->data->object;

        /** @var PlanInterface|PlatformObjectInterface $dbPlan */
        if (! ($dbPlan = $this->planManager->getRepository()->findOneByPlatformId($stripePlan->id))) {
            $dbPlan = $this->planManager->createEntity();
        }

        $this->planTransformer->reverseTransform($stripePlan, $dbPlan);
        $dbPlan->setPlatformWebhooked(true);

        $this->planManager->saveEntity($dbPlan);
    }

    public function onPlanDeleted(StripeWebhookEvent $event)
    {
        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Plan $stripePlan */
        $stripePlan = $stripeEvent->data->object;

        /** @var PlanInterface|PlatformObjectInterface $dbPlan */
        if (! ($dbPlan = $this->planManager->getRepository()->findOneByPlatformId($stripePlan->id))) {
            return;
        }

        $this->planManager->deleteEntity($dbPlan);
    }
}