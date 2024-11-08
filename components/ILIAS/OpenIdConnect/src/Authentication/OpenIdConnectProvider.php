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

namespace ILIAS\OpenIdConnect\Authentication;

use ILIAS\Cache\Config;
use ILIAS\Cache\Container\ActiveContainer;
use ILIAS\Cache\Container\BaseRequest;
use ILIAS\Cache\Container\Container;
use ILIAS\Cache\Container\VoidContainer;
use ILIAS\OpenIdConnect\Exceptions\AuthenticationException;
use ILIAS\OpenIdConnect\Exceptions\OpenIdConnectException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use ILIAS\Cache\Services as CacheFactory;
use ILIAS\Refinery\Factory as Refinery;

class OpenIdConnectProvider extends GenericProvider
{
    private array $config = [];
    private ?CacheFactory $cache = null;

    protected string $url_provider;
    protected string $scope_separator = ' ';

    public function __construct(
        private readonly Refinery $refinery,
        array $options = [],
        array $collaborators = []
    ) {
        $options['pkceMethod'] = self::PKCE_METHOD_S256;

        parent::__construct($options, $collaborators);
    }

    public function withCache(CacheFactory $cache): self
    {
        $clone = clone $this;
        $clone->cache = $cache;
        return $clone;
    }

    protected function getConfigurableOptions(): array
    {
        return array_merge(parent::getConfigurableOptions(), [
            'proxy',
            'timeout',
            'verify',
            'scope_separator'
        ]);
    }

    protected function getRequiredOptions(): array
    {
        return [
            'url_provider',
        ];
    }

    public function getBaseAuthorizationUrl(): string
    {
        return $this->getProviderUrl(WellKnownEndpoints::AUTHORIZATION);
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getProviderUrl(WellKnownEndpoints::TOKEN);
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->getProviderUrl(WellKnownEndpoints::USERINFO);
    }

    public function getEndSessionUrl(): string
    {
        return $this->getProviderUrl(WellKnownEndpoints::END_SESSION);
    }

    public function getIdToken(AccessToken $token): string
    {
        $values = $token->getValues();

        if (!isset($values['id_token'])) {
            throw new AuthenticationException('Authenticated without openid scope');
        }

        return $values['id_token'];
    }

    public function getIdTokenPayload(AccessToken $access_token): array
    {
        $id_token = $this->getIdToken($access_token);
        $parts = explode('.', $id_token);
        return [
            'header' => json_decode(base64_decode($parts[0] ?? ''), true),
            'body' => json_decode(base64_decode($parts[1] ?? ''), true)
        ];
    }

    private function getProviderUrl(WellKnownEndpoints $type): string
    {
        if (!$this->isCached($type) && !$this->isInMemory($type)) {
            $this->loadWellKnownConfig();
        }

        return $this->isCacheEnable()
            ? $this->readFromCache($type)
            : $this->config[$type->value];
    }

    private function loadWellKnownConfig(): void
    {
        $response = $this->getHttpClient()->request('GET', $this->url_provider . '/.well-known/openid-configuration');
        if (200 !== $response->getStatusCode()) {
            throw new OpenIdConnectException('Cannot access OpenID configuration resource.');
        }

        try {
            $content = $response->getBody()->getContents();
        } catch (\RuntimeException $e) {
            throw new OpenIdConnectException('Cannot read OpenID configuration content.');
        }

        if (null === $json = json_decode($content, true)) {
            throw new OpenIdConnectException('Cannot decode OpenID configuration file.');
        }

        $this->config = $json;

        if ($this->isCacheEnable()) {
            $this->writeToCache($json);
        }
    }

    private function isInMemory(WellKnownEndpoints $type): bool
    {
        return isset($this->config[$type->value]);
    }

    private function isCached(WellKnownEndpoints $type): bool
    {
        if (!$this->isCacheEnable()) {
            return false;
        }

        return $this->getCacheContainer()->has($type->value);
    }

    private function readFromCache(WellKnownEndpoints $type): ?string
    {
        return $this->getCacheContainer()
            ->get(
                $type->value,
                $this->refinery->to()->string()
            );
    }

    private function writeToCache(array $config): void
    {
        $cache_container = $this->getCacheContainer();

        foreach ($config as $key => $value) {
            $cache_container->set($key, $value);
        }
    }

    private function getCacheContainer(): Container
    {
        return $this->cache->get(new BaseRequest('auth_oidc_config', true));
    }

    protected function getScopeSeparator(): string
    {
        return $this->scope_separator;
    }


    private function isCacheEnable(): bool
    {
        return $this->cache && !$this->cache instanceof VoidContainer;
    }

}
