<?php

namespace Softspring\PlatformBundle\Stripe\Client;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Provider\CredentialsProviderInterface;

class StripeClientProvider
{
    /**
     * @var CredentialsProviderInterface
     */
    protected $credentialsProvider;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * StripeClientProvider constructor.
     *
     * @param CredentialsProviderInterface $credentialsProvider
     * @param LoggerInterface|null         $logger
     */
    public function __construct(CredentialsProviderInterface $credentialsProvider, ?LoggerInterface $logger)
    {
        $this->credentialsProvider = $credentialsProvider;
        $this->logger = $logger;
    }

    public function getClient($dbObject = null): StripeClient
    {
        $credentials = $this->getCredentials($dbObject);
        return new StripeClient($credentials->getApiSecretKey(), $credentials->getWebhookSigningSecret(), $this->logger);
    }

    protected function getCredentials($dbObject = null): StripeCredentials
    {
        /** @var StripeCredentials $credentials */
        $credentials = $dbObject ? $this->credentialsProvider->getCredentials($dbObject) : $this->credentialsProvider->getPlatformCredentials('stripe');

        return $credentials;
    }
}