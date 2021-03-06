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
use Stripe\Charge;
use Stripe\Collection;
use Stripe\Coupon;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Invoice;
use Stripe\InvoiceItem;
use Stripe\Plan;
use Stripe\Product;
use Stripe\Refund;
use Stripe\Source;
use Stripe\Stripe;
use Stripe\Subscription;
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

    public function planCreate($params = null, $options = null): Plan
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Plan::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function planRetrieve($id, $opts = null): Plan
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Plan::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return Collection|Plan[]
     * @throws PlatformException
     */
    public function planList($params = null, $opts = null): Collection
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Plan::all($params, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function productCreate($params = null, $options = null): Product
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Product::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function productRetrieve($id, $opts = null): Product
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Product::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @return Collection|Product[]
     * @throws PlatformException
     */
    public function productList($params = null, $opts = null): Collection
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Product::all($params, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function subscriptionCreate($params = null, $options = null): Subscription
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Subscription::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function subscriptionRetrieve($id, $opts = null): Subscription
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Subscription::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function subscriptionCancel(Subscription $subscription, $params = null, $options = null): Subscription
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return $subscription->cancel($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
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

    public function chargeCreate($params = null, $options = null): Charge
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Charge::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function refundCreate($params = null, $options = null): Refund
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Refund::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function chargeRetrieve($id, $opts = null): Charge
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Charge::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function refundRetrieve($id, $opts = null): Refund
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Refund::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function invoiceCreate($params = null, $options = null): Invoice
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Invoice::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function invoiceRetrieve($id, $opts = null): Invoice
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Invoice::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function invoiceItemCreate($params = null, $options = null): InvoiceItem
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return InvoiceItem::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function invoiceItemRetrieve($id, $opts = null): InvoiceItem
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return InvoiceItem::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function couponCreate($params = null, $options = null): Coupon
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Coupon::create($params, $options);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function couponRetrieve($id, $opts = null): Coupon
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Coupon::retrieve($id, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
    }

    public function eventRetrieve($id, $opts = null): Event
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Event::retrieve($id, $opts);
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
     * @param string   $customerId
     * @param null     $params
     * @param null     $opts
     *
     * @return array|\Stripe\StripeObject
     */
    public function sourceCreate($customerId, $params = null, $opts = null)
    {
        try {
            Stripe::setApiKey($this->apiSecretKey);
            return Customer::createSource($customerId, $params, $opts);
        } catch (\Exception $e) {
            throw $this->transformException($e);
        }
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

    protected function transformException(\Throwable $e): PlatformException
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