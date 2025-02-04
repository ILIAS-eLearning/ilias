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

use ILIAS\Setup;

class ilHttpSetupConfig implements Setup\Config
{
    protected string $http_path;
    protected bool $forced = false;
    protected bool $autodetection_enabled;
    protected ?string $header_name;
    protected ?string $header_value;
    protected bool $proxy_enabled;
    protected ?string $proxy_host;
    protected ?string $proxy_port;
    /** @var list<string>|null */
    protected ?array $allowed_hosts = null;

    /**
     * @param list<string>|null  $allowed_hosts
     */
    public function __construct(
        string $http_path,
        bool $autodetection_enabled,
        bool $forced,
        ?string $header_name,
        ?string $header_value,
        bool $proxy_enabled,
        ?string $proxy_host,
        ?string $proxy_port,
        ?array $allowed_hosts
    ) {
        if ($autodetection_enabled && (!$header_name || !$header_value)) {
            throw new \InvalidArgumentException(
                "Expected header name and value for https autodetection if that feature is enabled."
            );
        }
        if ($proxy_enabled && (!$proxy_host || !$proxy_port)) {
            throw new \InvalidArgumentException(
                "Expected setting for proxy host and port if proxy is enabled."
            );
        }
        $this->http_path = $http_path;
        $this->autodetection_enabled = $autodetection_enabled;
        $this->forced = $forced;
        $this->header_name = $header_name;
        $this->header_value = $header_value;
        $this->proxy_enabled = $proxy_enabled;
        $this->proxy_host = $proxy_host;
        $this->proxy_port = $proxy_port;

        if (is_array($allowed_hosts)) {
            $this->allowed_hosts = array_values(array_filter(array_map(strval(...), $allowed_hosts)));
        }
    }


    public function isForced(): bool
    {
        return $this->forced;
    }

    public function getHttpPath(): string
    {
        return $this->http_path;
    }

    public function isAutodetectionEnabled(): bool
    {
        return $this->autodetection_enabled;
    }

    public function getHeaderName(): ?string
    {
        return $this->header_name;
    }

    public function getHeaderValue(): ?string
    {
        return $this->header_value;
    }

    public function isProxyEnabled(): bool
    {
        return $this->proxy_enabled;
    }

    public function getProxyHost(): ?string
    {
        return $this->proxy_host;
    }

    public function getProxyPort(): ?string
    {
        return $this->proxy_port;
    }

    /**
     * @return list<string>|null
     */
    public function getAllowedHosts(): ?array
    {
        return $this->allowed_hosts;
    }
}
