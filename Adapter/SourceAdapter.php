<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\CustomerBundle\Model\SourceInterface;
use Softspring\PlatformBundle\Adapter\SourceAdapterInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\SourceTransformer;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Source;

class SourceAdapter implements SourceAdapterInterface
{
    /**
     * @var CustomerAdapter
     */
    protected $customerAdapter;

    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var SourceTransformer
     */
    protected $sourceTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * SourceAdapter constructor.
     *
     * @param CustomerAdapter      $customerAdapter
     * @param StripeClientProvider $stripeClientProvider
     * @param SourceTransformer    $sourceTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(CustomerAdapter $customerAdapter, StripeClientProvider $stripeClientProvider, SourceTransformer $sourceTransformer, ?LoggerInterface $logger)
    {
        $this->customerAdapter = $customerAdapter;
        $this->stripeClientProvider = $stripeClientProvider;
        $this->sourceTransformer = $sourceTransformer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function create(SourceInterface $source)
    {
        if ( ! ($customer = $source->getCustomer()) instanceof CustomerInterface) {
            throw new \Exception('Missing customer in source object');
        }

        /** @var Customer $customerStripe */
        $customerStripe = $this->customerAdapter->get($customer);

        $data = $this->sourceTransformer->transform($source, 'create');

        /** @var Source $sourceStripe */
        $sourceStripe = $this->stripeClientProvider->getClient($customer)->sourceCreate($customerStripe, $data);

        $this->sourceTransformer->reverseTransform($sourceStripe, $source);

        // save default
        if ($customer->getDefaultSource() === $source) {
            if ($customerStripe->default_source !== $sourceStripe->id) {
                // set default
                $customerStripe->default_source = $sourceStripe;
            }
        }

        $this->stripeClientProvider->getClient($customer)->save($customerStripe);

        return $sourceStripe;
    }

    /**
     * @inheritDoc
     */
    public function get(SourceInterface $source)
    {
        $sourceStripe = $this->stripeClientProvider->getClient($source)->sourceRetrieve($source->getCustomer()->getPlatformId(), $source->getPlatformId());

        $this->sourceTransformer->reverseTransform($sourceStripe, $source);

        return $sourceStripe;
    }
}