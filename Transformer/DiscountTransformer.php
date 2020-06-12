<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PaymentBundle\Model\DiscountInterface;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Coupon;

class DiscountTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    const MAPPING_DUE_DURATION = [
        'forever' => DiscountInterface::DUE_NEVER,
        'once' => DiscountInterface::DUE_AFTER_ONCE,
        'repeating' => DiscountInterface::DUE_AFTER_REPEATS,
    ];

    public function supports($discount): bool
    {
        return $discount instanceof DiscountInterface;
    }

    /**
     * @param DiscountInterface|PlatformObjectInterface $discount
     * @param string                                    $action
     *
     * @return array
     * @throws TransformException
     */
    public function transform($discount, string $action = ''): array
    {
        $this->checkSupports($discount);

        $data = [];

        if (!in_array($discount->getTarget(), [DiscountInterface::TARGET_INVOICE, DiscountInterface::TARGET_SUBSCRIPTION])) {
            return $data;
        }

        if ($action == 'create') {
            $data['discount'] = [
                'name' => $discount->getName(),
                'currency' => strtolower($discount->getCurrency()),
            ];

            switch ($discount->getDue()) {
                case DiscountInterface::DUE_NEVER:
                    $data['discount']['duration'] = 'forever';
                    break;

                case DiscountInterface::DUE_AFTER_ONCE:
                    $data['discount']['duration'] = 'once';
                    break;

                case DiscountInterface::DUE_AFTER_REPEATS:
                    $data['discount']['duration'] = 'repeating';
                    $data['discount']['duration_in_months'] = 1; // TODO GET THIS
                    break;

                case DiscountInterface::DUE_DATE:
                    throw new TransformException('stripe','Stripe coupons does not support due on date');
                    break;
            }

            switch ($discount->getType()) {
                case DiscountInterface::TYPE_PERCENTAGE:
                    $data['discount']['percent_off'] = (int) $discount->getValue();
                    break;

                case DiscountInterface::TYPE_FIXED_AMOUNT:
                    $data['discount']['amount_off'] = (int) round($discount->getValue() * 100);
                    break;
            }
        }

        return $data;
    }

    /**
     * @param Coupon                                         $stripeCoupon
     * @param DiscountInterface|PlatformObjectInterface|null $discount
     * @param string                                         $action
     *
     * @return DiscountInterface
     * @throws TransformException
     */
    public function reverseTransform($stripeCoupon, $discount = null, string $action = ''): DiscountInterface
    {
        if (null === $discount) {
            // TODO CALL MANAGER TO CREATE ONE DISCOUNT OBJECT
        }

        $this->checkSupports($discount);
        $this->reverseTransformPlatformObject($discount, $stripeCoupon);

        $discount->setName($stripeCoupon->name);
        $discount->setDue(self::MAPPING_DUE_DURATION[$stripeCoupon->duration]);
        $discount->setCurrency(strtoupper($stripeCoupon->currency));

        if ($stripeCoupon->amount_off) {
            $discount->setType(DiscountInterface::TYPE_FIXED_AMOUNT);
            $discount->setValue($stripeCoupon->amount_off / 100);
        } elseif ($stripeCoupon->percent_off) {
            $discount->setType(DiscountInterface::TYPE_PERCENTAGE);
            $discount->setValue($stripeCoupon->percent_off);
        }

        return $discount;
    }
}