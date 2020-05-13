<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\CustomerBundle\Model\SourceInterface;
use Softspring\PaymentBundle\Model\PaymentInterface;
use Softspring\PlatformBundle\Exception\PlatformException;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformByObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Charge;
use Stripe\Refund;

class PaymentTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    const MAPPING_STATUSES = [
        'pending' => PaymentInterface::STATUS_PENDING,
        'succeeded' => PaymentInterface::STATUS_DONE,
        'failed' => PaymentInterface::STATUS_FAILED,
    ];

    public function supports($payment): bool
    {
        return $payment instanceof PaymentInterface;
    }

    /**
     * @param PaymentInterface|PlatformObjectInterface $payment
     * @param string                                   $action
     *
     * @return array
     * @throws PlatformException
     */
    public function transform($payment, string $action = ''): array
    {
        $this->checkSupports($payment);

        $data = [];

        /** @var CustomerInterface|PlatformObjectInterface $customer */
        $customer = $payment->getCustomer();
        /** @var SourceInterface|PlatformObjectInterface $source */
        $source = $payment->getSource();

        switch ($payment->getType()) {
            case PaymentInterface::TYPE_CHARGE:
                $data['charge'] = [
                    'customer' => $customer->getPlatformId(),
                    'source' => $source->getPlatformId(),
                    'amount' => (int) ($payment->getAmount() * 100),
                    'currency' => $payment->getCurrency(),
                ];

                if ($payment->getConcept()) {
                    $data['charge']['description'] = $payment->getConcept();
                }

                break;

            case PaymentInterface::TYPE_REFUND:
                $data['refund'] = [];
                break;

            default:
                throw new PlatformException('stripe', 'Bad payment type');
        }

        return $data;
    }

    /**
     * @param Charge|Refund                                 $stripePayment
     * @param PaymentInterface|PlatformObjectInterface|null $payment
     * @param string                                        $action
     *
     * @return PaymentInterface
     * @throws TransformException
     */
    public function reverseTransform($stripePayment, $payment = null, string $action = ''): PaymentInterface
    {
        $this->checkSupports($payment);

        if ($payment instanceof PlatformByObjectInterface) {
            $payment->setPlatform('stripe');
        }

        $payment->setPlatformId($stripePayment->id);
        $payment->setTestMode(!$stripePayment->livemode);
        $payment->setPlatformLastSync(\DateTime::createFromFormat('U', $stripePayment->created)); // TODO update last sync date
        $payment->setPlatformConflict(false);
        $payment->setPlatformData($stripePayment->toArray());

        if ($stripePayment instanceof Charge) {
            $payment->setStatus(self::MAPPING_STATUSES[$stripePayment->status]);
            $payment->setDate(\DateTime::createFromFormat('U', $stripePayment->created));
            $payment->setConcept($stripePayment->description);
        }

        if ($stripePayment instanceof Refund) {

        }

        return $payment;
    }
}