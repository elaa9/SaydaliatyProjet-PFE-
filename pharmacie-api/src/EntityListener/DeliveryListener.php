<?php

namespace App\EntityListener;

use App\Entity\Delivery;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeliveryListener
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher=$hasher;
    }

    public function prePersist(Delivery $delivery)
    {
       $this->encodePassword($delivery);
    }

     public function preUpdate(Delivery $delivery)
    {
      $this->encodePassword($delivery);
    }

    /**
      * Encode password based on plain password
      *
      * @param Delivery $delivery
      * @return void
    */
    public function encodePassword(Delivery $delivery)
    {
        if ($delivery->getPlainPassword() === null) {
            return;
        }

        $delivery->setPassword(
          $this->hasher->hashPassword(
              $delivery,
              $delivery->getPlainPassword()
             )
         );

       $delivery->setPlainPassword(null);
      }
}
