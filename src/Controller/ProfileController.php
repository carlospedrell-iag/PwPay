<?php

namespace Pw\SlimApp\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Pw\SlimApp\Model\User;
use DateTime;
use Imagick;

final class ProfileController
{
    private ContainerInterface $container;

    private const UPLOADS_DIR = __DIR__ . '/../../public/uploads';

    private const UNEXPECTED_ERROR = "An unexpected error occurred uploading the file '%s'...";

    private const INVALID_EXTENSION_ERROR = "The received file extension '%s' is not valid, only .png is supported";

    // We use this const to define the extensions that we are going to allow
    private const ALLOWED_EXTENSIONS = ['png'];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function showProfile(Request $request, Response $response): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            //we get the user from the database yo show in the form
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
            
            return $this->container->get('view')->render(
                $response,
                'profile.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'email' => $user->email(),
                    'birthdate' => $user->birthdate()->format('Y-m-d'),
                    'phone' => $user->phone(),
                    'profile_picture' => $profile_picture,
                ]
            );
        }
    }

    public function profileAction(Request $request, Response $response): Response
    {   
        
        // This method decodes the received json
        $data = $request->getUploadedFiles();
        $data['form'] = $request->getParsedBody();

        //validate first
        $form = $this->validateProfile($data);
        //we retrieve user from the db
        $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
        
        //if there are no errors, we update the user in the database
        if (count($form['errors']) == 0){

            if($data['form']['phone'] != ''){
                $user->setPhone($data['form']['phone']);
            }

            if($data['file']->getError() != UPLOAD_ERR_NO_FILE){
                $this->saveProfilePicture($data['file']);
            }
            
            //we only update if at least one of the fields is not empty
            if( ($data['form']['phone'] != '') || ($data['file']->getError() != UPLOAD_ERR_NO_FILE) ){
                $user->setUpdatedAt(new DateTime());
                $this->container->get('user_repository')->updateUser($user);
            }
        
        }
        
        $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);        

        return $this->container->get('view')->render(
            $response,
            'profile.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'email' => $user->email(),
                'birthdate' => $user->birthdate()->format('Y-m-d'),
                'phone' => $user->phone(),
                'formErrors' => $form['errors'],
                'formMessages' => $form['messages'],
                'profile_picture' => $profile_picture
            ]
        );
    }

    public function validateProfile(array $data) : array {
        $form = [];
        $errors = [];
        $picture_errors = [];
        $messages = [];

        $phone = $data['form']['phone'];
        $picture = $data['file'];
        //validate spanish phone numbering 
        if($phone != ''){
            if(strlen($phone) != 9 ||  !($phone[0] == '6' || $phone[0] == '7' ) || ($phone[0] == '7' && $phone[1] == '0' ) || !is_numeric($phone)){
                $errors['phone'] = 'Invalid spanish phone number.';
            }
        }
        
        //validate picture
        /** @var UploadedFileInterface $picture */
        //We add an error for any error case except for empty file upload
        if($picture->getError() !== UPLOAD_ERR_NO_FILE){
            if ($picture->getError() !== UPLOAD_ERR_OK) {
                $picture_errors[] = sprintf(self::UNEXPECTED_ERROR, $picture->getClientFilename());
            } else {
                $name = $picture->getClientFilename();
                $fileInfo = pathinfo($name);
                $format = $fileInfo['extension'];
    
                if (!$this->isValidFormat($format)) {
                    $picture_errors[] = sprintf(self::INVALID_EXTENSION_ERROR, $format);
                } 
                
                if($picture->getSize() > 1000000){
                    $picture_errors[] = 'The size of the image must be less than 1MB.';
                }
            }
        }
        

        if(count($picture_errors) > 0){
            $errors['profile_picture'] = $picture_errors;
        }
        
        if(count($errors) == 0){
            $messages['success'] = 'Profile updated successfully.';
        }

        $form['errors'] = $errors;
        $form['messages'] = $messages;

        return $form;
    }


    private function isValidFormat(string $extension): bool
    {
        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    private function saveProfilePicture(object $file){
        $filename = $this->generateFileName();

        //first we create the uploads folder
        if (!file_exists(self::UPLOADS_DIR)) {
            mkdir(self::UPLOADS_DIR, 0777, true);
        }
        //we save the file in a physical location and open it to modify it
        $file->moveTo(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $filename);
        $imagick = new Imagick(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $filename);

        //we check the dimensions, if it's not a square (1:1) we will crop it
        $imageinfo= $imagick->getImageGeometry();
        $width = $imageinfo['width'];
        $height = $imageinfo['height'];

        if($width > $height){
            $imagick->cropImage($height,$height,0,0);
        }

        if($width < $height){
            $imagick->cropImage($width,$width,0,0);
        }
        //and resize it to 400 x 400
        $imagick->resizeImage(400, 400, Imagick::FILTER_LANCZOS, 1); 
        $imagick->writeImage(self::UPLOADS_DIR . DIRECTORY_SEPARATOR . $filename);

        //last but not least we store the filename in the database for the current user
        $this->container->get('user_repository')->updateProfilePicture($_SESSION['user'],$filename);
    }

    private function generateFileName(): string {
        //we generate a unique file name for the profile picture
        return 'profile_picture_' . uniqid(). '.png';
    }

    public function showSecurity(Request $request, Response $response): Response
    {   
        if(!isset($_SESSION['user'])){
            //check if user is logged in, redirects back to login
            echo '<br>You need to sign in to access this URL. <a href="/sign-in">Sign in</a>';
            return $response->withStatus(401);
        } else {
            
            $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);

            return $this->container->get('view')->render(
                $response,
                'security.twig',
                [
                    'session_set' => isset($_SESSION['user']),
                    'profile_picture' => $profile_picture,
                ]
            );
        }
    }

    public function changePassword(Request $request, Response $response): Response
    {   
        // This method decodes the received json
        $data = $request->getParsedBody();

        $old_password = $data['old_password'];
        $new_password = $data['new_password'];
        $confirm_password = $data['confirm_password'];
        $error = '';
        $success_message = '';

        if($this->checkPassword($old_password) && $this->validatePassword($new_password) && !strcmp($new_password, $confirm_password)){
            //Everything is ok, we update the password in the db
            $user = $this->container->get('user_repository')->getUser($_SESSION['user']);
            $user->setPassword(password_hash($new_password, PASSWORD_DEFAULT));
            $this->container->get('user_repository')->updateUser($user);
            
            $success_message = 'Password changed successfully.';
        } else {
            //Error
            $error = 'The password you entered is invalid or doesn\'t match the new one.';
        }

        $profile_picture = $this->container->get('user_repository')->getProfilePicturePath($_SESSION['user']);
        return $this->container->get('view')->render(
            $response,
            'security.twig',
            [
                'session_set' => isset($_SESSION['user']),
                'profile_picture' => $profile_picture,
                'password_error' => $error,
                'success_message' => $success_message,
            ]
        );
        

    }

    public function checkPassword($password) : bool {
        $user = $this->container->get('user_repository')->getUser($_SESSION['user']);

        return password_verify($password, $user->password());
    }

    public function validatePassword($password) : bool {
        //password validation
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);

        return !(empty($password) || strlen($password) < 6 || !$uppercase || !$lowercase || !$number);
     
    }

    
}
