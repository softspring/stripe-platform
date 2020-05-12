<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;
use Softspring\PlatformBundle\Stripe\Adapter\SourceAdapter;
use Softspring\PlatformBundle\Stripe\Client\StripeClient;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerBaseExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\SourceExample;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use Softspring\PlatformBundle\Stripe\Transformer\SourceTransformer;
use Stripe\Collection;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Source;

class SourceAdapterTest extends AbstractStripeAdapterTest
{
    /**
     * @var CustomerAdapter
     */
    protected $customerAdapter;

    /**
     * @var SourceAdapter
     */
    protected $sourcesAdapter;

    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var StripeClient
     */
    protected $stripeClient;

    protected function setUp(): void
    {
        $this->stripeClient = $this->createMock(StripeClient::class);

        $this->stripeClientProvider = $this->createMock(StripeClientProvider::class);
        $this->stripeClientProvider->method('getClient')->willReturn($this->stripeClient);

        $this->customerAdapter = new CustomerAdapter($this->stripeClientProvider, new CustomerTransformer(), null);
        $this->sourcesAdapter = new SourceAdapter($this->customerAdapter, $this->stripeClientProvider, new SourceTransformer(), null);
    }

    public function testGetExisting()
    {
        $source = new SourceExample();
        $source->setPlatformId('src_test');
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');
        $customer->setTestMode(true);
        $source->setCustomer($customer);

        $this->stripeClient->method('sourceRetrieve')->will($this->returnValue($this->createStripeObject(Source::class, [
            'id' => 'src_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
        ])));

        $this->sourcesAdapter->get($source);
        $this->assertEquals('src_test', $source->getPlatformId());
        $this->assertEquals(true, $source->isTestMode());
        $this->assertEquals(false, $source->isPlatformConflict());
    }

    public function testCreate()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');
        $source = new SourceExample();
        $customer->addSource($source);
        $customer->setDefaultSource($source);
        $source->setPlatformToken('token');

        $this->stripeClient->method('customerRetrieve')->will($this->returnValue($this->createStripeCustomerObject([
            'id' => 'cus_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'default_source' => null,
            'sources' => $this->createStripeObject(Collection::class, [], [
            ])
        ])));

        $this->stripeClient->method('sourceCreate')->will($this->returnValue($this->createStripeObject(Source::class, [
            'id' => 'src_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
        ])));

        $this->sourcesAdapter->create($source);

        $this->assertEquals('src_test', $source->getPlatformId());
        $this->assertEquals(true, $source->isTestMode());
        $this->assertEquals(false, $source->isPlatformConflict());
    }

    public function testCreateWithInvalidCustomer()
    {
        $this->expectException(\Exception::class);

        $source = new SourceExample();

        $this->sourcesAdapter->create($source);
    }
}
