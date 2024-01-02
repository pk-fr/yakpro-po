<?php
namespace Obfuscator\Classes\Ignore;

use Obfuscator\Classes\Scope;

/**
 * Basic class, defining statement to ignore during obfusction
 */
class Ignore
{

    private ?string $namespace = null;
    private bool $isRegex = false;

    public function __construct(public readonly string $name, public array $scopes = [])
    {
        
    }

    public static function FUNCTION(string $name): Ignore
    {
        return new Ignore($name, [Scope::FUNCTION]);
    }

    public static function REGEX(string $regex): Ignore
    {
        return (new Ignore($regex, [Scope::FUNCTION]))->setIsRegex();
    }


    public static function CLASS(string $name): Ignore
    {
        return new Ignore($name, [Scope::CLSS]);
    }

    /**
     * Ignore multiple names, without scope (global)
     *
     * @param string[] $names
     * @param string[] $scopes
     * @return Ignore[]
     */
    public static function LIST(array $names, array $scopes = []): array
    {
        return array_map(fn(string $name): Ignore => new Ignore($name, $scopes), $names);
    }

    public static function REGEXLIST(array $regexes, array $scopes = []): array
    {
        return array_map(fn(string $regex): Ignore => (new Ignore($regex, $scopes))->setIsRegex(), $regexes);
    }

    public static function PREFIXES(array $prefixes, array $scopes = []): array
    {
        $regexes = array_map(fn(string $prefix): string => "^$prefix", $prefixes);
        return self::REGEXLIST($regexes, $scopes);
    }

    /**
     * Ignore multiple functions
     *
     * @param string[] $names
     * @return Ignore[]
     */
    public static function FUNCTIONS(array $names): array
    {
        return array_map(fn(string $name): Ignore => Ignore::FUNCTION($name), $names);
    }

    /**
     * Ignore multiple classnames
     *
     * @param string[] $names
     * @return Ignore[]
     */
    public static function CLASSES(array $names): array
    {
        return array_map(fn(string $name): Ignore => Ignore::CLASS($name), $names);
    }
    
    /**
     * Match this Ignore against input name, scope and namespace
     *
     * @param string $name
     * @param string|null $scope
     * @param string|null $namespace
     * @return bool
     */
    public function match(string $name, ?string $scope = null, ?string $namespace = null): bool
    {
        if ($this->name != $name) {
            return false;
        }
        if ($scope && !empty($this->scopes) && in_array($scope, $this->scopes)) {
            return false;
        }
        if ($this->namespace && $namespace && $namespace != $namespace) {
            return false;
        }
        return true;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function setNamespace(?string $namespace): static
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function setIsRegex(bool $isRegex = true): static
    {
        $this->isRegex = $isRegex;
        return $this;
    }

    public function addScope(string $scope): static
    {
        $this->scopes[] = $scope;
        return $this;
    }
}
