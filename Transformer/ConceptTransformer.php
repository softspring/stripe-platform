<?php

namespace Softspring\PlatformBundle\Stripe\Transformer;

use Softspring\PaymentBundle\Model\ConceptInterface;
use Softspring\PlatformBundle\Exception\TransformException;
use Softspring\PlatformBundle\Model\PlatformObjectInterface;
use Softspring\PlatformBundle\Transformer\PlatformTransformerInterface;
use Stripe\InvoiceItem;

class ConceptTransformer extends AbstractPlatformTransformer implements PlatformTransformerInterface
{
    public function supports($concept): bool
    {
        return $concept instanceof ConceptInterface;
    }

    /**
     * @param ConceptInterface|PlatformObjectInterface $concept
     * @param string                                    $action
     *
     * @return array
     * @throws TransformException
     */
    public function transform($concept, string $action = ''): array
    {
        $this->checkSupports($concept);

        $data = [
            'concept' => [
                'customer' => $concept->getCustomer()->getPlatformId(),
                'currency' => $concept->getCurrency(),
                'description' => $concept->getConcept(),
            ],
        ];

        if ($concept->getQuantity() && $concept->getPrice()) {
            $data['concept']['quantity'] = $concept->getQuantity();
            $data['concept']['unit_amount'] = (int) ($concept->getPrice()*100);
        } else {
            $data['concept']['amount'] = (int) ($concept->getTotal()*100);
        }

        return $data;
    }

    /**
     * @param InvoiceItem                                   $stripeConcept
     * @param ConceptInterface|PlatformObjectInterface|null $concept
     * @param string                                        $action
     *
     * @return ConceptInterface
     * @throws TransformException
     */
    public function reverseTransform($stripeConcept, $concept = null, string $action = ''): ConceptInterface
    {
        if (null === $concept) {
            // TODO CALL MANAGER TO CREATE ONE CONCEPT OBJECT
        }

        $this->checkSupports($concept);
        $this->reverseTransformPlatformObject($concept, $stripeConcept);

        $concept->setConcept($stripeConcept->description);
        $concept->setTotal($stripeConcept->amount/100);
        $concept->setPrice($stripeConcept->unit_amount/100);
        $concept->setQuantity($stripeConcept->quantity);
        $concept->setCurrency($stripeConcept->currency);

        // TODO set currency
        // TODO set date
        // TODO set discountable
        // TODO set invoice
        // TODO set period
        // TODO set plan
        // TODO set proration
        // TODO set subscription
        // TODO set tax_rates

        return $concept;
    }
}