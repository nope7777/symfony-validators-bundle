<?php

namespace N7\SymfonyValidatorsBundle\Validator;

use N7\SymfonyValidatorsBundle\Helpers\ConstrainsExtractionTrait;
use N7\SymfonyValidatorsBundle\Service\ConstrainsExtractor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraints;

class NestedObjectValidator extends ConstraintValidator
{
    private ConstrainsExtractor $extractor;

    public function __construct(ConstrainsExtractor $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * @param mixed $value
     * @param NestedObject $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        $nestedConstrains = $this->extractor->extract($constraint->class);

        $this->context
            ->getValidator()
            ->inContext($this->context)
            ->validate($value, $nestedConstrains, $this->context->getGroup());
    }
}
