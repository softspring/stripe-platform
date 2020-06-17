<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Transformer;

use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Stripe\Tests\Examples\ProductExample;
use Softspring\PlatformBundle\Stripe\Transformer\ProductTransformer;
use PHPUnit\Framework\TestCase;
use Softspring\SubscriptionBundle\Manager\ProductManager;
use Softspring\SubscriptionBundle\Manager\ProductManagerInterface;
use Softspring\SubscriptionBundle\Model\ProductInterface;
use Stripe\Product;

class ProductTransformerTest extends TestCase
{
    /**
     * @var MockObject|ProductManagerInterface
     */
    protected $productManager;

    protected function setUp(): void
    {
        $this->productManager = $this->createMock(ProductManager::class);
    }

    public function testSupports()
    {
        $transformer = new ProductTransformer($this->productManager);

        $this->assertFalse($transformer->supports(new \stdClass()));
        $this->assertTrue($transformer->supports($this->createMock(ProductInterface::class)));
    }

    public function testTransform()
    {
        $transformer = new ProductTransformer($this->productManager);

        $product = new ProductExample();
        $product->setPlatformId('product_example');
        $product->setName('Product example');

        $this->assertEquals([
            'product' => [
                'name' => 'Product example',
                'id' => 'product_example',
            ],
        ], $transformer->transform($product, 'create'));
    }

    public function testReverseTransform()
    {
        $this->productManager->method('createEntity')->willReturn(new ProductExample());

        $transformer = new ProductTransformer($this->productManager);

        $stripeProduct = new Product('product_xxxxxxx');
        $stripeProduct->name = 'Test product';

        $product = $transformer->reverseTransform($stripeProduct, null);

        $this->assertEquals('product_xxxxxxx', $product->getPlatformId());
        $this->assertEquals('Test product', $product->getName());
    }
}
