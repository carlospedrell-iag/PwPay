<?php

declare(strict_types=1);

namespace Pw\SlimApp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use \DateTime;
use Exception;
use Pw\SlimApp\Model\User;

final class SigninController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function showSignin(Request $request, Response $response): Response
    {

        return $this->container->get('view')->render(
            $response,
            'signin.twig',
            []
        );
    }

    public function signinAction(Request $request, Response $response): Response
    {
        // This method decodes the received json
        $data = $request->getParsedBody();

        $form = $this->validateSignIn($data);

        //we try to login the user only if there are no errors so far
        if(count($form['errors']) == 0){
            if(!$this->container->get('user_repository')->login($data)){
                $form['errors']['login'] = 'You have entered an invalid email or password or the user has not been activated yet.';
            }
        }

        //check if errors were found
        if (count($form['errors']) > 0) {

            return $this->container->get('view')->render(
                $response,
                'signin.twig',
                [
                    'formErrors' => $form['errors'],
                    'formData' => $form['data']
                ]
            );
        }

        //we login the user in a new session
        $_SESSION['user'] = $data['email'];
        
        return $response->withStatus(301)->withHeader('Location', '/account/summary');;
    }

    private function validateSignIn(array $data): array
    {
        $form = [];
        $errors = [];

        $form_data = [];

        //email validation
        $email = $data['email'];
        $allowed = ['salle.url.edu','students.salle.url.edu']; 

        if (filter_var($email, FILTER_VALIDATE_EMAIL)){
            $parts = explode('@', $email);
            $domain = array_pop($parts);

            if (!in_array($domain, $allowed)){
                $errors['email'] = 'The email address is not valid';
            }
        } else {
            $errors['email'] = 'The email address is not valid';
        }

        //password validation
        $password = $data['password'];
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        if (empty($password) || strlen($password) < 6 || !$uppercase || !$lowercase || !$number) {
            $errors['password'] = 'The password is not valid';
        }


        //we save the data
        $form_data['email'] = $email;
        $form_data['password'] = $password;

        $form['errors'] = $errors;
        $form['data'] = $form_data;

        return $form;
    }

    public function signOut(Request $request, Response $response): Response
    {
        //We unset the session and redirect to home
        unset($_SESSION['user']);
        return $response->withHeader('Location', '/')->withStatus(200);
    }


}