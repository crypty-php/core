# 🔒 CryptyPHP : your new friend for database encryption in PHP

CryptyPHP is a modern Symfony bundle that helps developer setting up encryption on targeted database columns, with flexible configuration and full customization.

![Symfony >= 7.0](https://img.shields.io/badge/Symfony-7.0-blue?logo=symfony&link=https%3A%2F%2Fsymfony.com%2Fdoc%2F7.0%2Findex.html) ![Symfony >= 8.0](https://img.shields.io/badge/Symfony-8.0-blue?logo=symfony&link=https%3A%2F%2Fsymfony.com%2Fdoc%2F8.0%2Findex.html) ![PHP 8.2](https://img.shields.io/badge/PHP-8.2-blue?logo=php&link=https%3A%2F%2Fwww.php.net%2Fdocs.php)

## 🚀 Installation

```shell
composer require crypty-php/core
```

By default, CryptyPHP comes empty 🕳️. In order to make it work, you must select your implementation. At the moment, CryptyPHP only provides implementations for [Doctrine](https://www.doctrine-project.org/), for the column collecting part, and [Halite](https://github.com/paragonie/halite) for the encryption part.

You can install the full package by running the following command :

```shell
composer require crypty-php/core crypty-php/doctrine crypty-php/halite
```

If you don't want to use [Halite](https://github.com/paragonie/halite), you can also [create your own encryptor](#custom).

## 🔧 Configuration

CryptyPHP comes with a friendly configuration that allows developers to configure **encryptors**. Once configured, you can use it on targeted database columns.

Example of configuration using [Halite](https://github.com/paragonie/halite) :

```yaml
crypty:
    encryptors:
        default:
            type: halite
```

> ⚠️ If `crypty-php/halite` is not installed, an Exception will be thrown.

Available types are :
- halite : uses [Halite](https://github.com/paragonie/halite) library
- custom : uses a [custom encryption provider](#custom)

## 🎯 Target a column

Independently of your chosen implementation, CryptyPHP comes with a PHP attribute, called `Encrypt`, which is meant to be used on a property of a database model. This property is then flagged as a property that must be encrypted/decrypted.

```php
use Crypty\Core\Attribute\Encrypt;

class Model
{
    #[Encrypt]
    public string $encrypted;
}
```

By default, a property will be encrypted using the `default` configuration. If you want to choose a specific configuration, you can !

```php
use Crypty\Core\Attribute\Encrypt;

class Model
{
    #[Encrypt(usingConfig: 'other')]
    public string $encrypted;
}
```

## <a name="custom"></a> ✏️ Create a custom encryption provider

An encryption provider comes in two parts :
- a key provider
- an encryptor (which requires the key provider for obvious reasons)

Technically, the key provider **is not mandatory**. It is just here to hold the logic for key generation and reading.

To make a key provider, you must implement the `Crypty\Core\Key\KeyProviderInterface` interface :

```php
/**
 * @implements KeyProviderInterface<MyKey>
 */
final readonly class MyKeyProvider implements KeyProviderInterface
{
    public function generate(array $config): string
    {
        // ...
    }
    
    public function load(array $config): MyKey
    {
        // ...
    }
}
```

> ℹ️ The `generate` method must return the file path of the key.

Anyway, let's talk real business now : the encryptor ! It must implement the `Crypty\Core\Encryptor\EncryptorInterface` interface :

```php
final readonly class MyEncryptor implements EncryptorInterface
{
    public function encrypt(Property $property, array $config): string
    {
        // ...
    }
    
    public function decrypt(Property $property, array $config): mixed
    {
        // ...
    }
}
```

Then, you can simply inject your key provider into your encryptor in order to generate and retrieve the encryption key.

The `$config` parameter is the configuration defined for the encryptor config matching the encryptor class. Oh yeah, we didn't mention this yet...

By default, your custom encryptor is nothing without configuration. In order to use it, you will have to add it your YAML configuration, and attach it a **custom class**, which is your encryptor class :

```yaml
crypty:
    encryptors:
        default:
            type: custom
            class: App\Encryption\MyEncryptor
```

Every property flagged with the `Encrypt` attribute and the `default` configuration will be encrypted/decrypted with your custom encryptor !

## 📁 Storage

By default, every generated will be generated into the project root directory. This is probably not what you want, unless you're a messy developer !

Thankfully, CryptyPHP comes with multiple ways to configure your storage directory. You can do it :
- globally
- by encryptor

Example :

```yaml
crypty:
    encryptors:
        default:
            type: halite
            # For this specific encryptor
            storage_directory: '%kernel.project_dir%/config/halite'
    # Globally
    storage_directory: '%kernel.project_dir%/config/keys'
```

It is also possible to configure the filename of the key. By default, the name will match the encryptor **name** from the YAML configuration. The ".key" extension is always added.

For instance, for the `default` configuration, the name will be "default.key". In order to configure the name, you can use the `filename` option on an encryptor configuration :

```yaml
crypty:
    encryptors:
        default:
            type: halite
            filename: Halite
```
