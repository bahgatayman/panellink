<?php

namespace App\Services;

use App\Models\Owner;
use App\Models\SpeedProfile;

/**
 * Feature-aware boundary between the app and the MikroTik router.
 *
 * Every router interaction goes through here so the "build a client from the
 * owner's credentials" step lives in one place and (from Phase 3) the hotspot
 * feature gate is centralized. Method signatures deliberately do NOT expose
 * MikroTikService, so a second router vendor could later sit behind an
 * extracted interface without touching callers.
 *
 * NOTE (Phase 2): no gating yet — behavior is identical to the previous inline
 * `new MikroTikService(...)` calls. Gating is introduced in Phase 3.
 */
class HotspotSyncService
{
    private function client(Owner $owner): MikroTikService
    {
        return new MikroTikService(
            $owner->mikrotik_host,
            $owner->mikrotik_port,
            $owner->mikrotik_username,
            $owner->mikrotik_password,
        );
    }

    /** Provision a hotspot user on the router. Throws on router failure. */
    public function createUser(Owner $owner, string $phone, string $password, string $profileName): void
    {
        $client = $this->client($owner);
        try {
            $client->connect();
            $client->createHotspotUser($phone, $password, $profileName);
        } finally {
            $client->disconnect();
        }
    }

    /** Remove a hotspot user from the router. Throws on router failure. */
    public function deleteUser(Owner $owner, string $phone): void
    {
        $client = $this->client($owner);
        try {
            $client->connect();
            $client->deleteHotspotUser($phone);
        } finally {
            $client->disconnect();
        }
    }

    /** Re-point a single user at a profile on the router. Throws on router failure. */
    public function setUserSpeed(Owner $owner, string $phone, string $profileName): void
    {
        $client = $this->client($owner);
        try {
            $client->connect();
            $client->setUserSpeed($phone, $profileName);
        } finally {
            $client->disconnect();
        }
    }

    /** Create a hotspot profile on the router. Throws on router failure. */
    public function createProfile(Owner $owner, string $name, string $speedDownload, string $speedUpload): void
    {
        $client = $this->client($owner);
        try {
            $client->connect();
            $client->createHotspotProfile($name, $speedDownload, $speedUpload);
        } finally {
            $client->disconnect();
        }
    }

    /** Delete a hotspot profile from the router. Throws on router failure. */
    public function deleteProfile(Owner $owner, string $name): void
    {
        $client = $this->client($owner);
        try {
            $client->connect();
            $client->deleteHotspotProfile($name);
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Update a profile on the router and re-apply it to each assigned user in a
     * single connection. Best-effort per user: returns the list of "name: error"
     * strings for users that failed (empty array = all synced). Throws only if
     * the connect / profile-update step itself fails.
     *
     * @param  iterable<int, \App\Models\HotspotUser>  $assignedUsers
     * @return string[]  per-user sync errors
     */
    public function syncProfileToUsers(Owner $owner, SpeedProfile $profile, iterable $assignedUsers): array
    {
        $client = $this->client($owner);
        $errors = [];

        try {
            $client->connect();
            $client->updateHotspotProfile($profile->name, $profile->speed_download, $profile->speed_upload);

            foreach ($assignedUsers as $user) {
                try {
                    $client->setUserSpeed($user->phone, $profile->name);
                    $user->update([
                        'speed_download' => $profile->speed_download,
                        'speed_upload'   => $profile->speed_upload,
                    ]);
                } catch (\Exception $e) {
                    $errors[] = $user->name . ': ' . $e->getMessage();
                }
            }
        } finally {
            $client->disconnect();
        }

        return $errors;
    }

    /** Live active hotspot users from the router. Throws on router failure. */
    public function activeUsers(Owner $owner): array
    {
        $client = $this->client($owner);
        try {
            $client->connect();

            return $client->getActiveUsers();
        } finally {
            $client->disconnect();
        }
    }

    /** Verify the owner's router credentials connect. Throws on failure. */
    public function testConnection(Owner $owner): void
    {
        $client = $this->client($owner);
        try {
            $client->connect();
        } finally {
            $client->disconnect();
        }
    }
}
