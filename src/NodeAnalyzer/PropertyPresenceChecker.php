<?php

declare(strict_types=1);

namespace Rector\Core\NodeAnalyzer;

use PhpParser\Node\Stmt\Class_;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\ClassExistenceStaticHelper;
use Rector\NodeTypeResolver\Node\AttributeKey;
use ReflectionClass;
use ReflectionProperty;

final class PropertyPresenceChecker
{
    /**
     * @var PromotedPropertyResolver
     */
    private $promotedPropertyResolver;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    public function __construct(PromotedPropertyResolver $promotedPropertyResolver, NodeNameResolver $nodeNameResolver)
    {
        $this->promotedPropertyResolver = $promotedPropertyResolver;
        $this->nodeNameResolver = $nodeNameResolver;
    }

    public function hasClassPropertyByName(Class_ $class, string $propertyName): bool
    {
        $className = $class->getAttribute(AttributeKey::CLASS_NAME);
        if ($className === null) {
            return false;
        }

        if (! ClassExistenceStaticHelper::doesClassLikeExist($className)) {
            return false;
        }

        $availablePropertyReflections = $this->getParentClassPublicAndProtectedPropertyReflections($className);

        foreach ($availablePropertyReflections as $availablePropertyReflection) {
            if ($availablePropertyReflection->getName() !== $propertyName) {
                continue;
            }

            return true;
        }

        $promotedPropertyParams = $this->promotedPropertyResolver->resolveFromClass($class);
        foreach ($promotedPropertyParams as $promotedPropertyParam) {
            if ($this->nodeNameResolver->isName($promotedPropertyParam, $propertyName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ReflectionProperty[]
     */
    private function getParentClassPublicAndProtectedPropertyReflections(string $className): array
    {
        /** @var string[] $parentClassNames */
        $parentClassNames = (array) class_parents($className);

        $propertyReflections = [];

        foreach ($parentClassNames as $parentClassName) {
            $parentClassReflection = new ReflectionClass($parentClassName);

            $currentPropertyReflections = $parentClassReflection->getProperties(
                ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED
            );
            $propertyReflections = array_merge($propertyReflections, $currentPropertyReflections);
        }

        return $propertyReflections;
    }
}
