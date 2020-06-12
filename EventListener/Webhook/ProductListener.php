<?php

namespace Softspring\PlatformBundle\Stripe\EventListener\Webhook;

use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Transformer\ProductTransformer;
use Softspring\SubscriptionBundle\Manager\ProductManagerInterface;
use Softspring\SubscriptionBundle\Model\ProductInterface;
use Stripe\Event;
use Stripe\Product;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductListener implements EventSubscriberInterface
{
    /**
     * @var ProductManagerInterface|null
     */
    protected $productManager;

    /**
     * @var ProductTransformer
     */
    protected $productTransformer;

    /**
     * ProductListener constructor.
     *
     * @param ProductManagerInterface|null $productManager
     * @param ProductTransformer           $productTransformer
     */
    public function __construct(?ProductManagerInterface $productManager, ProductTransformer $productTransformer)
    {
        $this->productManager = $productManager;
        $this->productTransformer = $productTransformer;
    }

    public static function getSubscribedEvents()
    {
        return [
            // @see https://stripe.com/docs/api/events/types#event_types-product.created
            // data.object is a product
            // Occurs whenever a product is created.
            'sfs_platform.stripe_webhook.product.created' => [['onProductCreateOrUpdate', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-product.deleted
            // data.object is a product
            // Occurs whenever a product is deleted.
            'sfs_platform.stripe_webhook.product.deleted' => [['onProductDeleted', 0]],

            // @see https://stripe.com/docs/api/events/types#event_types-product.updated
            // data.object is a product
            // Occurs whenever a product is updated.
            'sfs_platform.stripe_webhook.product.updated' => [['onProductCreateOrUpdate', 0]],
        ];
    }

    public function onProductCreateOrUpdate(StripeWebhookEvent $event)
    {
        if (!$this->productManager) {
            return;
        }

        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Product $stripeProduct */
        $stripeProduct = $stripeEvent->data->object;

        /** @var ProductInterface|PlatformObjectInterface $dbProduct */
        if (! ($dbProduct = $this->productManager->getRepository()->findOneByPlatformId($stripeProduct->id))) {
            $dbProduct = $this->productManager->createEntity();
        }

        $this->productTransformer->reverseTransform($stripeProduct, $dbProduct);
        $dbProduct->setPlatformWebhooked(true);

        $this->productManager->saveEntity($dbProduct);
    }

    public function onProductDeleted(StripeWebhookEvent $event)
    {
        if (!$this->productManager) {
            return;
        }

        /** @var Event $stripeEvent */
        $stripeEvent = $event->getData();
        /** @var Product $stripeProduct */
        $stripeProduct = $stripeEvent->data->object;

        /** @var ProductInterface|PlatformObjectInterface $dbProduct */
        if (! ($dbProduct = $this->productManager->getRepository()->findOneByPlatformId($stripeProduct->id))) {
            return;
        }

        $this->productManager->deleteEntity($dbProduct);
    }
}