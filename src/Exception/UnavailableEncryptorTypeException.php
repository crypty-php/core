<?php

namespace Crypty\Core\Exception;

use Crypty\Core\Enum\EncryptorConfigType;

final class UnavailableEncryptorTypeException extends \LogicException
{
    public function __construct(EncryptorConfigType $type, string $configurationName)
    {
        $message = \sprintf(
            'Type "%s" is not available for configuration named "%s".',
            $type->value,
            $configurationName,
        );

        if (null !== $installationSuggestion = $type->getBundleSuggestion()) {
            $message .= \sprintf(' Please install "%s" in order to be able to use it.', $installationSuggestion);
        }

        parent::__construct($message);
    }
}
