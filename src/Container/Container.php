<?php
declare(strict_types=1);

namespace Din9xtrCloud\Container;

use Closure;
use Din9xtrCloud\Container\Exceptions\ContainerException;
use Din9xtrCloud\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Throwable;

final class Container implements ContainerInterface
{
    /**
     * @var array<string, Definition>
     */
    private array $definitions = [];
    /**
     * @var array<string, mixed>
     */
    private array $shared = [];
    /**
     * @var array<string, mixed>
     */
    private array $request = [];

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * @param string $id
     * @param callable|object $concrete
     * @return void
     */
    public function singleton(string $id, callable|object $concrete): void
    {
        $this->define($id, $concrete, Scope::Shared);
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function request(string $id, callable $factory): void
    {
        $this->define($id, $factory, Scope::Request);
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function factory(string $id, callable $factory): void
    {
        $this->define($id, $factory, Scope::Factory);
    }

    /**
     * @param string $id
     * @param callable|object $concrete
     * @param Scope $scope
     * @return void
     */
    private function define(string $id, callable|object $concrete, Scope $scope): void
    {
        $factory = $concrete instanceof Closure
            ? $concrete
            : (is_callable($concrete) ? $concrete(...) : fn() => $concrete);

        $this->definitions[$id] = new Definition($factory, $scope);
    }

    public function get(string $id)
    {
        if ($def = $this->definitions[$id] ?? null) {

            return match ($def->scope) {
                Scope::Shared => $this->shared[$id]
                    ??= ($def->factory)($this),

                Scope::Request => $this->request[$id]
                    ??= ($def->factory)($this),

                Scope::Factory => ($def->factory)($this),
            };
        }

        if (class_exists($id)) {
            return $this->shared[$id] ??= $this->autowire($id);
        }

        throw new NotFoundException("Service $id not found");
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return mixed
     * @throws ContainerException
     */
    private function autowire(string $class): mixed
    {
        try {
            $ref = new ReflectionClass($class);

            if (!$ref->isInstantiable()) {
                throw new ContainerException("Class $class is not instantiable");
            }

            $ctor = $ref->getConstructor();
            if ($ctor === null) {
                return new $class;
            }

            $deps = [];
            foreach ($ctor->getParameters() as $param) {

                $type = $param->getType();

                if ($type === null) {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$param->getName()} of $class: no type specified"
                    );
                }

                if ($type instanceof ReflectionUnionType) {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$param->getName()} of $class: union types not supported"
                    );
                }

                if (!$type instanceof ReflectionNamedType) {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$param->getName()} of $class: intersection types not supported"
                    );
                }

                if ($type->isBuiltin()) {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$param->getName()} of $class: built-in type '{$type->getName()}' not supported"
                    );
                }

                $typeName = $type->getName();

                if (!class_exists($typeName) && !interface_exists($typeName)) {
                    throw new ContainerException(
                        "Cannot resolve parameter \${$param->getName()} of $class: type '$typeName' not found"
                    );
                }


                $deps[] = $this->get($type->getName());
            }

            return $ref->newInstanceArgs($deps);
        } catch (Throwable $e) {
            throw new ContainerException("Reflection failed for $class", 0, $e);
        }
    }

    public function beginRequest(): void
    {
        $this->request = [];
    }
}
