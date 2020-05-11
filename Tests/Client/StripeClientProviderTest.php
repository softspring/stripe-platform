<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Client;

use Softspring\PlatformBundle\Provider\CredentialsProviderInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClient;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use PHPUnit\Framework\TestCase;
use Softspring\PlatformBundle\Stripe\Client\StripeCredentials;

class StripeClientProviderTest extends TestCase
{
    public function testGetClient()
    {
        $credentialsProvider = $this->createMock(CredentialsProviderInterface::class);
        $credentialsProvider->expects($this->once())->method('getCredentials')->willReturn(new StripeCredentials('', '', ''));

        $provider = new StripeClientProvider($credentialsProvider, null);

        $object = new \stdClass();

        $client = $provider->getClient($object);

        $this->assertInstanceOf(StripeClient::class, $client);
    }
}
