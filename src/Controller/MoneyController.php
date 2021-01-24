<?php

namespace Pw\SlimApp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Pw\SlimApp\Model\User;
use Pw\SlimApp\Model\Transaction;
use Pw\SlimApp\Model\MoneyRequest;
use Pw\SlimApp\Model\MoneySend;
use \DateTime;

final class MoneyController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function showSendForm(Request $request, Response $response): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
            
            return $this->container->get('view')->render(
                $response,
                'send_money.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'user' => $_SESSION['user'],
                    'profile_picture' => $profile_picture,
                ]
            );
        }
    }

    public function sendMoney(Request $request, Response $response): Response {

        $data = $request->getParsedBody();
        $errors = $this->validateData($data);   
        //it will only check the users if there are no form errors
        if(count($errors) == 0){
            $errors = $this->checkUsers($data['send_money_to'], $data['amount'],true);
        }

        if(count($errors) == 0){
            $amount = $data['amount'];
            //we update the users' balance
            $user_sender = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $user_recipient = $this->container->get('user_repository')->getUser($data['send_money_to']);

            $user_sender->setBalance($user_sender->balance() - $amount);
            $user_recipient->setBalance($user_recipient->balance() + $amount);

            $this->container->get('user_repository')->updateUser($user_sender);
            $this->container->get('user_repository')->updateUser($user_recipient);

            //we create a new transaction of type MoneySend to store in the DB
            $user_id = $this->container->get('user_repository')->getUserId($_SESSION['user']);
            $recipient_id = $this->container->get('user_repository')->getUserId($data['send_money_to']);
            
            $money_send = new MoneySend($user_id, $amount, new DateTime(), $recipient_id);
            $this->container->get('user_repository')->createMoneySend($money_send);

            $this->container->get('flash')->addMessage(
                'notifications',
                'Money sent successfully!'
            );

            return $response->withHeader('Location', '/account/summary')->withStatus(302);

        }

        $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
        $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
        
        return $this->container->get('view')->render(
            $response,
            'send_money.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'user' => $_SESSION['user'],
                'profile_picture' => $profile_picture,
                'errors' => $errors,
            ]
        );
    }

    public function showRequestForm(Request $request, Response $response): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
            
            return $this->container->get('view')->render(
                $response,
                'request_money.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'user' => $_SESSION['user'],
                    'profile_picture' => $profile_picture,
                ]
            );
        }
    }

    public function showPending(Request $request, Response $response): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $user->setId($this->container->get('user_repository')->getUserId($_SESSION['user']));

            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
            $money_requests = $this->container->get('user_repository')->getReceivedRequests($user->id());

            $flag = 0;

            foreach ($money_requests as $request) {
                if(!$request->isCompleted()){
                    $flag = 1;
                }
            }

            if(!$flag){
                $money_requests = [];
            }
            

            return $this->container->get('view')->render(
                $response,
                'pending.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'user' => $_SESSION['user'],
                    'profile_picture' => $profile_picture,
                    'money_requests' => $money_requests,
                ]
            );
        }
    }

    public function requestMoney(Request $request, Response $response): Response {

        $data = $request->getParsedBody();
        $errors = $this->validateData($data);   
        //it will only check the users if there are no form errors
        if(count($errors) == 0){
            $errors = $this->checkUsers($data['request_money_from'], $data['amount'], false);
        }

        if(count($errors) == 0){
            $amount = $data['amount'];
            $requester_id = $this->container->get('user_repository')->getUserId($_SESSION['user']);
            $user_id = $this->container->get('user_repository')->getUserId($data['request_money_from']);

            $money_request = new MoneyRequest($user_id, $amount, new DateTime(), $requester_id, false);
            
            $this->container->get('user_repository')->createMoneyRequest($money_request);
            
            $this->container->get('flash')->addMessage(
                'notifications',
                'Money requested successfully!'
            );
            
            return $response->withHeader('Location', '/account/summary')->withStatus(302);
        }

        $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
        $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
        
        return $this->container->get('view')->render(
            $response,
            'request_money.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'user' => $_SESSION['user'],
                'profile_picture' => $profile_picture,
                'errors' => $errors,
            ]
        );
    }

    public function acceptMoneyRequest(Request $request, Response $response, $args): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {

            $errors = $this->validateMoneyRequest($args['id']);
            
            if(count($errors) > 0){
                $this->container->get('flash')->addMessage(
                    'errors',
                    $errors
                );
            } else {
                $money_request = $this->container->get('user_repository')->getMoneyRequest($args['id']);
                
                //we update the users' balance
                $amount = $money_request->amount();
                $requester_email = $money_request->requesterEmail();

                $user_sender = $this->container->get('user_repository')->getUser($_SESSION['user']);
                $user_requester = $this->container->get('user_repository')->getUser($requester_email);

                $user_sender->setBalance($user_sender->balance() - $amount);
                $user_requester->setBalance($user_requester->balance() + $amount);

                $this->container->get('user_repository')->updateUser($user_sender);
                $this->container->get('user_repository')->updateUser($user_requester);

                //we create a new transaction of type MoneySend to store in the DB
                $money_send = new MoneySend($user_sender->id(), $amount, new DateTime(), $user_requester->id());
                $this->container->get('user_repository')->createMoneySend($money_send);

                //update the db marking the request as completed
                $this->container->get('user_repository')->completeRequest($args['id']);

                $this->container->get('flash')->addMessage(
                    'notifications',
                    'Money sent successfully!'
                );
            }
            
            return $response->withHeader('Location', '/account/summary')->withStatus(302);
        }
    }

    private function validateData(array $data): array{

        $errors = [];
        $email = $data['send_money_to'] ?? $data['request_money_from'];
        $amount = $data['amount'];

        $allowed = ['salle.url.edu','students.salle.url.edu'];
        //validate email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)){
            $parts = explode('@', $email);
            $domain = array_pop($parts);
            if (!in_array($domain, $allowed)){
                $errors['email'] = 'The email address is not valid, Only emails from the domain @salle.url.edu can be used.';
            }
        } else {
            $errors['email'] = 'The email address is not valid.';
        }
        //validate number
        if(is_numeric($amount)){
            if((float)$amount <= 0.00){
                $errors[] = 'Invalid positive number.';
            } 
        } else {
            $errors[] = 'Invalid number.';
        }

        return $errors;
    }

    private function validateMoneyRequest(string $id): array{

        $errors = [];

        if(!$this->container->get('user_repository')->checkIfRequestExists($id)){
            $errors[] = "Invalid Money Request";
        } else {
            $money_request = $this->container->get('user_repository')->getMoneyRequest($id);
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);

            //if the money request belongs to a different user
            if($money_request->userId() != $user->id()){
                $errors[] = "Invalid Money Request";
            }
            if($user->balance() < $money_request->amount()){
                $errors[] = 'There is not enough money in your account.';
            }
        }

        return $errors;
    }

    private function checkUsers(string $email, $amount, bool $is_sending): array{
        $errors = [];

        //We check if the current user has enough money only if it's a recipient! (the user is sending money)
        if($is_sending){
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);

            if($user->balance() < $amount){
                $errors[] = 'There is not enough money in your account.';
            }
        }
        
        //Check if the user that is going to receive the money exists and it's activated
        $exists = $this->container->get('user_repository')->checkIfUserExists($email);
        if(!$exists){
            $errors[] = 'The recipient doesn\'t exist.';
        } else {
            $user = $this->container->get('user_repository')->getUser($email);

            if(!$user->isActivated()){
                $errors[] = 'The recipient doesn\'t exist.';
            }
        }

        return $errors;
    }

}