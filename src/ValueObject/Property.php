<?php

namespace Crypty\Core\ValueObject;

final readonly class Property
{
    public function __construct(
        #[\SensitiveParameter] public mixed $value,
        public \ReflectionProperty $reflection,
    ) {
    }
}
