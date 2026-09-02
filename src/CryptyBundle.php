<?php

namespace Crypty\Core;

use Crypty\Core\Encryptor\EncryptorInterface;
use Crypty\Core\Enum\EncryptorConfigType;
use Crypty\Core\Exception\UnavailableEncryptorTypeException;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class CryptyBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.php');

        $this->loadDoctrine($config, $configurator);
        $this->loadHalite($configurator);

        // Processes encryptors config
        $encryptorConfigs = [];

        foreach ($config['encryptors'] ?? [] as $name => $encryptorConfig) {
            $encryptorType = EncryptorConfigType::tryFrom($encryptorConfig['type']);

            if (null === $encryptorType) {
                $availableTypes = \array_map(
                    static fn (EncryptorConfigType $type) => $type->value,
                    EncryptorConfigType::cases(),
                );

                throw new \LogicException(\sprintf(
                    'Encrypt of type "%s" is not valid. Valid types are : %s.',
                    $encryptorConfig['type'],
                    \implode(', ', $availableTypes),
                ));
            }

            if (!$encryptorType->isAvailable()) {
                throw new UnavailableEncryptorTypeException(
                    type: $encryptorType,
                    configurationName: $name,
                );
            }

            if (isset($encryptorConfig['class'])) {
                if (!\is_a($encryptorConfig['class'], EncryptorInterface::class, allow_string: true)) {
                    throw new \LogicException(\sprintf(
                        'Parameter $class of encryptor "%s" must implement %s, but it does not.',
                        $name,
                        EncryptorInterface::class,
                    ));
                }
            } else {
                if (EncryptorConfigType::Custom === $encryptorType) {
                    throw new \LogicException(\sprintf('Configuration "%s" does not define required encryptor class.', $name));
                }
                $encryptorConfig['class'] = $encryptorType->getDefaultEncryptorClass();
            }

            // If name is not configured, uses the type as a name
            if (!isset($encryptorConfig['filename'])) {
                $encryptorConfig['filename'] = $name . '.key';
            }

            if (!\str_ends_with($encryptorConfig['filename'], '.key')) {
                $encryptorConfig['filename'] .= '.key';
            }

            $encryptorConfigs[$name] = $encryptorConfig;
        }

        $container->setParameter('crypty.encryptors', $encryptorConfigs);

        // Keys storage directory
        $directory = $config['storage_directory'] ?? $container->getParameter('kernel.project_dir');
        $container->setParameter('crypty.defaults.storage_directory', $directory);

        // Tags encryptor services
        $container->registerForAutoconfiguration(EncryptorInterface::class)->addTag('crypty.encryptor');
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $node = $definition->rootNode();

        $node->children()
            ->arrayNode('encryptors')
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                ->children()
                    ->enumNode('type')
                        ->values(\array_map(static fn (EncryptorConfigType $type) => $type->value, EncryptorConfigType::cases()))
                        ->isRequired()
                    ->end()
                    ->scalarNode('class')
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifString()
                            ->then(static function (string $value) {
                                if (!\class_exists($value)) {
                                    throw new \InvalidArgumentException(\sprintf('Class "%s" does not exist.', $value));
                                }

                                return $value;
                            })
                        ->end()
                    ->end()
                    ->scalarNode('storage_directory')->cannotBeEmpty()->end()
                    ->scalarNode('filename')->cannotBeEmpty()->end()
                ->end()
            ->end()
            ->end()
            ->scalarNode('storage_directory')->end()
            ->arrayNode('doctrine')
                ->{\interface_exists('Doctrine\ORM\EntityManagerInterface') ? 'canBeDisabled' : 'canBeEnabled'}()
            ->end()
        ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadDoctrine(array $config, ContainerConfigurator $configurator): void
    {
        if (!($config['doctrine']['enabled'] ?? false)) {
            return;
        }

        $configurator->import('../config/doctrine.php');
    }

    private function loadHalite(ContainerConfigurator $configurator): void
    {
        if (!\class_exists('Crypty\Halite\Encryptor\HaliteEncryptor')) {
            return;
        }

        $configurator->import('../config/halite.php');
    }
}
