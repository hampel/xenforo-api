<?php

declare(strict_types=1);

namespace Hampel\XenForo\Api\Resource;

use Hampel\XenForo\Api\Generated\Schema\User;

/**
 * Me - the user this credential is acting as.
 *
 * Worth knowing what "me" resolves to, because it is not always a person. A guest key or an
 * unqualified super-user key acts as a guest, and this endpoint answers with the guest user
 * rather than an error. Index::get()->keyType and the XF-Request-User response header are
 * how you tell.
 */
final class Me extends Resource
{
    public function get(): User
    {
        return User::fromArray($this->apiGet('me/')->array('me'));
    }

    /**
     * Update the acting user's own profile, options and privacy settings.
     *
     * @param  array<string, mixed>  $payload  option[...], profile[...], privacy[...],
     *         visible, activity_visible, timezone, custom_title, custom_fields[...]
     */
    public function update(array $payload): bool
    {
        return $this->apiPost('me/', $payload)->isSuccess();
    }

    /**
     * Change the acting user's email address.
     *
     * `confirmation_required` in the response means the forum has sent a confirmation mail
     * and the address has NOT changed yet - a true `success` alongside it does not mean
     * what it looks like.
     *
     * @return array{success: bool, confirmation_required: bool}
     */
    public function changeEmail(string $currentPassword, string $email): array
    {
        $response = $this->apiPost('me/email', [
            'current_password' => $currentPassword,
            'email' => $email,
        ]);

        return [
            'success' => $response->isSuccess(),
            'confirmation_required' => (bool) $response->value('confirmation_required', false),
        ];
    }

    public function changePassword(string $currentPassword, string $newPassword): bool
    {
        return $this->apiPost('me/password', [
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
        ])->isSuccess();
    }

    public function deleteAvatar(): bool
    {
        return $this->apiDelete('me/avatar')->isSuccess();
    }
}
