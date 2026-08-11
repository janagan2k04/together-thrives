<?php
namespace Services;

use Core\Auth;
use Core\Database;
use Core\FileUploader;
use Core\Logger;
use Models\BloodBank;
use Models\GramaNiladhari;
use Models\Organization;
use Models\User;

/**
 * Account creation for all four registration types.
 *
 * Community members are active immediately. Organizations, Grama Niladharis and
 * blood banks land on STATUS_PENDING and stay locked out until an administrator
 * approves them — see the admin verification queue.
 *
 * Every role that writes two tables does so inside a transaction, so a failure
 * halfway through can never leave a users row without its detail row.
 */
class RegistrationService
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    /**
     * Community members need no verification — they can sign in straight away.
     * Their NIC scan is kept on file so an administrator can check it later.
     */
    public function registerCommunity(array $data, ?array $nicDocument): int
    {
        $path = $nicDocument
            ? FileUploader::store($nicDocument, 'nic-documents', ['pdf', 'jpg', 'png'], 5)
            : null;

        try {
            $id = $this->users->create([
                'full_name'         => $data['full_name'],
                'email'             => $data['email'],
                'phone'             => ($data['phone'] ?? '') ?: null,
                'nic_document_path' => $path,
                'password_hash'     => Auth::hash($data['password']),
                'role'              => ROLE_COMMUNITY,
                'status'            => STATUS_ACTIVE,
                'city_id'           => ($data['city_id'] ?? '') ?: null,
            ]);
        } catch (\Throwable $e) {
            FileUploader::delete($path);
            throw $e;
        }

        Logger::audit("Community account {$id} registered ({$data['email']})");
        return $id;
    }

    public function registerOrganization(array $data, ?array $document): int
    {
        return $this->registerPending(
            $data,
            ROLE_ORGANIZATION,
            $data['org_name'],
            $document,
            'org-documents',
            fn(int $userId, ?string $path) => (new Organization())->create([
                'user_id'       => $userId,
                'org_name'      => $data['org_name'],
                'reg_number'    => $data['reg_number'],
                'org_type'      => $data['org_type'],
                'website'       => ($data['website'] ?? '') ?: null,
                'description'   => $data['description'],
                'city_id'       => ($data['city_id'] ?? '') ?: null,
                'address'       => ($data['address'] ?? '') ?: null,
                'document_path' => $path,
            ])
        );
    }

    public function registerGramaNiladhari(array $data, ?array $document): int
    {
        return $this->registerPending(
            $data,
            ROLE_GN,
            $data['full_name'],
            $document,
            'gn-documents',
            fn(int $userId, ?string $path) => (new GramaNiladhari())->create([
                'user_id'       => $userId,
                'gn_division'   => $data['gn_division'],
                // Not collected at sign-up. The column is NOT NULL in the frozen
                // schema, so it is stored empty and the administrator fills it in
                // when verifying the officer.
                'division_code' => '',
                'ds_division'   => ($data['ds_division'] ?? '') ?: null,
                'district_id'   => $data['district_id'],
                'office_phone'  => ($data['phone'] ?? '') ?: null,
                'document_path' => $path,
            ])
        );
    }

    public function registerBloodBank(array $data, ?array $document): int
    {
        return $this->registerPending(
            $data,
            ROLE_BLOOD_BANK,
            $data['bank_name'],
            $document,
            'blood-bank-documents',
            fn(int $userId, ?string $path) => (new BloodBank())->create([
                'user_id'       => $userId,
                'bank_name'     => $data['bank_name'],
                'license_no'    => $data['license_no'],
                'hospital_name' => ($data['hospital_name'] ?? '') ?: null,
                'address'       => ($data['address'] ?? '') ?: null,
                'city_id'       => ($data['city_id'] ?? '') ?: null,
                'hotline'       => ($data['phone'] ?? '') ?: null,
                'document_path' => $path,
            ])
        );
    }

    /**
     * Shared path for the three roles that need admin approval: store the proof
     * document, then write the users row and the detail row as one unit.
     *
     * @param callable $detail  fn(int $userId, ?string $documentPath): int
     */
    private function registerPending(
        array $data,
        string $role,
        string $displayName,
        ?array $document,
        string $folder,
        callable $detail
    ): int {
        // Stored before the transaction because moving a file cannot be rolled
        // back; if the insert fails we delete it again below.
        $path = $document ? FileUploader::store($document, $folder, ['pdf', 'jpg', 'png'], 5) : null;

        try {
            $userId = Database::transaction(function () use ($data, $role, $displayName, $detail, $path) {
                $userId = $this->users->create([
                    'full_name'     => $displayName,
                    'email'         => $data['email'],
                    'phone'         => ($data['phone'] ?? '') ?: null,
                    'nic'           => ($data['nic'] ?? '') ?: null,
                    'password_hash' => Auth::hash($data['password']),
                    'role'          => $role,
                    'status'        => STATUS_PENDING,
                    'city_id'       => ($data['city_id'] ?? '') ?: null,
                ]);

                $detail($userId, $path);
                return $userId;
            });
        } catch (\Throwable $e) {
            FileUploader::delete($path);
            throw $e;
        }

        Logger::audit("{$role} account {$userId} registered ({$data['email']}) — awaiting verification");
        return $userId;
    }
}
