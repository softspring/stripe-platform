<?php

namespace Softspring\PlatformBundle\Stripe\Tests\Transformer;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Softspring\CustomerBundle\Manager\CustomerManager;
use Softspring\CustomerBundle\Manager\CustomerManagerInterface;
use Softspring\CustomerBundle\Manager\SourceManager;
use Softspring\CustomerBundle\Manager\SourceManagerInterface;
use Softspring\PaymentBundle\Manager\InvoiceManager;
use Softspring\PaymentBundle\Manager\InvoiceManagerInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Tests\Examples\CustomerFullExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\InvoiceExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\PaymentExample;
use Softspring\PlatformBundle\Stripe\Tests\Examples\SourceExample;
use Softspring\PlatformBundle\Stripe\Transformer\PaymentTransformer;
use PHPUnit\Framework\TestCase;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Source;

class PaymentTransformerTest extends TestCase
{
    /**
     * @var MockObject|CustomerManagerInterface
     */
    protected $customerManager;

    /**
     * @var MockObject|SourceManagerInterface
     */
    protected $sourceManager;

    /**
     * @var MockObject|InvoiceManagerInterface
     */
    protected $invoiceManager;

    protected function setUp(): void
    {
        $this->customerManager = $this->createMock(CustomerManager::class);
        $this->sourceManager = $this->createMock(SourceManager::class);
        $this->invoiceManager = $this->createMock(InvoiceManager::class);
    }

    public function testSupports()
    {
        $transformer = new PaymentTransformer($this->customerManager, $this->sourceManager, $this->invoiceManager);

        $this->assertFalse($transformer->supports(new \stdClass()));
        $this->assertTrue($transformer->supports($this->createMock(PaymentInterface::class)));
    }

    public function testChargeTransform()
    {
        $transformer = new PaymentTransformer($this->customerManager, $this->sourceManager, $this->invoiceManager);

        $customer = new CustomerFullExample();
        $customer->setPlatformId('cus_000000000000');

        $source = new SourceExample();
        $source->setPlatformId('src_00000000000');

        $payment = new PaymentExample();
        $payment->setConcept('Charge concept');
        $payment->setCustomer($customer);
        $payment->setSource($source);
        $payment->setStatus(PaymentInterface::STATUS_PENDING);
        $payment->setType(PaymentInterface::TYPE_CHARGE);
        $payment->setDate($date = new \DateTime('now'));
        $payment->setAmount(13.56);
        $payment->setCurrency('EUR');
        $payment->setConcept('Charge concept');
        $payment->setRefundPayment(null);

        $this->assertEquals([
            'charge' => [
                'customer' => 'cus_000000000000',
                'source' => 'src_00000000000',
                'amount' => 1356,
                'currency' => 'eur',
                'description' => 'Charge concept',
            ],
        ], $transformer->transform($payment, 'create'));
    }

    public function testRefundTransform()
    {
        $transformer = new PaymentTransformer($this->customerManager, $this->sourceManager, $this->invoiceManager);

        $customer = new CustomerFullExample();
        $customer->setPlatformId('cus_000000000000');

        $source = new SourceExample();
        $source->setPlatformId('src_00000000000');

        $charge = new PaymentExample();
        $charge->setPlatformId('ch_000000000000');

        $payment = new PaymentExample();
        $payment->setConcept('Refund concept');
        $payment->setCustomer($customer);
        $payment->setSource($source);
        $payment->setStatus(PaymentInterface::STATUS_PENDING);
        $payment->setType(PaymentInterface::TYPE_REFUND);
        $payment->setDate($date = new \DateTime('now'));
        $payment->setAmount(13.56);
        $payment->setCurrency('EUR');
        $payment->setConcept('Refund concept');
        $payment->setRefundPayment($charge);

        $this->assertEquals([
            'refund' => [
                'amount' => 1356,
                'charge' => 'ch_000000000000',
            ],
        ], $transformer->transform($payment, 'create'));
    }

    public function testBadTransform()
    {
        $transformer = new PaymentTransformer($this->customerManager, $this->sourceManager, $this->invoiceManager);

        $payment = new PaymentExample();
        $payment->setType(1111111111);

        $this->expectException(PlatformException::class);

        $transformer->transform($payment, 'create');
    }

    public function testReverseChargeTransform()
    {
        $customer = new CustomerFullExample();
        $customer->setPlatformId('cus_00000000000000');
        $customerRepository = $this->createMock(EntityRepository::class);
        $customerRepository->method('findOneBy')->willReturn($customer);
        $this->customerManager->method('getRepository')->willReturn($customerRepository);

        $source = new SourceExample();
        $source->setPlatformId('cus_00000000000000');
        $sourceRepository = $this->createMock(EntityRepository::class);
        $sourceRepository->method('findOneBy')->willReturn($source);
        $this->sourceManager->method('getRepository')->willReturn($sourceRepository);

        $invoice = new InvoiceExample();
        $invoice->setPlatformId('in_00000000000000');
        $invoiceRepository = $this->createMock(EntityRepository::class);
        $invoiceRepository->method('findOneBy')->willReturn($invoice);
        $this->invoiceManager->method('getRepository')->willReturn($invoiceRepository);

        $transformer = new PaymentTransformer($this->customerManager, $this->sourceManager, $this->invoiceManager);

        $stripeCharge = new Charge('ch_xxxxxxx');
        $stripeCharge->status = 'succeeded';
        $stripeCharge->created = $created = time();
        $stripeCharge->description = 'Charge description';
        $stripeCharge->amount = 1599;
        $stripeCharge->currency = 'usd';
        $stripeCharge->customer = 'cus_00000000000000';
        $stripeCharge->source = new Source('src_00000000000000');
        $stripeCharge->invoice = 'in_00000000000000';

        $payment = new PaymentExample();

        /** @var PaymentExample $payment */
        $payment = $transformer->reverseTransform($stripeCharge, $payment);

        $this->assertEquals('ch_xxxxxxx', $payment->getPlatformId());

        $this->assertEquals($customer, $payment->getCustomer());
        $this->assertEquals($source, $payment->getSource());
        $this->assertEquals($invoice, $payment->getInvoice());
        $this->assertEquals(PaymentInterface::STATUS_DONE, $payment->getStatus());
        $this->assertEquals('done', $payment->getStatusString());
        $this->assertEquals(PaymentInterface::TYPE_CHARGE, $payment->getType());
        $this->assertEquals($created, $payment->getDate()->format('U'));
        $this->assertEquals(15.99, $payment->getAmount());
        $this->assertEquals('USD', $payment->getCurrency());
        $this->assertEquals('Charge description', $payment->getConcept());
        $this->assertEquals(null, $payment->getRefundPayment());
    }

    public function testReverseRefundTransform()
    {
        $transformer = new PaymentTransformer($this->customerManager, $this->sourceManager, $this->invoiceManager);

        $stripeRefund = new Refund('ref_xxxxxxx');
        $stripeRefund->status = 'succeeded';
        $stripeRefund->created = $created = time();

        $payment = new PaymentExample();

        /** @var PaymentExample $payment */
        $payment = $transformer->reverseTransform($stripeRefund, $payment);

        $this->assertEquals('ref_xxxxxxx', $payment->getPlatformId());

        $this->assertEquals(PaymentInterface::STATUS_DONE, $payment->getStatus());
        $this->assertEquals('done', $payment->getStatusString());
        $this->assertEquals(PaymentInterface::TYPE_REFUND, $payment->getType());
        $this->assertEquals($created, $payment->getDate()->format('U'));
        $this->assertEquals(null, $payment->getRefundPayment());
    }
}
