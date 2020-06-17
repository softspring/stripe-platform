<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EventListener\Webhook;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\EventListener\Webhook\ProductListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\ProductExample;
use Softspring\PlatformBundle\Stripe\Transformer\ProductTransformer;
use Softspring\SubscriptionBundle\Manager\ProductManager;
use Softspring\SubscriptionBundle\Manager\ProductManagerInterface;
use Stripe\Product;
use Stripe\Event;

class ProductListenerTest extends TestCase
{
    /**
     * @var ProductManagerInterface|MockObject
     */
    protected $productManager;

    /**
     * @var ProductTransformer|MockObject
     */
    protected $productTransformer;

    protected function setUp(): void
    {
        $this->productManager = $this->createMock(ProductManager::class);
        $this->productTransformer = $this->createMock(ProductTransformer::class);
    }

    public function testSubscribedEvents()
    {
        $subscribedEvents = ProductListener::getSubscribedEvents();

        $this->assertArrayHasKey('sfs_platform.stripe_webhook.product.created', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.product.deleted', $subscribedEvents);
        $this->assertArrayHasKey('sfs_platform.stripe_webhook.product.updated', $subscribedEvents);
    }

    public function testOnProductCreateOrUpdate()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->productManager->method('getRepository')->willReturn($repository);
        $this->productManager->expects($this->once())->method('saveEntity');
        $this->productManager->expects($this->once())->method('createEntity')->willReturn(new ProductExample());

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Product('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('product.created', $stripeEvent);

        $eventListener = new ProductListener($this->productManager, $this->productTransformer);

        $eventListener->onProductCreateOrUpdate($event);
    }

    public function testOnProductDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(new ProductExample());
        $this->productManager->method('getRepository')->willReturn($repository);
        $this->productManager->expects($this->once())->method('deleteEntity');

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Product('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('product.deleted', $stripeEvent);

        $eventListener = new ProductListener($this->productManager, $this->productTransformer);

        $eventListener->onProductDeleted($event);
    }

    public function testOnProductDeletedAlreadyDeleted()
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('__call')->willReturn(null);
        $this->productManager->method('getRepository')->willReturn($repository);

        $stripeEvent = new Event();
        $stripeEvent->data = new \stdClass();
        $stripeEvent->data->object = new Product('cus_test');
        $stripeEvent->data->object->created = time();
        $stripeEvent->data->object->livemode = false;

        $event = new StripeWebhookEvent('product.deleted', $stripeEvent);

        $eventListener = new ProductListener($this->productManager, $this->productTransformer);

        $this->assertNull($eventListener->onProductDeleted($event));
    }
}
