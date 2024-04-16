<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use org\bovigo\vfs;
use ILIAS\Authentication\Password\ilLocalPasswordManager;
use ILIAS\Authentication\Password\ilLocalPasswordEncoderFactory;

require_once __DIR__ . '/../../../../vendor/composer/vendor/autoload.php';
require_once __DIR__ . '/ilUserBaseTest.php';

class ilObjUserPasswordTest extends ilUserBaseTest
{
    private const PASSWORD = 'password';
    private const ENCODED_PASSWORD = 'encoded';

    protected vfs\vfsStreamDirectory  $testDirectory;
    protected string $testDirectoryUrl;

    public function getTestDirectory(): vfs\vfsStreamDirectory
    {
        return $this->testDirectory;
    }

    public function setTestDirectory(vfs\vfsStreamDirectory $testDirectory): void
    {
        $this->testDirectory = $testDirectory;
    }

    public function getTestDirectoryUrl(): string
    {
        return $this->testDirectoryUrl;
    }

    public function setTestDirectoryUrl(string $testDirectoryUrl): void
    {
        $this->testDirectoryUrl = $testDirectoryUrl;
    }

    protected function setUp(): void
    {
        vfs\vfsStream::setup();
        $this->setTestDirectory(vfs\vfsStream::newDirectory('tests')->at(vfs\vfsStreamWrapper::getRoot()));
        $this->setTestDirectoryUrl(vfs\vfsStream::url('root/tests'));

        parent::setUp();
    }

    /**
     * @throws ilUserException
     */
    public function testExceptionIsRaisedIfPasswordManagerIsCreatedWithoutEncoderInformation(): void
    {
        $this->assertException(ilUserException::class);
        new ilLocalPasswordManager(['data_directory' => $this->getTestDirectoryUrl()]);
    }

