<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PaymentBundle\Model\DiscountInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Adapter\DiscountAdapterInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\DiscountTransformer;
use Stripe\Coupon;

class DiscountAdapter implements DiscountAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var DiscountTransformer
     */
    protected $discountTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * DiscountAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param DiscountTransformer   $discountTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, DiscountTransformer $discountTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->discountTransformer = $discountTransformer;
        $this->logger = $logger;
    }

    /**
     * @param DiscountInterface|PlatformObjectInterface $discount
     *
     * @return Coupon
     * @throws PlatformException
     */
    public function create(DiscountInterface $discount)
    {
        $data = $this->discountTransformer->transform($discount, 'create');

        if (empty($data['discount'])) {
            return null;
        }

        $discountStripe = $this->stripeClientProvider->getClient($discount)->couponCreate($data['discount']);

        $this->logger && $this->logger->info(sprintf('Stripe created discount %s', $discountStripe->id));

        $this->discountTransformer->reverseTransform($discountStripe, $discount);

        return $discountStripe;
    }

    /**
     * @param DiscountInterface|PlatformObjectInterface $discount
     *
     * @return Coupon
     * @throws PlatformException
     */
    public function get(DiscountInterface $discount)
    {
        $discountStripe = $this->stripeClientProvider->getClient($discount)->couponRetrieve($discount->getPlatformId());

        $this->discountTransformer->reverseTransform($discountStripe, $discount);

        return $discountStripe;
    }
}