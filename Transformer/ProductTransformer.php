<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Softspring\SubscriptionBundle\Manager\ProductManagerInterface;
use Softspring\SubscriptionBundle\Model\ProductInterface;
use Stripe\Product;

class ProductTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    /**
     * @var ProductManagerInterface|null
     */
    protected $productManager;

    /**
     * ProductTransformer constructor.
     *
     * @param ProductManagerInterface|null $productManager
     */
    public function __construct(?ProductManagerInterface $productManager)
    {
        $this->productManager = $productManager;
    }

    public function supports($product): bool
    {
        return $this->productManager && $product instanceof ProductInterface;
    }

    /**
     * @param ProductInterface|PlatformObjectInterface $product
     * @param string                                $action
     *
     * @return array
     * @throws TransformException
     */
    public function transform($product, string $action = ''): array
    {
        $this->checkSupports($product);

        $data = [
            'product' => [
            ],
        ];

        if ($action === 'create') {
            if ($product->getPlatformId()) {
                $data['product']['id'] = $product->getPlatformId();
            }

            $data['product']['name'] = $product->getName();
        }

        return $data;
    }

    /**
     * @param Product                                       $stripeProduct
     * @param ProductInterface|PlatformObjectInterface|null $product
     * @param string                                     $action
     *
     * @return ProductInterface
     * @throws TransformException
     */
    public function reverseTransform($stripeProduct, $product = null, string $action = ''): ProductInterface
    {
        if (null === $product) {
            $product = $this->productManager->createEntity();
        }

        $this->checkSupports($product);
        $this->reverseTransformPlatformObject($product, $stripeProduct);

        $product->setName($stripeProduct->name);

        return $product;
    }
}