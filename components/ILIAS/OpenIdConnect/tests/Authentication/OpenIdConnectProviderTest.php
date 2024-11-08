<?php

namespace ILIAS\OpenIdConnect\Tests\Authentication;

use GuzzleHttp\ClientInterface;
use ILIAS\Cache\Container\Container;
use ILIAS\Cache\Services as CacheService;
use ILIAS\OpenIdConnect\Authentication\OpenIdConnectProvider;
use ILIAS\OpenIdConnect\Authentication\WellKnownEndpoints;
use ILIAS\OpenIdConnect\Exceptions\AuthenticationException;
use ILIAS\OpenIdConnect\Exceptions\OpenIdConnectException;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\Refinery\To\Group;
use ILIAS\Refinery\To\Transformation\StringTransformation;
use ILIAS\Refinery\Transformation;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function base64_encode;
use function json_encode;

class OpenIdConnectProviderTest extends TestCase
{
    private const TEST_URL_PROVIDER = 'http://auth.example.com';
    /**
     * @var MockObject<CacheService>
     */
    protected MockObject $cache;
    /**
     * @var MockObject<ClientInterface>
     */
    private MockObject $http_client;

    protected OpenIdConnectProvider $provider;

    protected function setUp(): void
    {
        $refinery = $this->createMock(Refinery::class);

        $this->http_client = $this->createMock(ClientInterface::class);
        $this->cache = $this->mockCache();
        $this->provider = new OpenIdConnectProvider(
            $refinery,
            [
                'url_provider' => self::TEST_URL_PROVIDER,
                'scopes' => ['openid', 'email']
            ],
            [
                'httpClient' => $this->http_client
            ]
        );
    }

    public function testWithCache()
    {
        $providerWithCache = $this->provider->withCache($this->cache);

        $this->assertInstanceOf(OpenIdConnectProvider::class, $providerWithCache);
        $this->assertNotSame($providerWithCache, $this->provider);
    }

    public function testLoadUrlWithInvalidStatusCode()
    {
        $this->expectException(OpenIdConnectException::class);
        $this->expectExceptionMessage('Cannot access OpenID configuration resource.');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(400);

        $this->http_client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo(self::TEST_URL_PROVIDER . '/.well-known/openid-configuration')
            )->willReturn($response);

        $this->provider->getBaseAuthorizationUrl();
    }

    public function testLoadUrlWithInvalidResponse()
    {
        $this->expectException(OpenIdConnectException::class);
        $this->expectExceptionMessage('Cannot read OpenID configuration content.');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);

        $response
            ->expects($this->once())
            ->method('getBody')
            ->willThrowException(new \RuntimeException("Connection failed"));

        $this->http_client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo(self::TEST_URL_PROVIDER . '/.well-known/openid-configuration')
            )->willReturn($response);

        $this->provider->getBaseAuthorizationUrl();
    }


    public function testLoadUrlWithInvalidJson()
    {
        $this->expectException(OpenIdConnectException::class);
        $this->expectExceptionMessage('Cannot decode OpenID configuration file.');

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn("");

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $this->http_client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo(self::TEST_URL_PROVIDER . '/.well-known/openid-configuration')
            )->willReturn($response);

        $this->provider->getBaseAuthorizationUrl();
    }

    /**
     * @dataProvider provideEnableCache
     */
    public function testGetUrls(bool $enable_cache): void
    {
        $provider = $this->provider;
        if ($enable_cache) {
            $provider = $provider->withCache($this->cache);
        }

        $dummy_urls = $this->getDummyUrls();
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())
            ->method('getContents')
            ->willReturn(json_encode($dummy_urls));

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response
            ->expects($this->once())
            ->method('getBody')
            ->willReturn($stream);

        $this->http_client
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo(self::TEST_URL_PROVIDER . '/.well-known/openid-configuration')
            )->willReturn($response);

        $authorization_url = $provider->getBaseAuthorizationUrl();
        $access_token_url = $provider->getBaseAccessTokenUrl([]);
        $resource_owner_url = $provider->getResourceOwnerDetailsUrl($this->createMock(AccessToken::class));
        $end_session_url = $provider->getEndSessionUrl();

        $this->assertContains($authorization_url, $dummy_urls);
        $this->assertContains($access_token_url, $dummy_urls);
        $this->assertContains($resource_owner_url, $dummy_urls);
        $this->assertContains($end_session_url, $dummy_urls);
    }

    public function testIdTokenMissing(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Authenticated without openid scope');

        $access_token = $this->createMock(AccessToken::class);
        $access_token
            ->expects($this->once())
            ->method('getValues')
            ->willReturn([]);

        $this->provider->getIdToken($access_token);
    }


    public function testIdToken(): void
    {
        $id_token = $this->getDummyIdToken();
        $access_token = $this->createMock(AccessToken::class);
        $access_token
            ->expects($this->once())
            ->method('getValues')
            ->willReturn([
                'id_token' => $id_token
            ]);

        $this->assertEquals(
            $id_token,
            $this->provider->getIdToken($access_token)
        );
    }

    public function testIdTokenPayload(): void
    {
        $id_token = $this->getDummyIdToken();
        $access_token = $this->createMock(AccessToken::class);
        $access_token
            ->expects($this->once())
            ->method('getValues')
            ->willReturn([
                'id_token' => $id_token
            ]);

        $this->assertEquals(
            [
                'header' => ['head' => 'value'],
                'body' => ['email' => 'user@example.com']
            ],
            $this->provider->getIdTokenPayload($access_token)
        );
    }

    public function provideEnableCache(): array
    {
        return [
            'without cache' => [false],
            'with cache' => [true]
        ];
    }

    private function getDummyUrls(): array
    {
        return [
            WellKnownEndpoints::AUTHORIZATION->value => self::TEST_URL_PROVIDER . '/auth',
            WellKnownEndpoints::TOKEN->value => self::TEST_URL_PROVIDER . '/token',
            WellKnownEndpoints::USERINFO->value => self::TEST_URL_PROVIDER . '/userinfo',
            WellKnownEndpoints::END_SESSION->value => self::TEST_URL_PROVIDER . '/endsession'
        ];
    }

    private function getDummyIdToken(): string
    {
        return sprintf(
            '%s.%s',
            base64_encode(json_encode(['head' => 'value'])),
            base64_encode(json_encode(['email' => 'user@example.com']))
        );
    }

    private function mockCache(): CacheService
    {
        $fake_cache = [];

        $cacheContainer = $this->createMock(Container::class);
        $cacheContainer
            ->method('has')
            ->willReturnCallback(function (string $key) use (&$fake_cache) {
                return isset($fake_cache[$key]);
            });
        $cacheContainer
            ->method('get')
            ->willReturnCallback(function (string $key) use (&$fake_cache) {
                return $fake_cache[$key];
            });
        $cacheContainer
            ->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$fake_cache) {
                $fake_cache[$key] = $value;
            });

        $cache = $this->createMock(CacheService::class);
        $cache
            ->method('get')
            ->willReturn($cacheContainer);

        return $cache;
    }
}
