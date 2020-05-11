<?php

namespace Softspring\PlatformBundle\Stripe\Adapter;

use Psr\Log\LoggerInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\PlatformInterface;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PlatformBundle\Adapter\PaymentAdapterInterface;
use Softspring\PlatformBundle\Stripe\Transformer\PaymentTransformer;
use Stripe\Charge;
use Stripe\Refund;

class PaymentAdapter implements PaymentAdapterInterface
{
    /**
     * @var PaymentTransformer
     */
    protected $paymentTransformer;

    /**
     * @param PaymentInterface $payment
     *
     * @return Charge|Refund|void
     * @throws PlatformException
     */
    public function create(PaymentInterface $payment)
    {
        try {
            $this->initStripe();

            // prepare data for stripe
            $data = $this->paymentTransformer->transform($payment, 'create');

            switch ($payment->getType()) {
                case PaymentInterface::TYPE_CHARGE:
                    $paymentStripe = $this->stripeClientCreateCharge($data['charge']);
                    break;

                case PaymentInterface::TYPE_REFUND:
                    $paymentStripe = $this->stripeClientCreateRefund($data['refund']);
                    break;

                default:
                    throw new PlatformException(PlatformInterface::PLATFORM_STRIPE, 'Bad payment type');
            }

            $this->logger && $this->logger->info(sprintf('Stripe created payment %s', $paymentStripe->id));

            $this->paymentTransformer->reverseTransform($paymentStripe, $payment);

            return $paymentStripe;
        } catch (\Exception $e) {
            return $this->attachStripeExceptions($e);
        }
    }

    /**
     * @param PaymentInterface $payment
     *
     * @return Charge|Refund|void
     * @throws PlatformException
     */
    public function get(PaymentInterface $payment)
    {
        try {
            $this->initStripe();

            switch ($payment->getType()) {
                case PaymentInterface::TYPE_CHARGE:
                    $paymentStripe = $this->stripeClientRetrieveCharge($payment->getPlatformId());
                    break;

                case PaymentInterface::TYPE_REFUND:
                    $paymentStripe = $this->stripeClientRetrieveRefund($payment->getPlatformId());
                    break;

                default:
                    throw new PlatformException(PlatformInterface::PLATFORM_STRIPE, 'Bad payment type');
            }

            $this->paymentTransformer->reverseTransform($paymentStripe, $payment);

            return $paymentStripe;
        } catch (\Exception $e) {
            return $this->attachStripeExceptions($e);
        }
    }

    protected function stripeClientCreateCharge($params = null, $options = null): Charge
    {
        return Charge::create($params, $options);
    }

    protected function stripeClientCreateRefund($params = null, $options = null): Refund
    {
        return Refund::create($params, $options);
    }

    protected function stripeClientRetrieveCharge($id, $opts = null): Charge
    {
        return Charge::retrieve($id, $opts);
    }

    protected function stripeClientRetrieveRefund($id, $opts = null): Refund
    {
        return Refund::retrieve($id, $opts);
    }
}