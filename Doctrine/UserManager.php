<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;

class UserManager extends BaseUserManager
{
    protected ?ObjectManager $objectManager;
    private string $class;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        CanonicalFieldsUpdater $canonicalFieldsUpdater,
        ManagerRegistry $registry,
        $class
    ) {
        parent::__construct($passwordUpdater, $canonicalFieldsUpdater);

        $this->objectManager = $registry->getManagerForClass($class);
        $this->class = $class;
    }

    public function deleteUser(UserInterface $user): void
    {
        $this->objectManager->remove($user);
        $this->objectManager->flush();
    }

    public function getClass(): string
    {
        if (strpos($this->class, ':') !== false) {
            $metadata = $this->objectManager->getClassMetadata($this->class);
            $this->class = $metadata->getName();
        }

        return $this->class;
    }

    public function findUserBy(array $criteria): ?User
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    public function findUsers(): Collection
    {
        return $this->getRepository()->findAll();
    }

    public function reloadUser(UserInterface $user): void
    {
        $this->objectManager->refresh($user);
    }

    public function updateUser(UserInterface $user, $andFlush = true): void
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);

        $this->objectManager->persist($user);
        if ($andFlush) {
            $this->objectManager->flush();
        }
    }

    protected function getRepository(): ObjectRepository
    {
        return $this->objectManager->getRepository($this->getClass());
    }
}
