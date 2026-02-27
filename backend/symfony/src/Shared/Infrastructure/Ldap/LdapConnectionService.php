<?php

namespace App\Shared\Infrastructure\Ldap;

use Psr\Log\LoggerInterface;

/**
 * LDAP Connection Service — wrapper over native ldap_* functions.
 */
class LdapConnectionService
{
    /** @var resource|null */
    private $connection = null;
    private bool $bound = false;

    public function __construct(
        private readonly string $ldapHost,
        private readonly int $ldapPort,
        private readonly string $bindDn,
        #[\SensitiveParameter]
        private readonly string $bindPassword,
        private readonly string $baseDn,
        private readonly LoggerInterface $logger,
    ) {}

    public function getConnection()
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $connection = @ldap_connect($this->ldapHost, $this->ldapPort);

        if ($connection === false) {
            throw new \RuntimeException("Failed to connect to LDAP server {$this->ldapHost}:{$this->ldapPort}");
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        $this->connection = $connection;

        return $this->connection;
    }

    public function bind(): void
    {
        if ($this->bound) {
            return;
        }

        $connection = $this->getConnection();
        $result = @ldap_bind($connection, $this->bindDn, $this->bindPassword);

        if ($result === false) {
            $error = ldap_error($connection);
            throw new \RuntimeException("Failed to bind to LDAP: {$error}");
        }

        $this->bound = true;
    }

    public function search(string $dn, string $filter, array $attributes = [])
    {
        $this->bind();
        $result = @ldap_search($this->getConnection(), $dn, $filter, $attributes);

        if ($result === false) {
            $error = ldap_error($this->getConnection());
            throw new \RuntimeException("LDAP search failed: {$error}");
        }

        return $result;
    }

    public function list(string $dn, string $filter, array $attributes = [])
    {
        $this->bind();
        $result = @ldap_list($this->getConnection(), $dn, $filter, $attributes);

        if ($result === false) {
            $error = ldap_error($this->getConnection());
            throw new \RuntimeException("LDAP list failed: {$error}");
        }

        return $result;
    }

    public function getEntries($result): array
    {
        $entries = ldap_get_entries($this->getConnection(), $result);

        return $entries ?: [];
    }

    public function getBaseDn(): string
    {
        return $this->baseDn;
    }

    public function close(): void
    {
        if ($this->connection !== null) {
            @ldap_close($this->connection);
            $this->connection = null;
            $this->bound = false;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
