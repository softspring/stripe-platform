<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Transformer;

use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PlatformBundle\Stripe\Tests\Examples\AddressExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;
use Softspring\PlatformBundle\Stripe\Transformer\CustomerTransformer;
use PHPUnit\Framework\TestCase;

class CustomerTransformerTest extends TestCase
{
    public function testSupports()
    {
        $transformer = new CustomerTransformer();

        $this->assertFalse($transformer->supports(new \stdClass()));
        $this->assertTrue($transformer->supports($this->createMock(CustomerInterface::class)));
    }

    public function testTransform()
    {
        $transformer = new CustomerTransformer();

        $customer = new CustomerFullExample();
        $customer->setName('Test name');
        $customer->setEmail('test@example.com');
        $customer->setTaxIdCountry('ES');
        $customer->setTaxIdNumber('00000000X');
        $this->assertEquals([
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Test name',
            ],
            'tax_id' => [
                'type' => 'es_cif',
                'value' => '00000000X',
            ]
        ], $transformer->transform($customer, 'create'));

        $customer = new CustomerFullExample();
        $customer->setName('Test name');
        $customer->setEmail('test@example.com');
        $customer->setTaxIdCountry('UK');
        $customer->setTaxIdNumber('00000000X');
        $this->assertEquals([
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Test name',
            ],
        ], $transformer->transform($customer, 'create'));

        $customer = new CustomerFullExample();
        $customer->setTaxIdCountry('ES');
        $customer->setTaxIdNumber('00000000X');
        $customer->setEmail('test@example.com');
        $address = new AddressExample();
        $address->setName('Juan');
        $address->setSurname('González Pérez');
        $address->setStreetAddress('C/ Gran Vía 1');
        $address->setExtendedAddress('Piso 66, puerta Z');
        $address->setPostalCode('28000');
        $address->setLocality('Zaragoza');
        $address->setRegion('Zaragoza');
        $address->setCountryCode('ES');
        $address->setTel('9001231234');
        $customer->setBillingAddress($address);
        $this->assertEquals([
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Juan González Pérez',
                'phone' => '9001231234',
                'address' => [
                    'line1' => 'C/ Gran Vía 1',
                    'line2' => 'Piso 66, puerta Z',
                    'city' => 'Zaragoza',
                    'postal_code' => '28000',
                    'state' => 'Zaragoza',
                    'country' => 'ES',
                ],
                'description' => null,
            ],
            'tax_id' => [
                'type' => 'es_cif',
                'value' => '00000000X',
            ],
        ], $transformer->transform($customer, 'create'));
    }
}
