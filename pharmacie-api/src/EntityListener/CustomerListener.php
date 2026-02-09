<?php

namespace App\EntityListener;

use App\Entity\Customer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerListener
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher=$hasher;
    }

    public function prePersist(Customer $customer)
    {
       $this->encodePassword($customer);
    }

     public function preUpdate(Customer $customer)
    {
      $this->encodePassword($customer);
    }

    /**
      * Encode password based on plain password
      *
      * @param Customer $customer
      * @return void
    */
    public function encodePassword(Customer $customer)
    {
        if ($customer->getPlainPassword() === null) {
            return;
        }

        $customer->setPassword(
          $this->hasher->hashPassword(
              $customer,
              $customer->getPlainPassword()
             )
         );

       $customer->setPlainPassword(null);
      }
}
