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

/**
 * Class ilChatroomServerConnector
 * @author  Jan Posselt <jposselt@databay.de>
 * @version $Id$
 * @ingroup ModulesChatroom
 */
class ilChatroomServerConnector
{
    protected static ?bool $connection_status = null;

    public function __construct(private readonly ilChatroomServerSettings $settings)
    {
    }

    public static function checkServerConnection(bool $use_cache = true): bool
    {
        if ($use_cache && self::$connection_status !== null) {
            return self::$connection_status;
        }

        $connector = new self(ilChatroomAdmin::getDefaultConfiguration()->getServerSettings());
        self::$connection_status = $connector->isServerAlive();

        return self::$connection_status;
    }

    public function isServerAlive(): bool
    {
        $response = $this->file_get_contents(
            $this->settings->getURL('Heartbeat'),
            [
                'http' => [
                    'timeout' => 2
                ],
                'https' => [
                    'timeout' => 2
                ]
            ]
        );

        if (false === $response) {
            return false;
        }

        $responseObject = json_decode($response, false, 512, JSON_THROW_ON_ERROR);

        return $responseObject instanceof stdClass && ((int) $responseObject->status) === 200;
    }

    /**
     * Creates connect URL using given $scope and $userId and returns it.
     * @return string|false
     */
    public function connect(int $scope, int $userId)
    {
        return $this->file_get_contents(
            $this->settings->getURL('Connect', (string) $scope) . '/' . $userId
        );
    }

    /**
     * @param array|null $stream_context_params
     * @return string|false
     */
    protected function file_get_contents(string $url, ?array $stream_context_params = null)
    {
        $credentials = $this->settings->getAuthKey() . ':' . $this->settings->getAuthSecret();
        $header =
            "Connection: close\r\n" .
            "Content-Type: application/json; charset=utf-8\r\n" .
            "Authorization: Basic " . base64_encode($credentials);

        $ctx = [
            'http' => [
                'method' => 'GET',
                'header' => $header
            ],
            'https' => [
                'method' => 'GET',
                'header' => $header
            ]
        ];

        if (is_array($stream_context_params)) {
            $ctx = array_merge_recursive($ctx, $stream_context_params);
        }

        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, $severity, $severity, $file, $line);
        });

        try {
            return file_get_contents($url, false, stream_context_create($ctx));
        } catch (Exception $e) {
            ilLoggerFactory::getLogger('chatroom')->alert($e->getMessage());
        } finally {
            restore_error_handler();
        }

        return false;
    }

    /**
     * @return string|false
     */
    public function sendCreatePrivateRoom(int $scope, int $user, string $title)
    {
        return $this->file_get_contents(
            $this->settings->getURL('CreatePrivateRoom', (string) $scope) .
            '/' . $user . '/' . rawurlencode($title)
        );
    }

    /**
     * @return string|false
     * @deprecated Please use sendEnterPrivateRoom instead
     */
    public function enterPrivateRoom(int $scope, int $user)
    {
        return $this->sendEnterPrivateRoom($scope, $user);
    }

    /**
     * @return string|false
     */
    public function sendEnterPrivateRoom(int $scope, int $user)
    {
        return $this->file_get_contents(
            $this->settings->getURL('EnterPrivateRoom', (string) $scope) . '/' . $user
        );
    }

    /**
     * @return string|false
     */
    public function sendClearMessages(int $scope, int $user)
    {
        return $this->file_get_contents(
            $this->settings->getURL('ClearMessages', (string) $scope) . '/' . $user
        );
    }

    /**
     * @return string|false
     */
    public function leavePrivateRoom(int $scope, int $user)
    {
        return $this->sendLeavePrivateRoom($scope, $user);
    }

    /**
     * @return string|false
     */
    public function sendLeavePrivateRoom(int $scope, int $user)
    {
        return $this->file_get_contents(
            $this->settings->getURL('LeavePrivateRoom', (string) $scope) . '/' . $user
        );
    }

    /**
     * @return string|false
     */
    public function sendKick(int $scope, int $user)
    {
        return $this->kick($scope, $user);
    }

    /**
     * Returns kick URL
     * Creates kick URL using given $scope and $query and returns it.
     * @return string|false
     */
    public function kick(int $scope, int $user)
    {
        return $this->file_get_contents(
            $this->settings->getURL('Kick', (string) $scope) . '/' . $user
        );
    }

    /**
     * @return string|false
     */
    public function sendBan(int $scope, int $user)
    {
        return $this->file_get_contents(
            $this->settings->getURL('Ban', (string) $scope) . '/' . $user
        );
    }

    public function getSettings(): ilChatroomServerSettings
    {
        return $this->settings;
    }

    /**
     * @return string|false
     */
    public function sendInviteToPrivateRoom(int $scope, int $user, int $invited_id)
    {
        return $this->file_get_contents(
            $this->settings->getURL('InvitePrivateRoom', (string) $scope) .
            '/' . $user . '/' . $invited_id
        );
    }

    /**
     * @return string|false
     */
    public function sendUserConfigChange(string $message)
    {
        $query = http_build_query(['message' => $message]);

        return $this->file_get_contents(
            $this->settings->getURL('UserConfigChange', null) . '?' . $query
        );
    }
}
