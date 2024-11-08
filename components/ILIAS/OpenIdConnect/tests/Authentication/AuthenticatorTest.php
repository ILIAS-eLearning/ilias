<?php

namespace ILIAS\OpenIdConnect\Tests\Authentication;

use ilCtrlInterface;
use ILIAS\DI\Container;
use ILIAS\HTTP\Services as HttpService;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\OpenIdConnect\Authentication\Authenticator;
use ILIAS\OpenIdConnect\Authentication\OpenIdConnectProvider;
use ILIAS\OpenIdConnect\Exceptions\AuthenticationException;
use ILIAS\Refinery\Factory;
use ilObjUser;
use ilSession;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function define;
use function defined;
use function str_starts_with;

class AuthenticatorTest extends TestCase
{
    private const TEST_CODE = 'test_code_1234';
    private const TEST_STATE = 'test_state_5678';
    private const TEST_PKCE_CODE = 'test_pkce_code';
    private const TEST_ID_TOKEN = 'test_id_token';

    /**
     * @var MockObject<ilCtrlInterface>
     */
    protected MockObject $ctrl;
    /**
     * @var MockObject<OpenIdConnectProvider>
     */
    protected MockObject $provider;
    /**
     * @var MockObject<HttpService>
     */
    protected MockObject $http;
    /**
     * @var MockObject<ArrayBasedRequestWrapper>
     */
    protected MockObject $query;

    protected Authenticator $authenticator;

    protected function setUp(): void
    {
        if (!defined('ILIAS_HTTP_PATH')) {
            define('ILIAS_HTTP_PATH', 'http://ilias.example.com');
        }

        $this->ctrl = $this->createMock(ilCtrlInterface::class);
        $this->provider = $this->createMock(OpenIdConnectProvider::class);
        [$this->http, $this->query] = $this->createHttpMocks();
        $this->authenticator = new Authenticator(
            $this->createMock(Factory::class),
            $this->http,
            $this->ctrl
        );
    }

    protected function tearDown(): void
    {
        ilSession::clear(Authenticator::SESSION_KEY_STATE);
        ilSession::clear(Authenticator::SESSION_KEY_CODE);
    }

    public function testAuthenticateWithoutCode()
    {
        $this->provider
            ->expects($this->once())
            ->method('getAuthorizationUrl')
            ->willReturn('http://auth.example.com/login');

        $this->ctrl
            ->expects($this->once())
            ->method('redirectToURL')
            ->with($this->callback(
                fn($url) => str_starts_with($url, 'http://auth.example.com/login')
            ));
        $this->authenticator->authenticate($this->provider, []);
    }

    public function testAuthenticateWithInvalidStateQuery()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid State');

        $this->query->method('retrieve')
            ->willReturnCallback(function ($name) {
                return match($name) {
                    'code' => self::TEST_CODE,
                    'state' => null
                };
            });

        ilSession::set(Authenticator::SESSION_KEY_STATE, self::TEST_STATE);

        $this->authenticator->authenticate($this->provider, []);
    }

    public function testAuthenticateWithInvalidStateSession()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid State');

        $this->query->method('retrieve')
            ->willReturnCallback(function ($name) {
                return match($name) {
                    'code' => self::TEST_CODE,
                    'state' => self::TEST_STATE
                };
            });

        $this->authenticator->authenticate($this->provider, []);
    }

    public function testAuthenticateWithCode()
    {
        $this->provider
            ->expects($this->never())
            ->method('getAuthorizationUrl')
            ->willReturn('http://auth.example.com');

        $this->query->method('retrieve')
            ->willReturnCallback(function ($name) {
                return match($name) {
                    'code' => self::TEST_CODE,
                    'state' => self::TEST_STATE
                };
            });

        \ilSession::set(Authenticator::SESSION_KEY_STATE, self::TEST_STATE);
        \ilSession::set(Authenticator::SESSION_KEY_CODE, self::TEST_PKCE_CODE);

        $this->provider
            ->expects($this->once())
            ->method('setPkceCode')
            ->with($this->equalTo(self::TEST_PKCE_CODE));
        $this->provider
            ->expects($this->once())
            ->method('getAccessToken')
            ->with(
                $this->equalTo('authorization_code'),
                $this->equalTo(['code' => self::TEST_CODE])
            )->willReturn($this->createMock(AccessToken::class));

        $access_token = $this->authenticator->authenticate($this->provider, []);
        $this->assertInstanceOf(AccessToken::class, $access_token);
    }


    public function testAuthenticateException()
    {
        $this->expectException(AuthenticationException::class);

        $this->provider
            ->expects($this->never())
            ->method('getAuthorizationUrl')
            ->willReturn('http://auth.example.com');

        $this->query->method('retrieve')
            ->willReturnCallback(function ($name) {
                return match($name) {
                    'code' => self::TEST_CODE,
                    'state' => self::TEST_STATE
                };
            });

        \ilSession::set(Authenticator::SESSION_KEY_STATE, self::TEST_STATE);
        \ilSession::set(Authenticator::SESSION_KEY_CODE, self::TEST_PKCE_CODE);

        $this->provider
            ->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException($this->createMock(IdentityProviderException::class));

        $this->authenticator->authenticate($this->provider, []);
    }

    public function testAuthenticateWithError()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid Scopes a b c');
        $this->query->method('has')
            ->willReturnCallback(function ($name) {
                return match($name) {
                    'error' => true,
                    'error_description' => true,
                    default => false
                };
            });
        $this->query->method('retrieve')
            ->willReturnCallback(function ($name) {
                return match($name) {
                    'error' => 'Invalid Scopes',
                    'error_description' => 'Invalid Scopes a b c',
                    default => null
                };
            });

        $this->authenticator->authenticate($this->provider, []);
    }

    public function testLogout()
    {
        global $DIC;

        $DIC = $this->createMock(Container::class);
        $DIC->method('user')->willReturn($this->createMock(ilObjUser::class));

        $this->provider
            ->expects($this->once())
            ->method('getEndSessionUrl')
            ->willReturn('http://auth.example.com/logout');

        $this->ctrl
            ->expects($this->once())
            ->method('redirectToURL')
            ->with($this->callback(
                fn(string $url) => str_starts_with($url, 'http://auth.example.com/logout')
            ));

        $this->authenticator->logout($this->provider, self::TEST_ID_TOKEN);
    }

    /**
     * @return array{0: MockObject<HttpService>, 1: MockObject<ArrayBasedRequestWrapper>}
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function createHttpMocks(): array
    {
        $query_mock = $this->createMock(ArrayBasedRequestWrapper::class);
        $http_wrapper_mock = $this->createMock(WrapperFactory::class);
        $http_mock = $this->createMock(HttpService::class);

        $http_mock->method('wrapper')->willReturn($http_wrapper_mock);
        $http_wrapper_mock->method('query')->willReturn($query_mock);

        return [$http_mock, $query_mock];
    }
}
