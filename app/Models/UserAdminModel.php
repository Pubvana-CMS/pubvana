<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAdminModel extends Model
{
    protected $table      = 'users';
    protected $returnType = 'object';

    // ─── TOTP (custom columns Shield doesn't know about) ─

    public function isTotpEnabled(int $userId): bool
    {
        $row = $this->select('totp_enabled')->find($userId);
        return $row ? (bool) $row->totp_enabled : false;
    }

    public function getTotpInfo(int $userId): ?object
    {
        return $this->select('totp_secret, totp_enabled')->find($userId);
    }

    public function enableTotp(int $userId, string $secret): void
    {
        $this->update($userId, ['totp_secret' => $secret, 'totp_enabled' => 1]);
    }

    public function disableTotp(int $userId): void
    {
        $this->update($userId, ['totp_secret' => null, 'totp_enabled' => 0]);
    }

    // ─── Ownership check ────────────────────────────

    public function getOwnerId(): int
    {
        return (int) $this->selectMin('id')->first()->id;
    }

    // ─── Username uniqueness ─────────────────────────

    public function uniqueUsername(string $base): string
    {
        $name = $base;
        $i    = 2;
        while ($this->where('username', $name)->countAllResults() > 0) {
            $name = $base . $i++;
        }
        return $name;
    }
}
