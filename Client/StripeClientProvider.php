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

    public function getClient($dbObject): StripeClient
    {
        $credentials = $this->getCredentials($dbObject);
        return new StripeClient($credentials->getApiSecretKey(), $credentials->getWebhookSigningSecret(), $this->logger);
    }

    protected function getCredentials($dbObject): StripeCredentials
    {
        /** @var StripeCredentials $credentials */
        $credentials = $this->credentialsProvider->getCredentials($dbObject);

        return $credentials;
    }
}