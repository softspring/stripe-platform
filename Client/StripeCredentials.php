<?php

namespace Softspring\PlatformBundle\Stripe\Client;

use Softspring\PlatformBundle\Provider\CredentialsInterface;

class StripeCredentials implements CredentialsInterface
{
    /**
     * @var string
     */
    protected $apiSecretKey;

    /**
     * @var string
     */
    protected $apiPublicKey;

    /**
     * @var string|null
     */
    protected $webhookSigningSecret;

    /**
     * StripeCredentials constructor.
     *
     * @param string      $apiSecretKey
     * @param string      $apiPublicKey
     * @param string|null $webhookSigningSecret
     */
    public function __construct(string $apiSecretKey, string $apiPublicKey, ?string $webhookSigningSecret)
    {
        $this->apiSecretKey = $apiSecretKey;
        $this->apiPublicKey = $apiPublicKey;
        $this->webhookSigningSecret = $webhookSigningSecret;
    }

    /**
     * @return string
     */
    public function getApiSecretKey(): string
    {
        return $this->apiSecretKey;
    }

    /**
     * @return string
     */
    public function getApiPublicKey(): string
    {
        return $this->apiPublicKey;
    }

    /**
     * @return string|null
     */
    public function getWebhookSigningSecret(): ?string
    {
        return $this->webhookSigningSecret;
    }
}