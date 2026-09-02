<?php

namespace Crypty\Core\Enum;

enum EncryptorConfigType: string
{
    case Halite = 'halite';
    case Custom = 'custom';

    public function isAvailable(): bool
    {
        return match ($this) {
            self::Halite => class_exists('ParagonIE\Halite\Halite'),
            default => true,
        };
    }

    public function getBundleSuggestion(): ?string
    {
        return match ($this) {
            self::Halite => 'crypty-php/halite',
            default => null,
        };
    }

    public function getDefaultEncryptorClass(): ?string
    {
        return match ($this) {
            self::Halite => 'Crypty\Halite\Encryptor\HaliteEncryptor',
            default => null,
        };
    }
}
