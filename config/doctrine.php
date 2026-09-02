<?php

use Crypty\Doctrine\Subscriber\DoctrineSubscriber;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

return static function (ContainerConfigurator $container): void {
    $container->services()->set('crypty.doctrine.subscriber', DoctrineSubscriber::class)
        ->tag('doctrine.event_listener', [
            'event' => Events::onFlush,
        ])
        ->tag('doctrine.event_listener', [
            'event' => Events::postLoad,
        ])
        ->args([
            '$encryptors' => tagged_locator('crypty.encryptor'),
            '$encryptorConfigs' => param('crypty.encryptors'),
            '$propertyAccessor' => new Reference('property_accessor'),
        ])
    ;
};
