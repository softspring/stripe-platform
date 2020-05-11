<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Adapter\CustomerAdapterInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use Stripe\Customer;

class CustomerAdapter implements CustomerAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var CustomerTransformer
     */
    protected $customerTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * CustomerAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param CustomerTransformer  $customerTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, CustomerTransformer $customerTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->customerTransformer = $customerTransformer;
        $this->logger = $logger;
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     *
     * @return mixed|Customer
     * @throws TransformException
     */
    public function create(CustomerInterface $customer)
    {
        // prepare data for stripe
        $data = $this->customerTransformer->transform($customer, 'create');

        $customerStripe = $this->stripeClientProvider->getClient($customer)->customerCreate($data['customer']);

        $this->logger && $this->logger->info(sprintf('Stripe created customer %s', $customerStripe->id));

        $this->customerTransformer->reverseTransform($customerStripe, $customer);
        $this->updateTaxId($customer, $customerStripe, $data);

        return $customerStripe;
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     *
     * @return mixed|Customer
     * @throws TransformException
     */
    public function update(CustomerInterface $customer)
    {
        // prepare data for stripe
        $data = $this->customerTransformer->transform($customer, 'update');

        /** @var Customer $customerStripe */
        $customerStripe = $this->get($customer);
        $customerStripe->updateAttributes($data['customer']);
        $this->stripeClientProvider->getClient($customer)->save($customerStripe);

        $this->logger && $this->logger->info(sprintf('Stripe updated customer %s', $customerStripe->id));

        $this->customerTransformer->reverseTransform($customerStripe, $customer);
        $this->updateTaxId($customer, $customerStripe, $data);

        return $customerStripe;
    }

    /**
     * @param CustomerInterface $customer
     *
     * @return void
     * @throws TransformException
     */
    public function delete(CustomerInterface $customer)
    {
        $customerStripe = $this->get($customer);
        $this->stripeClientProvider->getClient($customer)->delete($customerStripe);

        $this->logger && $this->logger->info(sprintf('Stripe deleted customer %s', $customerStripe->id));

        return;
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     *
     * @return Customer
     * @throws TransformException
     */
    public function get(CustomerInterface $customer)
    {
        $customerStripe = $this->stripeClientProvider->getClient($customer)->customerRetrieve([
            'id' => $customer->getPlatformId(),
        ]);

        $this->customerTransformer->reverseTransform($customerStripe, $customer);

        return $customerStripe;
    }

    /**
     * @param CustomerInterface $customer
     * @param Customer          $customerStripe
     * @param array             $dataForPlatform
     *
     * @throws \Exception
     */
    protected function updateTaxId(CustomerInterface $customer, Customer $customerStripe, array $dataForPlatform)
    {
        if (empty($dataForPlatform['tax_id'])) {
            return;
        }

        $action = 'create';
        foreach ($customerStripe->tax_ids->getIterator() as $taxId) {
            if ($taxId->type == $dataForPlatform['tax_id']['type']) {
                if ($taxId->value == $dataForPlatform['tax_id']['value']) {
                    $action = 'none';
                } else {
                    $action = 'update';
                    $deleteTaxId = $taxId->id;
                }
            }
        }

        if ($action == 'create') {
            $this->stripeClientProvider->getClient($customer)->customerTaxIdCreate($customerStripe->id, $dataForPlatform['tax_id']);
        } elseif ($action == 'update') {
            $this->stripeClientProvider->getClient($customer)->customerTaxIdDelete($customerStripe->id, $deleteTaxId);
            $this->stripeClientProvider->getClient($customer)->customerTaxIdCreate($customerStripe->id, $dataForPlatform['tax_id']);
        }
    }
}