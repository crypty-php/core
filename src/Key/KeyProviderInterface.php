<?php

namespace Crypty\Core\Key;

/**
 * @phpstan-template T
 */
interface KeyProviderInterface
{
    /**
     * Defines how an encryption key should be generated.
     *
     * @param array<string, mixed> $config
     */
    public function generate(array $config): string;

    /**
     * Defines how to load an encryption and returns it.
     *
     * @param array<string, mixed> $config
     *
     * @phpstan-return T
     */
    public function load(array $config): mixed;
}
