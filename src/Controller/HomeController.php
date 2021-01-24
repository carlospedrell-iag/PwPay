<?php

namespace Pw\SlimApp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class HomeController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function showHomePage(Request $request, Response $response): Response
    {
        $profile_picture = '';
        
        if(isset($_SESSION['user'])){
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);   
        }

        return $this->container->get('view')->render(
            $response,
            'home.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'profile_picture' => $profile_picture,
            ]
        );
    }
}
