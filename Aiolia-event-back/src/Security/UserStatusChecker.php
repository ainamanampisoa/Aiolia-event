<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserStatusChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->assertAccountIsActive($user);
        $this->assertRoleIsAllowed($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->assertAccountIsActive($user);
        $this->assertRoleIsAllowed($user);
    }

    private function assertAccountIsActive(User $user): void
    {
        if ($user->getAccountStatus() !== 'active') {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte doit être validé par un administrateur avant de pouvoir vous connecter.'
            );
        }
    }

    private function assertRoleIsAllowed(User $user): void
    {
        if (!in_array($user->getRole(), [UserRoleEnum::ADMIN, UserRoleEnum::ORGANIZER], true)) {
            throw new CustomUserMessageAuthenticationException(
                'Seuls les comptes Administrateur ou Organisateur peuvent accéder à Aiolia Event Back.'
            );
        }
    }
}

