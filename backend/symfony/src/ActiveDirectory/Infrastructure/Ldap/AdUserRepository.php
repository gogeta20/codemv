<?php

namespace App\ActiveDirectory\Infrastructure\Ldap;

use App\ActiveDirectory\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Ldap\LdapConnectionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class AdUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly LdapConnectionService $ldap,
        private readonly LoggerInterface $logger,
    ) {}

    public function findBySamAccountName(string $samAccountName): ?array
    {
        try {
            $escaped = ldap_escape($samAccountName, '', LDAP_ESCAPE_FILTER);

            $result = $this->ldap->search(
                $this->ldap->getBaseDn(),
                sprintf('(&(objectClass=user)(sAMAccountName=%s))', $escaped),
                $this->getAllAttributes(),
            );

            $entries = $this->ldap->getEntries($result);

            if (($entries['count'] ?? 0) === 0) {
                return null;
            }

            return $this->cleanLdapEntry($entries[0]);
        } catch (\Exception $e) {
            $this->logger->warning('[AD_USER] findBySamAccountName failed', [
                'samAccountName' => $samAccountName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function findByOrganization(string $organizationCode): array
    {
        $orgDn = sprintf('OU=%s,%s', $organizationCode, $this->ldap->getBaseDn());

        try {
            $result = $this->ldap->search(
                $orgDn,
                '(&(objectClass=user)(objectCategory=person))',
                $this->getUserAttributes(),
            );

            $entries = $this->ldap->getEntries($result);

            return $this->mapUserEntries($entries);
        } catch (\Exception $e) {
            $this->logger->warning('[AD_USER] findByOrganization failed', [
                'organizationCode' => $organizationCode,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function search(string $query): array
    {
        $escaped = ldap_escape($query, '', LDAP_ESCAPE_FILTER);

        try {
            $result = $this->ldap->search(
                $this->ldap->getBaseDn(),
                sprintf('(&(objectClass=user)(objectCategory=person)(|(cn=*%s*)(mail=*%s*)(sAMAccountName=*%s*)))', $escaped, $escaped, $escaped),
                $this->getUserAttributes(),
            );

            $entries = $this->ldap->getEntries($result);

            return $this->mapUserEntries($entries);
        } catch (\Exception $e) {
            $this->logger->warning('[AD_USER] search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // --- Private helpers ---

    private function getUserAttributes(): array
    {
        return [
            'cn', 'samaccountname', 'distinguishedname', 'mail',
            'userprincipalname', 'displayname', 'givenname', 'sn',
            'useraccountcontrol', 'whencreated', 'lastlogon',
            'memberof', 'objectguid',
        ];
    }

    private function getAllAttributes(): array
    {
        return ['*', '+'];
    }

    private function mapUserEntries(array $entries): array
    {
        $users = [];
        $count = $entries['count'] ?? 0;

        for ($i = 0; $i < $count; $i++) {
            $e = $entries[$i];
            $uac = isset($e['useraccountcontrol'][0]) ? (int) $e['useraccountcontrol'][0] : 0;

            $users[] = [
                'uuid' => isset($e['objectguid'][0]) ? $this->convertGuidToUuid($e['objectguid'][0]) : null,
                'cn' => $e['cn'][0] ?? null,
                'samaccountname' => $e['samaccountname'][0] ?? null,
                'distinguishedname' => $e['distinguishedname'][0] ?? null,
                'mail' => $e['mail'][0] ?? null,
                'userprincipalname' => $e['userprincipalname'][0] ?? null,
                'displayname' => $e['displayname'][0] ?? null,
                'givenname' => $e['givenname'][0] ?? null,
                'sn' => $e['sn'][0] ?? null,
                'enabled' => !($uac & 0x0002),
                'groups' => $this->extractGroups($e),
                'whencreated' => $e['whencreated'][0] ?? null,
                'lastlogon' => $e['lastlogon'][0] ?? null,
            ];
        }

        return $users;
    }

    private function extractGroups(array $entry): array
    {
        if (!isset($entry['memberof'])) {
            return [];
        }

        $groups = $entry['memberof'];
        unset($groups['count']);

        return array_values(array_map(function (string $dn): string {
            if (preg_match('/CN=([^,]+)/', $dn, $matches)) {
                return $matches[1];
            }
            return $dn;
        }, $groups));
    }

    private function cleanLdapEntry(array $entry): array
    {
        $cleaned = [];
        $binaryAttributes = [
            'objectguid', 'objectsid', 'ms-ds-consistencyguid', 'usercertificate',
            'ntsecuritydescriptor', 'logonhours', 'thumbnailphoto', 'userparameters',
        ];

        foreach ($entry as $key => $value) {
            if (is_int($key) || $key === 'count') {
                continue;
            }

            if (is_array($value) && isset($value['count'])) {
                $cleanedValue = [];
                for ($i = 0; $i < $value['count']; $i++) {
                    $cleanedValue[] = $this->handleBinaryAttribute($key, $value[$i], $binaryAttributes);
                }
                $cleaned[$key] = count($cleanedValue) === 1 ? $cleanedValue[0] : $cleanedValue;
            } else {
                $cleaned[$key] = $this->handleBinaryAttribute($key, $value, $binaryAttributes);
            }
        }

        // Add UUID from objectGUID
        if (isset($cleaned['objectguid'])) {
            try {
                $binaryGuid = base64_decode($cleaned['objectguid']);
                $cleaned['uuid'] = Uuid::fromBinary($binaryGuid)->toRfc4122();
            } catch (\Exception) {
            }
        }

        return $cleaned;
    }

    private function handleBinaryAttribute(string $name, mixed $value, array $binaryAttributes): mixed
    {
        if (in_array(strtolower($name), $binaryAttributes, true) && is_string($value)) {
            return base64_encode($value);
        }

        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            return base64_encode($value);
        }

        return $value;
    }

    private function convertGuidToUuid(string $guid): ?string
    {
        try {
            return Uuid::fromBinary($guid)->toRfc4122();
        } catch (\Exception) {
            return null;
        }
    }
}
