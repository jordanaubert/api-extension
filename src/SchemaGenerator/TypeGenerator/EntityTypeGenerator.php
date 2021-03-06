<?php

/*
 * This file is part of the API Extension project.
 *
 * (c) Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiExtension\SchemaGenerator\TypeGenerator;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class EntityTypeGenerator implements TypeGeneratorInterface
{
    /**
     * @var PropertyInfoExtractorInterface
     */
    private $propertyInfo;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function setPropertyInfo(PropertyInfoExtractorInterface $propertyInfo): void
    {
        $this->propertyInfo = $propertyInfo;
    }

    public function setAnnotationReader(Reader $reader): void
    {
        $this->reader = $reader;
    }

    public function supports(string $property, array $mapping, array $context = []): bool
    {
        return null !== $mapping['targetEntity'] && in_array($mapping['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE], true);
    }

    public function generate(string $property, array $mapping, array $context = []): array
    {
        $reflectionClass = new \ReflectionClass($mapping['targetEntity']);
        if (0 < count($this->propertyInfo->getProperties($mapping['targetEntity'], $context))) {
            $type = $this->container->get('schemaGenerator')->generate($reflectionClass, $context);
        } elseif ($this->reader->getClassAnnotation($reflectionClass, ApiResource::class)) {
            $type = [
                'type' => ['string'],
                'pattern' => $this->container->get('helper')->getItemUriPattern(new \ReflectionClass($mapping['targetEntity'])),
            ];
        } else {
            throw new \LogicException('Entity '.$mapping['targetEntity'].' does not have any property to serialize with following groups: '.implode(', ', $context['serializer_groups']));
        }
        if ($mapping['nullable'] ?? true) {
            if (!is_array($type['type'])) {
                $type['type'] = [$type['type']];
            }
            $type['type'][] = 'null';
            $type['type'] = array_unique($type['type']);
        }

        return $type;
    }
}
