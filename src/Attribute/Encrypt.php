<?php

namespace Crypty\Core\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final readonly class Encrypt
{
    public function __construct(
        public string $usingConfig = 'default',
    ) {
    }
}
