<?php

namespace Pw\SlimApp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Pw\SlimApp\Model\User;
use Iban\Validation\Validator;
use Iban\Validation\Iban;
use Pw\SlimApp\Model\MoneyCharge;
use \DateTime;

final class AccountController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function showDashboard(Request $request, Response $response): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            //load flash messages
            $messages = $this->container->get('flash')->getMessages();
            $notifications = $messages['notifications'] ?? [];
            $errors = $messages['errors'][0] ?? [];

            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
            
            $transactions = $this->container->get('user_repository')->getTransactions($user->id());
            //sort array by date
            usort($transactions, function($a, $b) {
                $ad = $a->createdAt();
                $bd = $b->createdAt();
                if ($ad == $bd) { return 0;}
                return $ad > $bd ? -1 : 1;
            });

            $transactions = array_slice($transactions, 0, 5);

            return $this->container->get('view')->render(
                $response,
                'dashboard.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'user' => $_SESSION['user'],
                    'profile_picture' => $profile_picture,
                    'balance' => sprintf("%0.2f",$user->balance()),
                    'notifications' => $notifications,
                    'errors' => $errors,
                    'transactions' => $transactions,
                    'user_id' => $user->id(),
                ]
            );
        }
    }

    public function showBankAccount(Request $request, Response $response): Response{

        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
            
            return $this->container->get('view')->render(
                $response,
                'bank_account.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'profile_picture' => $profile_picture,
                    'owner_name' => $user->ownerName(),
                    'iban' => substr($user->iban(), 0, 6),
                ]
            );
        }
    }

    public function checkBankAccount(Request $request, Response $response): Response{
        
        $errors = [];
        $success = '';
        $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
        $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);   

        $data = $request->getParsedBody();

        $iban = new Iban($data['iban']);
        $validator = new Validator();

        if (!$validator->validate($iban)) {
            $errors = $validator->getViolations();
            
        } else {
            //if the IBAN is correct, we update it in the db
            $user->setOwnerName($data['owner_name']);
            $user->setIban($data['iban']);
            $this->container->get('user_repository')->updateUser($user);
            $success = 'Bank account details updated successfully!';
        }

        return $this->container->get('view')->render(
            $response,
            'bank_account.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'profile_picture' => $profile_picture,
                'owner_name' => $user->ownerName(),
                'iban' => substr($user->iban(), 0, 6),
                'errors' => $errors,
                'success' => $success,
            ]
        );
    }

    public function loadMoney(Request $request, Response $response): Response{
        
        $load_error = '';
        $load_success = '';

        $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
        $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);   

        $data = $request->getParsedBody();

        $amount = $data['amount'];
        if(is_numeric($amount)){
            if((float)$amount > 0){
                $user->setBalance($user->balance() + (float)$amount);
                $this->container->get('user_repository')->updateUser($user);

                //we create a new transaction
                $money_charge = new MoneyCharge($user->id(),(float)$amount, new DateTime());
                $this->container->get('user_repository')->createMoneyCharge($money_charge);
                $load_success = 'Money successfully loaded into the bank account!';
                
            } else {
                $load_error = 'Invalid positive number';
            }
        } else {
            $load_error = 'Invalid positive number';
        }

        return $this->container->get('view')->render(
            $response,
            'bank_account.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'profile_picture' => $profile_picture,
                'owner_name' => $user->ownerName(),
                'iban' => substr($user->iban(), 0, 6),
                'load_error' => $load_error,
                'load_success' => $load_success,
            ]
        );
    }

    public function showTransactions(Request $request, Response $response): Response{

        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);

            $transactions = $this->container->get('user_repository')->getTransactions($user->id());
            //sort array by date
            usort($transactions, function($a, $b) {
                $ad = $a->createdAt();
                $bd = $b->createdAt();
                if ($ad == $bd) { return 0;}
                return $ad > $bd ? -1 : 1;
            });

            return $this->container->get('view')->render(
                $response,
                'transactions.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'profile_picture' => $profile_picture,
                    'transactions' => $transactions,
                    'user_id' => $user->id(),
                ]
            );
        }
    }
}