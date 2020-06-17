<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Event;

use PHPUnit\Framework\MockObject\MockObject;
use Softspring\PlatformBundle\Provider\CredentialsProviderInterface;
use Softspring\PlatformBundle\Provider\StaticCredentialsProvider;
use Softspring\PlatformBundle\Stripe\Client\StripeCredentials;
use Softspring\PlatformBundle\Stripe\Event\StripeWebhookEvent;
use Softspring\PlatformBundle\Stripe\Event\WebhookEventFactory;
use PHPUnit\Framework\TestCase;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Request;

class WebhookEventFactoryTest extends TestCase
{
    /**
     * @var CredentialsProviderInterface|MockObject
     */
    protected $credentialsProvider;

    protected function setUp(): void
    {
        $this->credentialsProvider = $this->createMock(StaticCredentialsProvider::class);
    }

    public function testSupport()
    {
        $factory = new WebhookEventFactory($this->credentialsProvider, null);

        $this->assertTrue($factory->support(new Request()));
    }

    public function testCreate()
    {
        $this->credentialsProvider->method('getCredentialsFromWebhook')->willReturn(new StripeCredentials('sk', 'pk', 'wh'));

        $factory = new WebhookEventFactory($this->credentialsProvider, null);

        $contentString = '{"type":"example"}';
        $timestamp = time();
        $request = new Request([], [], [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.hash_hmac('sha256', "$timestamp.$contentString", 'wh'),
        ], $contentString);

        $event = $factory->create($request);

        $this->assertInstanceOf(StripeWebhookEvent::class, $event);
        $this->assertEquals('stripe', $event->getPlatform());
        $this->assertEquals('example', $event->getName());
        $this->assertInstanceOf(Event::class, $event->getData());
    }

    public function testCreateBadJson()
    {
        $this->credentialsProvider->method('getCredentialsFromWebhook')->willReturn(new StripeCredentials('sk', 'pk', 'wh'));

        $factory = new WebhookEventFactory($this->credentialsProvider, null);

        $contentString = '{a}';
        $timestamp = time();
        $request = new Request([], [], [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.hash_hmac('sha256', "$timestamp.$contentString", 'wh'),
        ], $contentString);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Bad JSON body from Stripe!');

        $event = $factory->create($request);
    }

    public function testCreateBadSignature()
    {
        $this->credentialsProvider->method('getCredentialsFromWebhook')->willReturn(new StripeCredentials('sk', 'pk', 'wh'));

        $factory = new WebhookEventFactory($this->credentialsProvider, null);

        $contentString = '{}';
        $timestamp = time()-10000000;
        $request = new Request([], [], [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.hash_hmac('sha256', "$timestamp.$contentString", 'wh'),
        ], $contentString);

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Timestamp outside the tolerance zone');

        $event = $factory->create($request);
    }
}
