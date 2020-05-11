<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Client;

use Softspring\PlatformBundle\Stripe\Client\StripeCredentials;
use PHPUnit\Framework\TestCase;

class StripeCredentialsTest extends TestCase
{
    public function testCredentials()
    {
        $credentials = new StripeCredentials('secret', 'public', 'signature');

        $this->assertEquals('secret', $credentials->getApiSecretKey());
        $this->assertEquals('public', $credentials->getApiPublicKey());
        $this->assertEquals('signature', $credentials->getWebhookSigningSecret());
    }
}
