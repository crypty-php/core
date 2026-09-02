<?php

use Crypty\Halite\Encryptor\HaliteEncryptor;
use Crypty\Halite\Key\HaliteKeyProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container): void {
    $container->services()->set(HaliteKeyProvider::class)
        ->args([
            '$storageDirectory' => param('crypty.defaults.storage_directory'),
        ])
        ->public()
    ;

    $container->services()->set(HaliteEncryptor::class)
        ->tag('crypty.encryptor')
        ->args([
            '$keyProvider' => new Reference(HaliteKeyProvider::class),
        ])
        ->public()
    ;
};
