<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PlatformBundle\Adapter\PaymentAdapterInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Stripe\Client\StripeClientProvider;
use Softspring\PlatformBundle\Stripe\Transformer\PaymentTransformer;
use Stripe\Charge;
use Stripe\Refund;

class PaymentAdapter implements PaymentAdapterInterface
{
    /**
     * @var StripeClientProvider
     */
    protected $stripeClientProvider;

    /**
     * @var PaymentTransformer
     */
    protected $paymentTransformer;

    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * PaymentAdapter constructor.
     *
     * @param StripeClientProvider $stripeClientProvider
     * @param PaymentTransformer   $paymentTransformer
     * @param LoggerInterface|null $logger
     */
    public function __construct(StripeClientProvider $stripeClientProvider, PaymentTransformer $paymentTransformer, ?LoggerInterface $logger)
    {
        $this->stripeClientProvider = $stripeClientProvider;
        $this->paymentTransformer = $paymentTransformer;
        $this->logger = $logger;
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     *
     * @return Charge|Refund|void
     * @throws PlatformException
     */
    public function create(PaymentInterface $payment)
    {
        $data = $this->paymentTransformer->transform($payment, 'create');

        switch ($payment->getType()) {
            case PaymentInterface::TYPE_CHARGE:
                $paymentStripe = $this->stripeClientProvider->getClient($payment)->chargeCreate($data['charge']);
                break;

            case PaymentInterface::TYPE_REFUND:
                $paymentStripe = $this->stripeClientProvider->getClient($payment)->refundCreate($data['refund']);
                break;

            default:
                throw new PlatformException('stripe', 'Bad payment type');
        }

        $this->logger && $this->logger->info(sprintf('Stripe created payment %s', $paymentStripe->id));

        $this->paymentTransformer->reverseTransform($paymentStripe, $payment);

        return $paymentStripe;
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     *
     * @return Charge|Refund|void
     * @throws PlatformException
     */
    public function get(PaymentInterface $payment)
    {
        switch ($payment->getType()) {
            case PaymentInterface::TYPE_CHARGE:
                $paymentStripe = $this->stripeClientProvider->getClient($payment)->chargeRetrieve($payment->getPlatformId());
                break;

            case PaymentInterface::TYPE_REFUND:
                $paymentStripe = $this->stripeClientProvider->getClient($payment)->refundRetrieve($payment->getPlatformId());
                break;

            default:
                throw new PlatformException('stripe', 'Bad payment type');
        }

        $this->paymentTransformer->reverseTransform($paymentStripe, $payment);

        return $paymentStripe;
    }
}