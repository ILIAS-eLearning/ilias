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

use ILIAS\Cache\Config;
use ILIAS\Cache\Services as GlobalCache;
use ILIAS\OpenIdConnect\Authentication\Authenticator;
use ILIAS\OpenIdConnect\Authentication\OpenIdConnectProvider;
use ILIAS\Refinery\Factory as Refinery;

class ilAuthProviderOpenIdConnect extends ilAuthProvider
{
    private const OIDC_AUTH_IDTOKEN = 'oidc_auth_idtoken';

    private readonly ilOpenIdConnectSettings $settings;
    /** @var array $body */
    private readonly ilLogger $logger;
    private readonly ilLanguage $lng;
    private readonly Refinery $refinery;
    private readonly GlobalCache $cache;
    private Authenticator $authenticator;

    public function __construct(ilAuthCredentials $credentials)
    {
        global $DIC;
        parent::__construct($credentials);

        $this->logger = $DIC->logger()->auth();
        $this->settings = ilOpenIdConnectSettings::getInstance();
        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule('auth');
        $this->refinery = $DIC->refinery();
        $this->cache = $DIC->globalCache();

        $this->authenticator = new Authenticator(
            $DIC->refinery(),
            $DIC->http(),
            $DIC->ctrl()
        );
    }

    public function handleLogout(): void
    {
        if ($this->settings->getLogoutScope() === ilOpenIdConnectSettings::LOGOUT_SCOPE_LOCAL) {
            return;
        }

        $id_token = ilSession::get(self::OIDC_AUTH_IDTOKEN);
        $this->logger->debug('Logging out with token: ' . $id_token);

        if (isset($id_token) && $id_token !== '') {
            ilSession::set(self::OIDC_AUTH_IDTOKEN, '');
            $provider = $this->initClient();

            try {
                $this->authenticator->logout(
                    $provider,
                    $id_token
                );
            } catch (ilException $e) {
                $this->logger->warning('Logging out of OIDC provider failed with: ' . $e->getMessage());
            }
        }
    }

    public function doAuthentication(ilAuthStatus $status): bool
    {
        try {
            $auth_params = [];
            if ($this->settings->getLoginPromptType() === ilOpenIdConnectSettings::LOGIN_ENFORCE) {
                $auth_params['prompt'] = 'login';
            }

            $provider = $this->initClient();
            $access_token = $this->authenticator->authenticate($provider, $auth_params);
            // user is authenticated, otherwise redirected to authorization endpoint or exception

            $claims = $provider->getIdTokenPayload($access_token)['body'];
            $this->logger->dump($claims, ilLogLevel::DEBUG);
            $status = $this->handleUpdate($status, $claims);

            // @todo : provide a general solution for all authentication methods
            //$_GET['target'] = $this->getCredentials()->getRedirectionTarget();// TODO PHP8-REVIEW Please eliminate this. Mutating the request is not allowed and will not work in ILIAS 8.

            if ($this->settings->getLogoutScope() === ilOpenIdConnectSettings::LOGOUT_SCOPE_GLOBAL) {
                ilSession::set(self::OIDC_AUTH_IDTOKEN, $provider->getIdToken($access_token));
            }
            return true;
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
            $this->logger->warning((string) $e->getCode());
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setTranslatedReason($this->lng->txt('auth_oidc_failed'));
            return false;
        }
    }

    private function handleUpdate(ilAuthStatus $status, array $user_info): ilAuthStatus
    {
        // Transform assoc array to stdClass to be compatible with existing handleUpdate implementation.
        $user_info = (object) $user_info;

        if (!is_object($user_info)) {
            $this->logger->error('Received invalid user credentials: ');
            $this->logger->dump($user_info, ilLogLevel::ERROR);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason('err_wrong_login');
            return $status;
        }

        $uid_field = $this->settings->getUidField();
        $ext_account = $user_info->{$uid_field} ?? '';

        if (!is_string($ext_account) || $ext_account === '') {
            $this->logger->error('Could not determine valid external account, value is empty or not a string.');
            $this->logger->dump($user_info, ilLogLevel::ERROR);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason('err_wrong_login');
            return $status;
        }

        $this->logger->debug('Authenticated external account: ' . $ext_account);

        $int_account = ilObjUser::_checkExternalAuthAccount(
            ilOpenIdConnectUserSync::AUTH_MODE,
            $ext_account
        );

        try {
            $sync = new ilOpenIdConnectUserSync($this->settings, $user_info);
            $sync->setExternalAccount($ext_account);
            $sync->setInternalAccount((string) $int_account);
            $sync->updateUser();

            $user_id = $sync->getUserId();
            ilSession::set('used_external_auth_mode', ilAuthUtils::AUTH_OPENID_CONNECT);
            $status->setAuthenticatedUserId($user_id);
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
            //$_GET['target'] = $this->getCredentials()->getRedirectionTarget();// TODO PHP8-REVIEW Please eliminate this. Mutating the request is not allowed and will not work in ILIAS 8.
        } catch (ilOpenIdConnectSyncForbiddenException) {
            $status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
            $status->setReason('err_wrong_login');
        }

        return $status;
    }

    private function initClient(): OpenIdConnectProvider
    {
        $options = [
            'clientId' => $this->settings->getClientId(),
            'clientSecret' => $this->settings->getSecret(),
            'redirectUri' => ILIAS_HTTP_PATH . '/openidconnect.php',
            'url_provider' => $this->settings->getProvider(),
            'scopes' => $this->settings->getAllScopes(),
            'scope_separator' => $this->settings->getScopeSeparator()
        ];

        $proxy = ilProxySettings::_getInstance();
        if ($proxy->isActive()) {
            $options['proxy'] = $proxy->getHost() . ':' . $proxy->getPort();
            $options['verify'] = false;
        }

        return (new OpenIdConnectProvider($this->refinery, $options))
            ->withCache($this->cache);
    }
}
