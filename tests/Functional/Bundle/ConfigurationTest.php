<?php

namespace Crypty\Core\Tests\Functional\Bundle;

use Crypty\Core\CryptyBundle;
use Crypty\Core\Encryptor\EncryptorInterface;
use Crypty\Core\Enum\EncryptorConfigType;
use Crypty\Core\Exception\UnavailableEncryptorTypeException;
use Crypty\Core\ValueObject\Property;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class ConfigurationTest extends TestCase
{
    public function testGlobalStorageDirectoryIsConfigured(): void
    {
        $builder = $this->loadConfiguration([
            'storage_directory' => __DIR__,
        ]);

        self::assertTrue($builder->hasParameter('crypty.defaults.storage_directory'));
        self::assertSame(__DIR__, $builder->getParameter('crypty.defaults.storage_directory'));
    }

    public function testThrowsExceptionIfEncryptorTypeIsNotValid(): void
    {
        $availableTypes = array_map(
            static fn (EncryptorConfigType $type) => $type->value,
            EncryptorConfigType::cases(),
        );

        $this->expectExceptionObject(new \LogicException(\sprintf(
            'Encrypt of type "test" is not valid. Valid types are : %s.',
            implode(', ', $availableTypes),
        )));

        $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => 'test',
                ],
            ],
        ]);
    }

    public function testThrowsExceptionIfEncryptorTypeIsNotAvailable(): void
    {
        $this->expectExceptionObject(new UnavailableEncryptorTypeException(
            type: EncryptorConfigType::Halite,
            configurationName: 'test',
        ));

        $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => EncryptorConfigType::Halite->value,
                ],
            ],
        ]);
    }

    public function testThrowsExceptionIfCustomEncryptorIsNotImplementingInterface(): void
    {
        $this->expectExceptionObject(new \LogicException(\sprintf(
            'Parameter $class of encryptor "test" must implement %s, but it does not.',
            EncryptorInterface::class,
        )));

        $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => EncryptorConfigType::Custom->value,
                    'class' => self::class,
                ],
            ],
        ]);
    }

    public function testThrowsExceptionIfCustomEncryptorDoesNotDefineClass(): void
    {
        $this->expectExceptionObject(new \LogicException('Configuration "test" does not define required encryptor class.'));

        $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => EncryptorConfigType::Custom->value,
                ],
            ],
        ]);
    }

    public function testEncryptorsParameterIsDefinedAndDefaultFilenameUsesEncryptorName(): void
    {
        $encryptor = self::getEncryptor();

        $builder = $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => EncryptorConfigType::Custom->value,
                    'class' => $encryptor::class,
                ],
            ],
        ]);

        self::assertTrue($builder->hasParameter('crypty.encryptors'));

        $encryptors = (array) $builder->getParameter('crypty.encryptors');

        self::assertArrayHasKey('test', $encryptors);
        self::assertSame(
            [
                'type' => EncryptorConfigType::Custom->value,
                'class' => $encryptor::class,
                'filename' => 'test.key',
            ],
            $encryptors['test'],
        );
    }

    public function testCustomFilenameIsSuccessfullyBound(): void
    {
        $encryptor = self::getEncryptor();

        $builder = $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => EncryptorConfigType::Custom->value,
                    'class' => $encryptor::class,
                    'filename' => 'custom.key',
                ],
            ],
        ]);

        self::assertTrue($builder->hasParameter('crypty.encryptors'));

        $encryptors = (array) $builder->getParameter('crypty.encryptors');

        self::assertArrayHasKey('test', $encryptors);
        self::assertSame(
            [
                'type' => EncryptorConfigType::Custom->value,
                'class' => $encryptor::class,
                'filename' => 'custom.key',
            ],
            $encryptors['test'],
        );
    }

    public function testKeyExtensionIsSuccessfullyAddedToFilename(): void
    {
        $encryptor = self::getEncryptor();

        $builder = $this->loadConfiguration([
            'storage_directory' => __DIR__,
            'encryptors' => [
                'test' => [
                    'type' => EncryptorConfigType::Custom->value,
                    'class' => $encryptor::class,
                    'filename' => 'custom',
                ],
            ],
        ]);

        self::assertTrue($builder->hasParameter('crypty.encryptors'));

        $encryptors = (array) $builder->getParameter('crypty.encryptors');

        self::assertArrayHasKey('test', $encryptors);
        self::assertSame(
            [
                'type' => EncryptorConfigType::Custom->value,
                'class' => $encryptor::class,
                'filename' => 'custom.key',
            ],
            $encryptors['test'],
        );
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function loadConfiguration(array $configuration): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $bundle = new CryptyBundle();

        $instanceof = [];
        $bundle->loadExtension(
            config: $configuration,
            configurator: new ContainerConfigurator(
                container: $builder,
                loader: new PhpFileLoader(
                    container: $builder,
                    locator: new FileLocator(),
                ),
                instanceof: $instanceof,
                path: __DIR__.'/../../src/',
                file: __DIR__.'/../../src/CryptyBundle.php',
            ),
            container: $builder,
        );

        return $builder;
    }

    private static function getEncryptor(): EncryptorInterface
    {
        return new class implements EncryptorInterface {
            public function encrypt(Property $property, array $config): string
            {
                return 'test';
            }

            public function decrypt(Property $property, array $config): string
            {
                return 'test';
            }
        };
    }
}
