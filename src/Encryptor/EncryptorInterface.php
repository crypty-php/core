<?php

namespace Crypty\Core\Encryptor;

use Crypty\Core\ValueObject\Property;

/**
 * Defines behavior for every data encryptor.
 */
interface EncryptorInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function encrypt(Property $property, array $config): string;

    /**
     * @param array<string, mixed> $config
     */
    public function decrypt(Property $property, array $config): mixed;
}
