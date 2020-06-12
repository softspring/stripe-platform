<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Adapter\ProductAdapterInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\ProductTransformer;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Softspring\SubscriptionBundle\Model\ProductInterface;
use Stripe\Product;

class ProductAdapter implements ProductAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var ProductTransformer
     */
    protected $productTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * ProductAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param ProductTransformer      $productTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, ProductTransformer $productTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->productTransformer = $productTransformer;
        $this->logger = $logger;
    }

    /**
     * @return PlatformTransformerInterface
     */
    public function getTransformer(): ?PlatformTransformerInterface
    {
        return $this->productTransformer;
    }

    /**
     * @param ProductInterface|PlatformObjectInterface $product
     *
     * @return Product
     * @throws PlatformException
     */
    public function create(ProductInterface $product)
    {
        $data = $this->productTransformer->transform($product, 'create');

        $productStripe = $this->stripeClientProvider->getClient($product)->productCreate($data['product']);

        $this->logger && $this->logger->info(sprintf('Stripe created product %s', $productStripe->id));

        $this->productTransformer->reverseTransform($productStripe, $product);

        return $productStripe;
    }

    /**
     * @param ProductInterface|PlatformObjectInterface $product
     *
     * @return Product
     * @throws PlatformException
     */
    public function get(ProductInterface $product)
    {
        $productStripe = $this->stripeClientProvider->getClient($product)->productRetrieve([
            'id' => $product->getPlatformId(),
        ]);

        $this->productTransformer->reverseTransform($productStripe, $product);

        return $productStripe;
    }

    public function update(ProductInterface $product): void
    {
        // TODO: Implement update() method.
    }

    public function delete(ProductInterface $product): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @return Collection|Product[]
     * @throws PlatformException
     */
    public function list(): Collection
    {
        $products = $this->stripeClientProvider->getClient(null)->productList();

        return new ArrayCollection($products->getIterator()->getArrayCopy());
    }
}