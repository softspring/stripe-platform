<?php

namespace Softspring\PlatformBundle\Stripe\Client;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Exception\MaxSubscriptionsReachException;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Exception\PaymentException;
use Softspring\PlatformBundle\Exception\PlatformException;
use Stripe\ApiOperations\Delete;
use Stripe\ApiOperations\Update;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Source;
use Stripe\Stripe;
use Stripe\TaxId;

class StripeClient
{
    /**
     * @var string
     */
    protected $apiSecretKey;

    /**
     * @var string|null
     */
    protected $webhookSigningSecret;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * StripeClient constructor.
     *
     * @param string               $apiSecretKey
     * @param string|null          $webhookSigningSecret
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $apiSecretKey, ?string $webhookSigningSecret, ?LoggerInterface $logger)
    {
        $this->apiSecretKey = $apiSecretKey;
        $this->webhookSigningSecret = $webhookSigningSecret;
        $this->logger = $logger;
    }

    public function customerCreate($params = null, $options = null): Customer
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Customer::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function customerRetrieve($id, $opts = null): Customer
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Customer::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function customerTaxIdCreate($id, $params = null, $opts = null): TaxId
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Customer::createTaxId($id, $params, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function customerTaxIdDelete($id, $taxIdId, $params = null, $opts = null): TaxId
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Customer::deleteTaxId($id, $taxIdId, $params, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    /**
     * @param      $customerId
     * @param      $id
     * @param null $params
     * @param null $opts
     *
     * @return \Stripe\AlipayAccount|\Stripe\BankAccount|\Stripe\BitcoinReceiver|Card|Source
     * @throws \Exception
     */
    public function sourceRetrieve($customerId, $id, $params = null, $opts = null)
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Customer::retrieveSource($customerId, $id, $params, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    /**
     * @param Customer $customer
     * @param null     $params
     * @param null     $opts
     *
     * @return array|\Stripe\StripeObject
     */
    public function sourceCreate(Customer $customer, $params = null, $opts = null)
    {
        return $customer->sources->create($params, $opts);
    }

    /**
     * @param Update $stripeObject
     * @param null   $opts
     *
     * @return mixed
     * @throws \Exception
     */
    public function save($stripeObject, $opts = null)
    {
        try {
            return $stripeObject->save($opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    /**
     * @param Delete $stripeObject
     * @param null   $opts
     *
     * @return mixed
     * @throws \Exception
     */
    public function delete($stripeObject, $opts = null)
    {
        try {
            return $stripeObject->delete($opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    /**
     * @param \Throwable $e
     *
     * @return \Exception
     */
    protected function transformException(\Throwable $e): \Exception
    {
        if ($e instanceof PlatformException) {
            return $e;
        }

        if ($e instanceof ApiConnectionException) {
            $this->logger && $this->logger->error(sprintf('Can not connect to Stripe: %s', $e->getMessage()));
            return new PlatformException('stripe', 'api_connection_error', 'Can not connecto to stripe', 0, $e);
        }

        if ($e instanceof CardException) {
            switch ($e->getStripeCode()) {
                case 'card_declined':
                    return new PaymentException('stripe', $e->getDeclineCode(), $e->getMessage(), 0, $e);
                    break;
            }

            $this->logger && $this->logger->error(sprintf('Stripe unknown card error: %s', $e->getMessage()));
            return new PaymentException('stripe', 'unknown_card_error', 'Stripe unknown card error', 0, $e);
        }

        if ($e instanceof InvalidRequestException) {
            switch ($e->getStripeCode()) {
                case 'customer_max_subscriptions':
                    $this->logger && $this->logger->warning(sprintf('Stripe customer has reached max subscriptions limit'));
                    return new MaxSubscriptionsReachException('stripe', 'customer_max_subscriptions', $e->getMessage(), 0, $e);

                case 'resource_missing':
                    $this->logger && $this->logger->error(sprintf('Stripe resource %s not found', $e->getRequestId()));
                    return new NotFoundInPlatform('stripe', 'not_found',$e->getMessage(), 0, $e);

                default:
                    $this->logger && $this->logger->error(sprintf('Stripe invalid request: %s', $e->getMessage()));
                    return new PlatformException('stripe', 'invalid_request','Invalid stripe request', 0, $e);
            }
        }

        $this->logger && $this->logger->error(sprintf('Stripe unknown exception: %s', $e->getMessage()));
        return new PlatformException('stripe', 'unknown_error', 'Unknown stripe exception', 0, $e);
    }
}