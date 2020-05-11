<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Adapter;

use Softspring\PlatformBundle\Stripe\Adapter\CustomerAdapter;
use Softspring\PlatformBundle\Stripe\Client\StripeClient;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerBaseExample;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use Stripe\Customer;

class CustomerAdapterTest extends AbstractStripeAdapterTest
{
    /**
     * @var CustomerAdapter
     */
    protected $adapter;

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

        $this->adapter = new CustomerAdapter($this->stripeClientProvider, new CustomerTransformer(), null);
    }

    public function testGetExisting()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $this->stripeClient->method('customerRetrieve')->will($this->returnValue($this->createStripeCustomerObject([
            'id' => 'cus_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
        ])));

        $stripeCustomer = $this->adapter->get($customer);
        $this->assertInstanceOf(Customer::class, $stripeCustomer);
        $this->assertEquals('cus_test', $customer->getPlatformId());
        $this->assertEquals(true, $customer->isTestMode());
        $this->assertEquals(false, $customer->isPlatformConflict());
        $this->assertEquals($created->format('Y-m-d H:i:s'), $customer->getPlatformLastSync()->format('Y-m-d H:i:s'));
    }

    public function testCreate()
    {
        $customer = new CustomerBaseExample();
        $customer->setTaxIdCountry('ES');
        $customer->setTaxIdNumber('000000000X');

        $taxIdMockObject = $this->createStripeTaxIdObject([
            'id' => 'txi_test',
            'country' => 'ES',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
            'customer' => 'cus_test',
            'type' => 'eu_vat',
            'value' => '000000000X',
            'verification' => [
                'status' => 'pending',
                'verified_address' => null,
                'verified_name' => null,
            ],
        ]);

        $this->stripeClient->method('customerCreate')->will($this->returnValue($this->createStripeCustomerObject([
            'id' => 'cus_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
            'tax_ids' => $this->createStripeCollectionObject([$taxIdMockObject]),
        ])));

        $this->stripeClient->method('customerTaxIdCreate')->will($this->returnValue($taxIdMockObject));

        $this->adapter->create($customer);
        $this->assertEquals('cus_test', $customer->getPlatformId());
        $this->assertEquals(true, $customer->isTestMode());
        $this->assertEquals(false, $customer->isPlatformConflict());
        $this->assertEquals($created->format('Y-m-d H:i:s'), $customer->getPlatformLastSync()->format('Y-m-d H:i:s'));
    }


    public function testDelete()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $object = $this->createStripeCustomerObject([
            'id' => 'cus_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
        ]);

        $this->stripeClient->expects($this->once())->method('delete');

        $this->stripeClient->method('customerRetrieve')->will($this->returnValue($object));

        $this->adapter->delete($customer);
    }

    public function testUpdate()
    {
        $customer = new CustomerBaseExample();
        $customer->setPlatformId('cus_test');

        $object = $this->createStripeCustomerObject([
            'id' => 'cus_test',
            'livemode' => false,
            'created' => ($created = new \DateTime('now'))->format('U'),
        ]);

        $this->stripeClient->expects($this->once())->method('save');

        $this->stripeClient->method('customerRetrieve')->will($this->returnValue($object));

        $this->adapter->update($customer);
    }
}
