<?php

namespace App\EntityListener;

use App\Entity\AdminPharmacy;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminPharmacyListener
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher=$hasher;
    }

    public function prePersist(AdminPharmacy $adminpharmacist)
    {
       $this->encodePassword($adminpharmacist);
    }

     public function preUpdate(AdminPharmacy $adminpharmacist)
    {
      $this->encodePassword($adminpharmacist);
    }

    /**
      * Encode password based on plain password
      *
      * @param Pharmacist $pharmacist
      * @return void
    */
    public function encodePassword(AdminPharmacy $adminpharmacist)
    {
        if ($adminpharmacist->getPlainPassword() === null) {
            return;
        }

        $adminpharmacist->setPassword(
          $this->hasher->hashPassword(
              $adminpharmacist,
              $adminpharmacist->getPlainPassword()
             )
         );

       $adminpharmacist->setPlainPassword(null);
      }
}
