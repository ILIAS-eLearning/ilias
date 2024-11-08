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

use ILIAS\Data\Factory;
use IlIAS\HTTP\Services as HttpService;
use ILIAS\OpenIdConnect\Exceptions\AuthenticationException;
use ILIAS\Refinery\Factory as Refinery;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;

class Authenticator
{
    public const SESSION_KEY_STATE = 'oauth2state';
    public const SESSION_KEY_CODE = 'oauth2pkceCode';

    public function __construct(
        private readonly Refinery $refinery,
        private readonly HttpService $http_service,
        private readonly \ilCtrlInterface $ctrl
    ) {
    }

    public function authenticate(OpenIdConnectProvider $provider, array $auth_params = []): ?AccessTokenInterface
    {
        $request = $this->http_service->wrapper();

        if ($request->query()->has('error') || $request->query()->has('error_description')) {
            $error_description = $request->query()->retrieve(
                'error_description',
                $this->refinery->byTrying([
                    $this->refinery->kindlyTo()->string(),
                    $this->refinery->always(
                        $request->query()->retrieve(
                            'error',
                            $this->refinery->kindlyTo()->string()
                        )
                    )
                ])
            );

            throw new AuthenticationException($error_description);
        }

        $code = $request->query()->retrieve(
            'code',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always(null)
            ])
        );

        if (!$code) {
            $auth_url = $provider->getAuthorizationUrl($auth_params);

            \ilSession::set(self::SESSION_KEY_STATE, $provider->getState());
            \ilSession::set(self::SESSION_KEY_CODE, $provider->getPkceCode());

            $this->ctrl->redirectToURL($auth_url);
            return null;
        }

        $state = $request->query()->retrieve(
            'state',
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always(null)
            ])
        );

        if (!$state || !\ilSession::has(self::SESSION_KEY_STATE) || $state !== \ilSession::get(self::SESSION_KEY_STATE)) {
            \ilSession::clear(self::SESSION_KEY_STATE);
            \ilSession::clear(self::SESSION_KEY_CODE);

            throw new AuthenticationException('Invalid State');
        }

        try {
            $provider->setPkceCode(\ilSession::get(self::SESSION_KEY_CODE));

            return $provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);
        } catch (IdentityProviderException $e) {
            throw new AuthenticationException(
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    public function logout(OpenIdConnectProvider $provider, string $id_token): void
    {
        $end_session_url = (new Factory())->uri($provider->getEndSessionUrl());
        $end_session_url = $end_session_url
            ->withParameters([
                'id_token_hint' => $id_token,
                'post_logout_redirect_uri' => ILIAS_HTTP_PATH . '/' . \ilStartUpGUI::logoutUrl()
            ]);

        $this->ctrl->redirectToURL((string) $end_session_url);
    }
}
