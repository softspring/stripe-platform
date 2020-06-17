<?php

namespace Softspring\PlatformBundle\Stripe\Tests\EntityListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Stripe\Adapter\ProductAdapter;
use Softspring\PlatformBundle\Stripe\EntityListener\ProductEntityListener;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Tests\Examples\ProductExample;

class ProductEntityListenerTest extends TestCase
{
    /**
     * @var ProductAdapter|MockObject
     */
    protected $productAdapter;

    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    protected function setUp(): void
    {
        $this->productAdapter = $this->createMock(ProductAdapter::class);
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testPrePersist()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $eventArgs = new LifecycleEventArgs($product, $this->em);

        $this->productAdapter->expects($this->once())->method('create');

        $listener->prePersist($product, $eventArgs);
    }

    public function testPrePersistWebhooked()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $product->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($product, $this->em);

        $this->productAdapter->expects($this->never())->method('create');

        $listener->prePersist($product, $eventArgs);
    }

    public function testPreUpdate()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($product, $this->em, $changeSet);

        $this->productAdapter->expects($this->once())->method('create');

        $listener->preUpdate($product, $eventArgs);
    }

    public function testPreUpdateExisting()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $product->setPlatformId('id');
        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($product, $this->em, $changeSet);

        $this->productAdapter->expects($this->once())->method('update');

        $listener->preUpdate($product, $eventArgs);
    }

    public function testPreUpdateWebhooked()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $product->setPlatformWebhooked(true);

        $changeSet = [];
        $eventArgs = new PreUpdateEventArgs($product, $this->em, $changeSet);

        $this->productAdapter->expects($this->never())->method('create');

        $listener->preUpdate($product, $eventArgs);
    }

    public function testPreRemoveMissing()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $eventArgs = new LifecycleEventArgs($product, $this->em);

        $this->productAdapter->expects($this->never())->method('delete');

        $listener->preRemove($product, $eventArgs);
    }

    public function testPreRemoveExisting()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $product->setPlatformId('id');
        $eventArgs = new LifecycleEventArgs($product, $this->em);

        $this->productAdapter->expects($this->once())->method('delete');

        $listener->preRemove($product, $eventArgs);
    }

    public function testPreRemoveWebhooked()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $product->setPlatformWebhooked(true);

        $eventArgs = new LifecycleEventArgs($product, $this->em);

        $this->productAdapter->expects($this->never())->method('delete');

        $listener->preRemove($product, $eventArgs);
    }

    public function testPreRemoveNotFoundInPlatform()
    {
        $listener = new ProductEntityListener($this->productAdapter);

        $product = new ProductExample();
        $product->setPlatformId('id');

        $eventArgs = new LifecycleEventArgs($product, $this->em);

        $this->productAdapter->method('delete')->willThrowException(new NotFoundInPlatform('stripe', 'Not found'));

        $listener->preRemove($product, $eventArgs);

        $this->assertTrue(true);
    }
}
