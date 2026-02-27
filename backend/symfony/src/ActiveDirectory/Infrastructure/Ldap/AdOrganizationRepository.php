<?php

namespace App\ActiveDirectory\Infrastructure\Ldap;

use App\ActiveDirectory\Domain\Repository\OrganizationRepositoryInterface;
use App\ActiveDirectory\Domain\ValueObject\OrganizationCode;
use App\Shared\Infrastructure\Ldap\LdapConnectionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class AdOrganizationRepository implements OrganizationRepositoryInterface
{
    public function __construct(
        private readonly LdapConnectionService $ldap,
        private readonly LoggerInterface $logger,
    ) {}

    public function findAll(): array
    {
        $result = $this->ldap->list(
            $this->ldap->getBaseDn(),
            '(&(objectClass=organizationalUnit))',
            $this->getListAttributes(),
        );

        $entries = $this->ldap->getEntries($result);

        return $this->mapListEntries($entries);
    }

    public function findByCodeWithFullData(OrganizationCode $code): ?array
    {
        $orgDn = sprintf('OU=%s,%s', $code->value(), $this->ldap->getBaseDn());

        try {
            $result = $this->ldap->search($orgDn, '(objectClass=organizationalUnit)', ['*', '+']);
            $entries = $this->ldap->getEntries($result);
        } catch (\Exception $e) {
            $this->logger->info('[AD] Organization not found', ['code' => $code->value(), 'error' => $e->getMessage()]);
            return null;
        }

        if (($entries['count'] ?? 0) === 0) {
            return null;
        }

        $orgData = $entries[0];

        $subOus = $this->getSubOus($orgDn);
        $users = $this->getUsers($orgDn);
        $groups = $this->getGroups($orgDn);
        $computers = $this->getComputers($orgDn);

        $uuid = isset($orgData['objectguid'][0])
            ? $this->convertGuidToUuid($orgData['objectguid'][0])
            : null;

        return [
            'uuid' => $uuid,
            'distinguishedname' => $orgData['distinguishedname'][0] ?? null,
            'ou' => $orgData['ou'][0] ?? null,
            'name' => $orgData['name'][0] ?? null,
            'description' => $orgData['description'][0] ?? null,
            'whencreated' => $orgData['whencreated'][0] ?? null,
            'whenchanged' => $orgData['whenchanged'][0] ?? null,
            'sub_ous' => $subOus,
            'sub_ous_count' => count($subOus),
            'users' => $users,
            'users_count' => count($users),
            'groups' => $groups,
            'groups_count' => count($groups),
            'computers' => $computers,
            'computers_count' => count($computers),
        ];
    }

    public function search(string $query): array
    {
        $escaped = ldap_escape($query, '', LDAP_ESCAPE_FILTER);

        $result = $this->ldap->search(
            $this->ldap->getBaseDn(),
            sprintf('(&(objectClass=organizationalUnit)(|(ou=*%s*)(description=*%s*)))', $escaped, $escaped),
            $this->getListAttributes(),
        );

        $entries = $this->ldap->getEntries($result);

        return $this->mapListEntries($entries);
    }

    // --- Private helpers ---

    private function getListAttributes(): array
    {
        return ['ou', 'name', 'description', 'distinguishedname', 'whencreated', 'whenchanged'];
    }

    private function mapListEntries(array $entries): array
    {
        $mapped = [];
        $count = $entries['count'] ?? 0;

        for ($i = 0; $i < $count; $i++) {
            $mapped[] = [
                'code' => $entries[$i]['ou'][0] ?? null,
                'name' => $entries[$i]['description'][0] ?? $entries[$i]['name'][0] ?? null,
                'distinguishedname' => $entries[$i]['distinguishedname'][0] ?? null,
                'description' => $entries[$i]['description'][0] ?? null,
                'whencreated' => $entries[$i]['whencreated'][0] ?? null,
                'whenchanged' => $entries[$i]['whenchanged'][0] ?? null,
            ];
        }

        return $mapped;
    }

    private function getSubOus(string $orgDn): array
    {
        try {
            $result = $this->ldap->search($orgDn, '(objectClass=organizationalUnit)', ['ou', 'distinguishedname', 'name', 'whencreated', 'description']);
            $entries = $this->ldap->getEntries($result);
            $subOus = [];

            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                if (($entries[$i]['distinguishedname'][0] ?? '') === $orgDn) {
                    continue;
                }
                $subOus[] = [
                    'ou' => $entries[$i]['ou'][0] ?? null,
                    'distinguishedname' => $entries[$i]['distinguishedname'][0] ?? null,
                    'name' => $entries[$i]['name'][0] ?? null,
                    'whencreated' => $entries[$i]['whencreated'][0] ?? null,
                    'description' => $entries[$i]['description'][0] ?? null,
                ];
            }

            return $subOus;
        } catch (\Exception) {
            return [];
        }
    }

    private function getUsers(string $orgDn): array
    {
        try {
            $result = $this->ldap->search($orgDn, '(&(objectClass=user)(objectCategory=person))', [
                'cn', 'samaccountname', 'distinguishedname', 'mail', 'userprincipalname',
                'displayname', 'givenname', 'sn', 'useraccountcontrol', 'whencreated',
                'lastlogon', 'objectguid',
            ]);
            $entries = $this->ldap->getEntries($result);
            $users = [];

            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
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
                    'whencreated' => $e['whencreated'][0] ?? null,
                    'lastlogon' => $e['lastlogon'][0] ?? null,
                ];
            }

            return $users;
        } catch (\Exception) {
            return [];
        }
    }

    private function getGroups(string $orgDn): array
    {
        try {
            $result = $this->ldap->search($orgDn, '(objectClass=group)', [
                'cn', 'distinguishedname', 'description', 'grouptype', 'member', 'whencreated', 'samaccountname',
            ]);
            $entries = $this->ldap->getEntries($result);
            $groups = [];

            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                $e = $entries[$i];
                $members = [];
                if (isset($e['member'])) {
                    unset($e['member']['count']);
                    $members = array_values($e['member']);
                }

                $groupType = isset($e['grouptype'][0]) ? (int) $e['grouptype'][0] : 0;

                $groups[] = [
                    'cn' => $e['cn'][0] ?? null,
                    'distinguishedname' => $e['distinguishedname'][0] ?? null,
                    'description' => $e['description'][0] ?? null,
                    'samaccountname' => $e['samaccountname'][0] ?? null,
                    'grouptype' => (string) $groupType,
                    'member' => $members,
                    'members_count' => count($members),
                    'whencreated' => $e['whencreated'][0] ?? null,
                ];
            }

            return $groups;
        } catch (\Exception) {
            return [];
        }
    }

    private function getComputers(string $orgDn): array
    {
        try {
            $result = $this->ldap->search($orgDn, '(objectClass=computer)', [
                'cn', 'distinguishedname', 'dnshostname', 'operatingsystem', 'operatingsystemversion', 'whencreated', 'lastlogon',
            ]);
            $entries = $this->ldap->getEntries($result);
            $computers = [];

            for ($i = 0; $i < ($entries['count'] ?? 0); $i++) {
                $e = $entries[$i];
                $computers[] = [
                    'cn' => $e['cn'][0] ?? null,
                    'distinguishedname' => $e['distinguishedname'][0] ?? null,
                    'dnshostname' => $e['dnshostname'][0] ?? null,
                    'operatingsystem' => $e['operatingsystem'][0] ?? null,
                    'operatingsystemversion' => $e['operatingsystemversion'][0] ?? null,
                    'whencreated' => $e['whencreated'][0] ?? null,
                    'lastlogon' => $e['lastlogon'][0] ?? null,
                ];
            }

            return $computers;
        } catch (\Exception) {
            return [];
        }
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
