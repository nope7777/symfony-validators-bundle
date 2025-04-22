<?php

declare(strict_types=1);

namespace N7\SymfonyValidatorsBundle\Service;

use Doctrine\Common\Annotations\Reader;
use N7\SymfonyValidatorsBundle\Options\AllowExtraFields;
use N7\SymfonyValidatorsBundle\Options\AllowMissingFields;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ReflectionClass;
use ReflectionAttribute;

final class ConstrainsExtractor
{
    private const VALIDATIOR_ALLOW_EXTRA_FIELDS = 'allowExtraFields';
    private const VALIDATIOR_ALLOW_MISSING_FIELDS = 'allowMissingFields';
    private const VALIDATIOR_FIELDS = 'fields';

    private ValidatorInterface $validator;
    private Reader $annotationsReader;

    public function __construct(ValidatorInterface $validator, Reader $reader)
    {
        $this->validator = $validator;
        $this->annotationsReader = $reader;
    }

    public function extract(string $class): Collection
    {
        $reflectionClass = new ReflectionClass($class);
        $meta = $this->validator->getMetadataFor($class);

        $constraints = $this->collectConstraints($reflectionClass, $meta);
        $allowExtraFields = $this->extractOption($reflectionClass, AllowExtraFields::class, true);
        $allowMissingFields = $this->extractOption($reflectionClass, AllowMissingFields::class, true);

        return new Collection([
            self::VALIDATIOR_ALLOW_EXTRA_FIELDS => $allowExtraFields,
            self::VALIDATIOR_ALLOW_MISSING_FIELDS => $allowMissingFields,
            self::VALIDATIOR_FIELDS => $constraints,
        ]);
    }

    private function collectConstraints(ReflectionClass $reflectionClass, ClassMetadata $meta): array
    {
        $constraints = array_map(
            fn(PropertyMetadataInterface $property): array => $property->getConstraints(),
            $meta->properties
        );

        foreach ($reflectionClass->getProperties() as $property) {
            $name = $property->getName();

            $attributeConstraints = array_map(
                fn(ReflectionAttribute $attributes) => $attributes->newInstance(),
                $property->getAttributes(Constraint::class, ReflectionAttribute::IS_INSTANCEOF)
            );

            if (!empty($attributeConstraints) || !isset($constraints[$name])) {
                $constraints[$name] = $attributeConstraints;
            }
        }

        return $constraints;
    }

    private function extractOption(
        ReflectionClass $reflectionClass,
        string $optionClass,
        bool $default
    ): bool {
        $attributes = $reflectionClass->getAttributes($optionClass);

        if (!empty($attributes)) {
            return $attributes[0]->newInstance()->getValue();
        }

        $annotation = $this->annotationsReader->getClassAnnotation($reflectionClass, $optionClass);

        return $annotation instanceof $optionClass ? $annotation->getValue() : $default;
    }
}