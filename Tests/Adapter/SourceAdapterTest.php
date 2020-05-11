<?php

namespace Softspring\PlatformBundle\Tests\Manager\Adapter\Stripe;

use Softspring\PlatformBundle\Platform\Adapter\Stripe\CustomerAdapter;
use Softspring\PlatformBundle\Platform\Adapter\Stripe\SourceAdapter;
use Softspring\PlatformBundle\Exception\NotFoundInPlatform;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Tests\Model\Examples\CustomerBaseExample;
use Softspring\PlatformBundle\Tests\Model\Examples\SourceExample;
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

    protected function setUp(): void
    {
        $this->customerAdapter = $this->getMockBuilder(CustomerAdapter::class)
            ->setConstructorArgs(['sk_test_xxx', null, null])
            ->onlyMethods(['initStripe', 'stripeClientCreate', 'stripeClientRetrieve', 'stripeClientTaxIdCreate', 'stripeClientTaxIdDelete'])
            ->getMock();

        $this->sourcesAdapter = $this->getMockBuilder(SourceAdapter::class)
            ->setConstructorArgs([$this->customerAdapter, 'sk_test_xxx', null, null])
            ->onlyMethods(['initStripe', 'stripeClientRetrieve'])
            ->getMock();
    }


    public function testGetExisting()
    {
        $source = new SourceExample();
        $source->setPlatformId('src_test');
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');
        $source->setCustomer($customer);

        $this->sourcesAdapter->method('stripeClientRetrieve')->will($this->returnValue($this->createStripeObject(Source::class, [
            'id' => 'src_test',
            'livemode' => false,
        ])));

        $this->sourcesAdapter->get($source);
        $this->assertEquals('src_test', $source->getPlatformId());
        $this->assertEquals(true, $source->isTestMode());
        $this->assertEquals(false, $source->isPlatformConflict());
    }

    public function testGetMissing()
    {
        $this->expectException(NotFoundInPlatform::class);

        $source = new SourceExample();
        $source->setPlatformId('src_test_not_existing');
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');
        $source->setCustomer($customer);

        $e = new InvalidRequestException();
        $e->setStripeCode('resource_missing');
        $this->sourcesAdapter->method('stripeClientRetrieve')->will($this->throwException($e));

        $this->sourcesAdapter->get($source);
    }


    public function testCreate()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');
        $source = new SourceExample();
        $customer->addSource($source);
        $customer->setDefaultSource($source);

        $this->customerAdapter->method('stripeClientRetrieve')->will($this->returnValue($this->createStripeCustomerObject([
            'id' => 'cus_test',
            'livemode' => false,
            'created' => (new \DateTime('now'))->format('U'),
            'default_source' => null,
            'sources' => $this->createStripeObject(Collection::class, [], [
                'create' => function ($a) {
                    return $this->createStripeObject(Source::class, [
                        'id' => 'src_test',
                        'livemode' => false,
                        'created' => (new \DateTime('now'))->format('U'),
                    ]);
                }
            ])
        ])));

        $this->sourcesAdapter->create($source);

        $this->assertEquals('src_test', $source->getPlatformId());
        $this->assertEquals(true, $source->isTestMode());
        $this->assertEquals(false, $source->isPlatformConflict());
    }

    public function testCreateWithError()
    {
        $this->expectException(PlatformException::class);

        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');
        $source = new SourceExample();
        $customer->addSource($source);
        $customer->setDefaultSource($source);

        $e = new ApiConnectionException();
        $this->customerAdapter->method('stripeClientRetrieve')->will($this->throwException($e));

        $this->sourcesAdapter->create($source);
    }

    public function testCreateWithInvalidCustomer()
    {
        $this->expectException(\Exception::class);

        $source = new SourceExample();

        $this->sourcesAdapter->create($source);
    }
}
