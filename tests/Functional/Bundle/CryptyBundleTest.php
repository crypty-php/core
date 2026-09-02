<?php

namespace Crypty\Core\Tests\Functional\Bundle;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CryptyBundleTest extends KernelTestCase
{
    public function testDefaultStorageDirectoryIsProjectDir(): void
    {
        self::assertTrue(self::getContainer()->hasParameter('crypty.defaults.storage_directory'));

        self::assertSame(
            self::getContainer()->getParameter('kernel.project_dir'),
            self::getContainer()->getParameter('crypty.defaults.storage_directory'),
        );
    }

    public function testEncryptorsIsEmptyArrayByDefault(): void
    {
        self::assertTrue(self::getContainer()->hasParameter('crypty.encryptors'));
        self::assertSame([], self::getContainer()->getParameter('crypty.encryptors'));
    }
}
