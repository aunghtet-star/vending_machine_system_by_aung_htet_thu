<?php

namespace App\Core;

use Exception;
use ReflectionClass;
use ReflectionNamedType;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * Bind a class or interface to a concrete class or closure
     */
    public function bind(string $abstract, mixed $concrete = null): void
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Bind a singleton
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null; // Mark as singleton
    }

    /**
     * Resolve a class dependency
     */
    public function get(string $abstract): mixed
    {
        // Check if it's a singleton and already resolved
        if (array_key_exists($abstract, $this->instances) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }

        // Get concrete implementation
        $concrete = $this->bindings[$abstract] ?? $abstract;

        // If it's a closure, execute it
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } elseif (is_object($concrete)) {
            $object = $concrete;
        } else {
            // Use reflection to resolve
            $object = $this->resolve($concrete);
        }

        // Save instance if singleton
        if (array_key_exists($abstract, $this->instances)) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Resolve dependencies using Reflection
     */
    private function resolve(string $concrete): object
    {
        if (!class_exists($concrete)) {
            throw new Exception("Class {$concrete} not found");
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve method parameters
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve class dependency {$parameter->name}");
                }
            } else {
                $dependencies[] = $this->get($type->getName());
            }
        }

        return $dependencies;
    }
}
