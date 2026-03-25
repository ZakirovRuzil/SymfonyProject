<?php

namespace App\Security;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public const EDIT = 'POST_EDIT';
    public const DELETE = 'POST_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE], true) && $subject instanceof Post;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $post = $subject;

        if (in_array(User::ROLE_ADMIN, $user->getRoles(), true)) {
            return true;
        }

        if ($post->getAuthor()?->getId() === $user->getId()) {
            return true;
        }

        return in_array(User::ROLE_MODERATOR, $user->getRoles(), true)
            && $post->getSection()?->isModerator($user) === true;
    }
}
