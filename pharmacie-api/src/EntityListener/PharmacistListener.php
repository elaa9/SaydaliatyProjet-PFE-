<?php

namespace App\EntityListener;

use App\Entity\Pharmacist;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PharmacistListener
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher=$hasher;
    }

    public function prePersist(Pharmacist $pharmacist)
    {
       $this->encodePassword($pharmacist);
    }

     public function preUpdate(Pharmacist $pharmacist)
    {
      $this->encodePassword($pharmacist);
    }

    /**
      * Encode password based on plain password
      *
      * @param Pharmacist $pharmacist
      * @return void
    */
    public function encodePassword(Pharmacist $pharmacist)
    {
        if ($pharmacist->getPlainPassword() === null) {
            return;
        }

        $pharmacist->setPassword(
          $this->hasher->hashPassword(
              $pharmacist,
              $pharmacist->getPlainPassword()
             )
         );

       $pharmacist->setPlainPassword(null);
      }
}
