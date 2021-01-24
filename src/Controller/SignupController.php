<?php

declare(strict_types=1);

namespace Pw\SlimApp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use \DateTime;
//use Exception;
use Pw\SlimApp\Model\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

final class SignupController
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function showSignup(Request $request, Response $response): Response
    {

        return $this->container->get('view')->render(
            $response,
            'signup.twig',
            []
        );
    }

    public function signupAction(Request $request, Response $response): Response
    {
        // This method decodes the received json
        $data = $request->getParsedBody();

        $form = $this->validateSignUp($data);

        //check if errors were found
        if (count($form['errors']) > 0) {

            return $this->container->get('view')->render(
                $response,
                'signup.twig',
                [
                    'formErrors' => $form['errors'],
                    'formData' => $form['data']
                ]
            );
        }

        //we post the user into the db
        try {
            $user = new User(
                $data['email'] ?? '',
                password_hash($data['password'], PASSWORD_DEFAULT),
                new DateTime($data['birthdate']),
                '',
                new DateTime(),
                new DateTime(),
                bin2hex(random_bytes(8)),
                false,
                20,
                '',
                ''
            );

            $this->container->get('user_repository')->save($user);

        } catch (Exception $exception) {
            $response->getBody()->write('Unexpected error: ' . $exception->getMessage());
            return $response->withStatus(500);
        }
        //sends email to the user and shows a success message
        $this->sendMail($data['email']);
        return $this->container->get('view')->render(
            $response,
            'signup.twig',
            [
                'success_message' => 'Registration complete! Check your email for the activation link and activate your account to log in.'
            ]
        );
    
    }

    private function validateSignUp(array $data): array
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
                $errors['email'] = 'The email address is not valid, Only emails from the domain @salle.url.edu can be used';
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
            $errors['password'] = 'The password is not valid, It must be more than 5 characters, contain both upper and lower case letters and numbers.';
        }

        //birth date validation
        $birthdate=new DateTime($data['birthdate']);

        $ageLimit=new DateTime('-18 years');
        if($birthdate > $ageLimit){
            $errors['birthdate'] = 'The birthday is not valid, you need to be 18 years old or older';
        }

        //we save the data
        $form_data['birthdate'] = $birthdate->format('Y-m-d');
        $form_data['email'] = $email;
        $form_data['password'] = $password;

        $form['errors'] = $errors;
        $form['data'] = $form_data;

        return $form;
    }

    public function activateUser(Request $request, Response $response, $args): Response{
       
        $params = $request->getQueryParams();
        //echo $token;

        if($this->container->get('user_repository')->validateToken($params['token']) && $params['token'] != '0'){
            echo '<br>You have activated your PwPay account successfully! <a href="/sign-in">Sign in</a>';
            return $response->withStatus(201);
        } else {
            echo '<br>This activation link is invalid or it has expired...  <a href="/">Go Home</a>';
            return $response->withStatus(401);
        }
        
        
    }

    private function sendMail(string $email){
        $subject = 'Activate your PwPay account';
        $token = $this->container->get('user_repository')->getUser($email)->authToken();
        $link = 'http://pwpay.com:8030/activate?token='.$token;

        // Instantiation and passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();                                       // Send using SMTP
            $mail->Host= 'mail.smtpbucket.com';                    // Set the SMTP server to send through
            $mail->Port= 8025;                                     // TCP port to connect to

            //Recipients
            $mail->addAddress($email);
            $mail->setFrom('noreply@pwpay.com', 'Mailer');

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = 'Activate your PwPay account';
            $mail->Body    = 'To complete the activation of your account please click the following link: <a href="'.$link.'">'.$link.'</a>';
            $mail->AltBody = 'To complete the activation of your account please click the following link: '.$link;

            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    }


}