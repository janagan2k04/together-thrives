<?php
namespace Services;

use Core\Database;
use Core\Logger;
use Models\BloodBank;
use Models\GramaNiladhari;
use Models\Notification;
use Models\Organization;
use Models\User;

/**
 * Administrator decisions on accounts that registered but are not yet allowed in.
 *
 * Every decision writes two things as one unit: the new account status, and a
 * notification telling the applicant what happened. If the notification insert
 * fails we do not want a silently approved account, so both go in a transaction.
 *
 * Organizations, Grama Niladharis and blood banks also carry `verified_at` /
 * `verified_by` on their detail row - that is the audit trail of who let them in.
 */
class VerificationService
{
    private User $users;
    private Notification $notifications;

    public function __construct()
    {
        $this->users         = new User();
        $this->notifications = new Notification();
    }

    /** Let the account in. Returns false if it was not actually pending. */
    public function approve(int $userId, int $adminId): bool
    {
        $user = $this->users->find($userId);
        if (!$user || $user['status'] !== STATUS_PENDING) {
            return false;
        }

        Database::transaction(function () use ($user, $userId, $adminId) {
            $this->users->update($userId, ['status' => STATUS_ACTIVE]);
            $this->stampDetailRow($user['role'], $userId, $adminId);

            $this->notifications->create([
                'user_id'  => $userId,
                'title'    => 'Your account has been approved',
                'body'     => 'Welcome to TogetherThrives. You can now sign in and start using the platform.',
                'category' => Notification::CATEGORY_VERIFICATION,
                'link'     => '/login',
            ]);
        });

        Logger::audit("Admin {$adminId} approved {$user['role']} account {$userId} ({$user['email']})");
        return true;
    }

    /**
     * Turn the application down. The account is kept - the applicant can be told
     * why, and the row stays as evidence - but it can never sign in.
     */
    public function reject(int $userId, int $adminId, string $reason = ''): bool
    {
        $user = $this->users->find($userId);
        if (!$user || $user['status'] !== STATUS_PENDING) {
            return false;
        }

        Database::transaction(function () use ($userId, $reason) {
            $this->users->update($userId, ['status' => STATUS_REJECTED]);

            $body = 'We could not verify the details you submitted, so your account was not approved.';
            if ($reason !== '') {
                $body .= ' Reason: ' . $reason;
            }

            $this->notifications->create([
                'user_id'  => $userId,
                'title'    => 'Your registration was not approved',
                'body'     => mb_substr($body, 0, 500),
                'category' => Notification::CATEGORY_VERIFICATION,
                'link'     => '/support',
            ]);
        });

        Logger::audit("Admin {$adminId} rejected {$user['role']} account {$userId} ({$user['email']})");
        return true;
    }

    /** Block an account that is already active. */
    public function suspend(int $userId, int $adminId, string $reason = ''): bool
    {
        $user = $this->users->find($userId);
        if (!$user || $user['status'] !== STATUS_ACTIVE || $user['role'] === ROLE_ADMIN) {
            return false;
        }

        Database::transaction(function () use ($userId, $reason) {
            $this->users->update($userId, ['status' => STATUS_SUSPENDED]);

            $body = 'Your account has been suspended by an administrator.';
            if ($reason !== '') {
                $body .= ' Reason: ' . $reason;
            }

            $this->notifications->create([
                'user_id'  => $userId,
                'title'    => 'Your account has been suspended',
                'body'     => mb_substr($body, 0, 500),
                'category' => Notification::CATEGORY_SYSTEM,
                'link'     => '/support',
            ]);
        });

        Logger::audit("Admin {$adminId} suspended account {$userId} ({$user['email']})");
        return true;
    }

    /** Undo a suspension or a rejection. */
    public function reinstate(int $userId, int $adminId): bool
    {
        $user = $this->users->find($userId);
        if (!$user || !in_array($user['status'], [STATUS_SUSPENDED, STATUS_REJECTED], true)) {
            return false;
        }

        Database::transaction(function () use ($userId) {
            $this->users->update($userId, ['status' => STATUS_ACTIVE]);

            $this->notifications->create([
                'user_id'  => $userId,
                'title'    => 'Your account has been reinstated',
                'body'     => 'You can sign in again and continue using the platform.',
                'category' => Notification::CATEGORY_SYSTEM,
                'link'     => '/login',
            ]);
        });

        Logger::audit("Admin {$adminId} reinstated account {$userId} ({$user['email']})");
        return true;
    }

    /**
     * Record who approved the detail row. Community members have no detail row,
     * and `blood_banks` has no `verified_by` column - only `verified_at`.
     */
    private function stampDetailRow(string $role, int $userId, int $adminId): void
    {
        $now = date('Y-m-d H:i:s');

        match ($role) {
            ROLE_ORGANIZATION => $this->stamp(
                new Organization(), $userId, ['verified_at' => $now, 'verified_by' => $adminId]
            ),
            ROLE_GN => $this->stamp(
                new GramaNiladhari(), $userId, ['verified_at' => $now, 'verified_by' => $adminId]
            ),
            ROLE_BLOOD_BANK => $this->stamp(
                new BloodBank(), $userId, ['verified_at' => $now]
            ),
            default => null,
        };
    }

    private function stamp(object $model, int $userId, array $data): void
    {
        $row = $model->findBy('user_id', $userId);
        if ($row) {
            $model->update((int) $row['id'], $data);
        }
    }
}
