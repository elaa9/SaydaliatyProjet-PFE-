<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserService extends AbstractController
{

    public function getUserData()
    {
        return $this->getUser();
    }
}