    /**
     * @throws ilUserException
     */
    public function testExceptionIsRaisedIfPasswordManagerIsCreatedWithoutFactory(): void
    {
        $this->assertException(ilUserException::class);
        new ilLocalPasswordManager([
            'password_encoder' => 'md5',
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
    }

    /**
     * @throws ilUserException
     */
    public function testExceptionIsRaisedIfPasswordManagerIsCreatedWithoutValidFactory(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessageMatches('/' . ilLocalPasswordEncoderFactory::class . '/');

        new ilLocalPasswordManager([
            'password_encoder' => 'md5',
            'encoder_factory' => 'test',
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testInstanceCanBeCreated(): void
    {
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();
        $factory_mock->expects($this->exactly(2))->method('getSupportedEncoderNames')->will($this->onConsecutiveCalls(
            [
                'mockencoder',
                'second_mockencoder'
            ],
            [
                'mockencoder'
            ]
        ));

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'md5',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $this->assertInstanceOf('ilUserPasswordManager', $password_manager);
        $this->assertEquals('md5', $password_manager->getEncoderName());
        $this->assertEquals($factory_mock, $password_manager->getEncoderFactory());

        $this->assertTrue($password_manager->isEncodingTypeSupported('second_mockencoder'));
        $this->assertFalse($password_manager->isEncodingTypeSupported('second_mockencoder'));
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testPasswordManagerEncodesRawPasswordWithSalt(): void
    {
        $user_mock = $this->getMockBuilder(ilObjUser::class)->disableOriginalConstructor()->getMock();
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();

        $user_mock->expects($this->once())->method('setPasswordSalt')->with($this->isType('string'));
        $user_mock->expects($this->once())->method('getPasswordSalt')->willReturn('asuperrandomsalt');
        $user_mock->expects($this->once())->method('setPasswordEncodingType')->with($this->equalTo('mockencoder'));
        $user_mock->expects($this->once())->method('setPasswd')->with(
            $this->equalTo(self::ENCODED_PASSWORD),
            $this->equalTo(ilObjUser::PASSWD_CRYPTED)
        );

        $encoder->expects($this->once())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->once())->method('requiresSalt')->willReturn(true);
        $encoder->expects($this->once())->method('encodePassword')
                ->with(
                    $this->equalTo(self::PASSWORD),
                    $this->isType('string')
                )->willReturn(self::ENCODED_PASSWORD);

        $factory_mock->expects($this->once())->method('getEncoderByName')->willReturn($encoder);

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'mockencoder',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);

        $password_manager->encodePassword($user_mock, self::PASSWORD);
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testPasswordManagerEncodesRawPasswordWithoutSalt(): void
    {
        $user_mock = $this->getMockBuilder(ilObjUser::class)->disableOriginalConstructor()->getMock();
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();

        $user_mock->expects($this->once())->method('setPasswordSalt')->with($this->equalTo(null));
        $user_mock->expects($this->once())->method('getPasswordSalt')->willReturn(null);
        $user_mock->expects($this->once())->method('setPasswordEncodingType')->with($this->equalTo('mockencoder'));
        $user_mock->expects($this->once())->method('setPasswd')->with(
            $this->equalTo(self::ENCODED_PASSWORD),
            $this->equalTo(ilObjUser::PASSWD_CRYPTED)
        );

        $encoder->expects($this->once())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->once())->method('requiresSalt')->willReturn(false);
        $encoder->expects($this->once())->method('encodePassword')->with(
            $this->equalTo(self::PASSWORD),
            $this->equalTo(null)
        )->willReturn(self::ENCODED_PASSWORD);

        $factory_mock->expects($this->once())->method('getEncoderByName')->willReturn($encoder);

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'mockencoder',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);

        $password_manager->encodePassword($user_mock, self::PASSWORD);
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testPasswordManagerVerifiesPassword(): void
    {
        $user_mock = $this->getMockBuilder(ilObjUser::class)->disableOriginalConstructor()->getMock();
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();

        $user_mock->expects($this->atLeast(1))->method('getPasswordSalt')->willReturn('asuperrandomsalt');
        $user_mock->expects($this->atLeast(1))->method('getPasswordEncodingType')->willReturn('mockencoder');
        $user_mock->expects($this->atLeast(1))->method('getPasswd')->willReturn(self::ENCODED_PASSWORD);
        $user_mock->expects($this->never())->method('resetPassword');

        $encoder->expects($this->once())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->once())->method('isPasswordValid')->with(
            $this->equalTo(self::ENCODED_PASSWORD),
            $this->equalTo(self::PASSWORD),
            $this->isType('string')
        )->willReturn(true);
        $encoder->expects($this->once())->method('requiresReencoding')
                ->with($this->equalTo(self::ENCODED_PASSWORD))
                ->willReturn(false);

        $factory_mock->expects($this->once())->method('getEncoderByName')->willReturn($encoder);

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'mockencoder',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);

        $this->assertTrue($password_manager->verifyPassword($user_mock, self::PASSWORD));
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testPasswordManagerMigratesPasswordOnVerificationWithVariantEncoders(): void
    {
        $user_mock = $this->getMockBuilder(ilObjUser::class)->disableOriginalConstructor()->getMock();
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();

        $user_mock->expects($this->once())->method('getPasswordSalt')->willReturn('asuperrandomsalt');
        $user_mock->expects($this->once())->method('getPasswordEncodingType')->willReturn('second_mockencoder');
        $user_mock->expects($this->once())->method('getPasswd')->willReturn(self::ENCODED_PASSWORD);
        $user_mock->expects($this->once())->method('resetPassword')->with(
            $this->equalTo(self::PASSWORD),
            $this->equalTo(self::PASSWORD)
        );

        $encoder->expects($this->once())->method('getName')->willReturn('second_mockencoder');
        $encoder->expects($this->once())->method('isPasswordValid')->with(
            $this->equalTo(self::ENCODED_PASSWORD),
            $this->equalTo(self::PASSWORD),
            $this->isType('string')
        )->willReturn(true);
        $encoder->expects($this->never())->method('requiresReencoding')
                ->with($this->equalTo(self::ENCODED_PASSWORD))
                ->willReturn(false);

        $factory_mock->expects($this->once())->method('getEncoderByName')->willReturn($encoder);

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'mockencoder',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);

        $this->assertTrue($password_manager->verifyPassword($user_mock, self::PASSWORD));
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testPasswordManagerReencodesPasswordIfReencodingIsNecessary(): void
    {
        $user_mock = $this->getMockBuilder(ilObjUser::class)->disableOriginalConstructor()->getMock();
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();

        $user_mock->expects($this->once())->method('getPasswordSalt')->willReturn('asuperrandomsalt');
        $user_mock->expects($this->once())->method('getPasswordEncodingType')->willReturn('mockencoder');
        $user_mock->expects($this->exactly(2))->method('getPasswd')->willReturn(self::ENCODED_PASSWORD);
        $user_mock->expects($this->once())->method('resetPassword')->with(
            $this->equalTo(self::PASSWORD),
            $this->equalTo(self::PASSWORD)
        );

        $encoder->expects($this->once())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->once())->method('isPasswordValid')->with(
            $this->equalTo(self::ENCODED_PASSWORD),
            $this->equalTo(self::PASSWORD),
            $this->isType('string')
        )->willReturn(true);
        $encoder->expects($this->once())->method('requiresReencoding')
                ->with($this->equalTo(self::ENCODED_PASSWORD))
                ->willReturn(true);

        $factory_mock->expects($this->once())->method('getEncoderByName')->willReturn($encoder);

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'mockencoder',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);

        $this->assertTrue($password_manager->verifyPassword($user_mock, self::PASSWORD));
    }

    /**
     * @throws ilUserException
     * @throws ReflectionException
     */
    public function testPasswordManagerNeverMigratesPasswordOnFailedVerificationWithVariantEncoders(): void
    {
        $user_mock = $this->getMockBuilder(ilObjUser::class)->disableOriginalConstructor()->getMock();
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $factory_mock = $this->getMockBuilder(ilLocalPasswordEncoderFactory::class)->disableOriginalConstructor()->getMock();

        $user_mock->expects($this->once())->method('getPasswordSalt')->willReturn('asuperrandomsalt');
        $user_mock->expects($this->once())->method('getPasswordEncodingType')->willReturn('second_mockencoder');
        $user_mock->expects($this->once())->method('getPasswd')->willReturn(self::ENCODED_PASSWORD);
        $user_mock->expects($this->never())->method('resetPassword');

        $encoder->expects($this->once())->method('getName')->willReturn('second_mockencoder');
        $encoder->expects($this->never())->method('requiresReencoding');
        $encoder->expects($this->once())->method('isPasswordValid')
                ->with(
                    $this->equalTo(self::ENCODED_PASSWORD),
                    $this->equalTo(self::PASSWORD),
                    $this->isType('string')
                )->willReturn(false);

        $factory_mock->expects($this->once())->method('getEncoderByName')->willReturn($encoder);

        $password_manager = new ilLocalPasswordManager([
            'password_encoder' => 'mockencoder',
            'encoder_factory' => $factory_mock,
            'data_directory' => $this->getTestDirectoryUrl()
        ]);

        $this->assertFalse($password_manager->verifyPassword($user_mock, self::PASSWORD));
    }

    /**
     * @throws ilPasswordException
     */
    public function testFactoryCanBeCreated(): void
    {
        $factory = new ilLocalPasswordEncoderFactory([
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $this->assertInstanceOf(ilLocalPasswordEncoderFactory::class, $factory);
    }

    /**
     * @throws ReflectionException
     * @throws ilPasswordException
     * @throws ilUserException
     */
    public function testGettersOfFactoryShouldReturnWhatWasSetBySetters(): void
    {
        $factory = new ilLocalPasswordEncoderFactory([
            'default_password_encoder' => 'md5',
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $this->assertEquals('md5', $factory->getDefaultEncoder());

        $encoder = $this->createMock(ilPasswordEncoder::class);
        $encoder->expects($this->atLeastOnce())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->atLeastOnce())->method('isSupportedByRuntime')->willReturn(true);

        $second_mockencoder = $this->createMock(ilPasswordEncoder::class);
        $second_mockencoder->expects($this->atLeastOnce())->method('getName')->willReturn('second_mockencoder');
        $second_mockencoder->expects($this->atLeastOnce())->method('isSupportedByRuntime')->willReturn(true);

        $factory->setSupportedEncoders([$encoder, $second_mockencoder]);
        $this->assertCount(2, $factory->getSupportedEncoders());
        $this->assertCount(2, $factory->getSupportedEncoderNames());
        $this->assertCount(
            0,
            array_diff(['mockencoder', 'second_mockencoder'], $factory->getSupportedEncoderNames())
        );
        $this->assertCount(
            0,
            array_diff($factory->getSupportedEncoderNames(), ['mockencoder', 'second_mockencoder'])
        );
    }

    /**
     * @throws ilPasswordException
     * @throws ilUserException
     */
    public function testFactoryRaisesAnExceptionIfAnUnsupportedEncoderWasInjected(): void
    {
        $this->assertException(ilUserException::class);
        $factory = new ilLocalPasswordEncoderFactory([
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $factory->setSupportedEncoders(['phpunit']);
    }

    /**
     * @throws ilPasswordException
     * @throws ilUserException
     */
    public function testFactoryRaisesAnExceptionIfAnUnsupportedEncoderIsRequestedAndNoDefaultEncoderWasSpecifiedInFallbackMode(): void
    {
        $this->assertException(ilUserException::class);
        $factory = new ilLocalPasswordEncoderFactory([
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $factory->getEncoderByName('phpunit');
    }

    /**
     * @throws ilPasswordException
     * @throws ilUserException
     */
    public function testFactoryRaisesAnExceptionIfAnUnsupportedEncoderIsRequestedAndTheDefaultEncoderDoesNotMatchOneOfTheSupportedEncodersInFallbackMode(): void
    {
        $this->assertException(ilUserException::class);
        $factory = new ilLocalPasswordEncoderFactory([
            'default_password_encoder' => 'phpunit',
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $factory->getEncoderByName('phpunit');
    }

    /**
     * @throws ReflectionException
     * @throws ilPasswordException
     * @throws ilUserException
     */
    public function testFactoryReturnsTheDefaultEncoderIfAnUnsupportedEncoderIsRequestedAndASupportedDefaultEncoderWasSpecifiedInFallbackMode(): void
    {
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $encoder->expects($this->atLeastOnce())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->atLeastOnce())->method('isSupportedByRuntime')->willReturn(true);

        $factory = new ilLocalPasswordEncoderFactory([
            'default_password_encoder' => $encoder->getName(),
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $factory->setSupportedEncoders([$encoder]);
        $this->assertEquals($encoder, $factory->getEncoderByName('phpunit'));
    }

    /**
     * @throws ilUserException
     * @throws ilPasswordException
     * @throws ReflectionException
     */
    public function testFactoryReturnsCorrectEncoderIfAMatchingEncoderWasFound(): void
    {
        $encoder = $this->getMockBuilder(ilBasePasswordEncoder::class)->disableOriginalConstructor()->getMock();
        $encoder->expects($this->atLeastOnce())->method('getName')->willReturn('mockencoder');
        $encoder->expects($this->atLeastOnce())->method('isSupportedByRuntime')->willReturn(true);

        $factory = new ilLocalPasswordEncoderFactory([
            'default_password_encoder' => $encoder->getName(),
            'data_directory' => $this->getTestDirectoryUrl()
        ]);
        $factory->setSupportedEncoders([$encoder]);
        $this->assertEquals($encoder, $factory->getEncoderByName('mockencoder'));
    }
}
