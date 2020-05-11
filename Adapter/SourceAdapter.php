<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\CustomerBundle\Model\SourceInterface;
use Softspring\PlatformBundle\Adapter\CustomerAdapterInterface;
use Softspring\PlatformBundle\Adapter\SourceAdapterInterface;
use Softspring\PlatformBundle\PlatformInterface;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Source;

class SourceAdapter implements SourceAdapterInterface
{
    /**
     * @var CustomerAdapterInterface
     */
    protected $customerAdapter;

    /**
     * SourceAdapter constructor.
     *
     * @param CustomerAdapterInterface $customerAdapter
     * @param string                   $apiSecretKey
     * @param string|null              $webhookSigningSecret
     * @param LoggerInterface|null     $logger
     */
    public function __construct(CustomerAdapterInterface $customerAdapter, string $apiSecretKey, ?string $webhookSigningSecret, ?LoggerInterface $logger)
    {
        parent::__construct($apiSecretKey, $webhookSigningSecret, $logger);
        $this->customerAdapter = $customerAdapter;
    }

    /**
     * @param SourceInterface $source
     * @param Source|Card     $sourceStripe
     */
    public function syncSource(SourceInterface $source, $sourceStripe)
    {
        // save platform data
        $source->setPlatform(PlatformInterface::PLATFORM_STRIPE);
        $source->setPlatformId($sourceStripe->id);
        $source->setTestMode(!$sourceStripe->livemode);
        // $source->setPlatformLastSync($sourceStripe->created);
        $source->setPlatformConflict(false);
        $source->setPlatformData($sourceStripe->toArray());
    }

    /**
     * @inheritDoc
     */
    public function create(SourceInterface $source)
    {
        try {
            if ( ! ($customer = $source->getCustomer()) instanceof CustomerInterface) {
                throw new \Exception('Missing customer in source object');
            }

            $this->initStripe();

            /** @var Customer $customerStripe */
            $customerStripe = $this->customerAdapter->get($customer);

            /** @var Source $sourceStripe */
            $sourceStripe = $customerStripe->sources->create(['source' => $source->getPlatformToken()]);

            // save default
            if ($customer->getDefaultSource() === $source) {
                if ($customerStripe->default_source !== $sourceStripe->id) {
                    // set default
                    $customerStripe->default_source = $sourceStripe;
                }
            }

            $customerStripe->save();

            $this->syncSource($source, $sourceStripe);

            return $sourceStripe;
        } catch (\Exception $e) {
            return $this->attachStripeExceptions($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function get(SourceInterface $source)
    {
        try {
            $this->initStripe();

            $sourceStripe = $this->stripeClientRetrieve($source->getCustomer()->getPlatformId(), $source->getPlatformId());

            $this->syncSource($source, $sourceStripe);

            return $sourceStripe;
        } catch (\Exception $e) {
            return $this->attachStripeExceptions($e);
        }
    }

    /**
     * @param      $id
     * @param      $sourceId
     * @param null $params
     * @param null $opts
     *
     * @return \Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|Card|Source
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function stripeClientRetrieve($id, $sourceId, $params = null, $opts = null)
    {
        return Customer::retrieveSource($id, $sourceId, $params, $opts);
    }
}