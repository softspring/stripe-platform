<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\CustomerBundle\Model\CustomerBillingAddressInterface;
use Softspring\CustomerBundle\Model\CustomerInterface;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformByObjectInterface;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\Customer;

class CustomerTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    public function supports($customer): bool
    {
        return $customer instanceof CustomerInterface;
    }

    /**
     * @param CustomerInterface|PlatformObjectInterface $customer
     * @param string                                    $action
     *
     * @return array
     * @throws TransformException
     */
    public function transform($customer, string $action = ''): array
    {
        $this->checkSupports($customer);

        $data = [
            'customer' => [],
        ];

        if (method_exists($customer, 'getEmail')) {
            $data['customer']['email'] = $customer->getEmail();
        }

        if ($customer->getTaxIdCountry() && $customer->getTaxIdNumber()) {
            // @see https://stripe.com/docs/billing/taxes/tax-ids
            switch (strtolower($customer->getTaxIdCountry())) {
                case 'es':
                    $type = 'es_cif';
                    break;

                default:
                    $type = null;
                    break;
            }

            if ($type) {
                $data['tax_id'] = [
                    'type' => $type,
                    'value' => $customer->getTaxIdNumber(),
                ];
            }
        }

        if (method_exists($customer, 'getName')) {
            $data['customer']['name'] = $customer->getName();
        }

        if ($customer instanceof CustomerBillingAddressInterface && $customer->getBillingAddress()) {
            $data['customer']['description'] = $data['customer']['name'];

            $data['customer']['name'] = trim("{$customer->getBillingAddress()->getName()} {$customer->getBillingAddress()->getSurname()}");
            $data['customer']['address']['line1'] = $customer->getBillingAddress()->getStreetAddress();
            $data['customer']['address']['line2'] = $customer->getBillingAddress()->getExtendedAddress();
            $data['customer']['address']['city'] = $customer->getBillingAddress()->getLocality();
            $data['customer']['address']['postal_code'] = $customer->getBillingAddress()->getPostalCode();
            $data['customer']['address']['state'] = $customer->getBillingAddress()->getRegion();
            $data['customer']['address']['country'] = $customer->getBillingAddress()->getCountryCode();

            if ($customer->getBillingAddress()->getTel()) {
                $data['customer']['phone'] = $customer->getBillingAddress()->getTel();
            }
        }

        return $data;
    }

    /**
     * @param Customer                                       $stripeCustomer
     * @param CustomerInterface|PlatformObjectInterface|null $customer
     * @param string                                         $action
     *
     * @return CustomerInterface
     * @throws TransformException
     */
    public function reverseTransform($stripeCustomer, $customer = null, string $action = ''): CustomerInterface
    {
        if (null === $customer) {
            // TODO CALL MANAGER TO CREATE ONE CUSTOMER OBJECT
        }

        $this->checkSupports($customer);

        if ($customer instanceof PlatformByObjectInterface) {
            $customer->setPlatform('stripe');
        }

        $customer->setPlatformId($stripeCustomer->id);
        $customer->setTestMode(!$stripeCustomer->livemode);
        $customer->setPlatformLastSync(\DateTime::createFromFormat('U', $stripeCustomer->created)); // TODO update last sync date
        $customer->setPlatformConflict(false);
        $customer->setPlatformData($stripeCustomer->toArray());

        return $customer;
    }
}