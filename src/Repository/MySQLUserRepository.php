<?php

declare(strict_types=1);

namespace Pw\SlimApp\Repository;

use PDO;
use Pw\SlimApp\Model\User;
use Pw\SlimApp\Model\MoneyRequest;
use Pw\SlimApp\Model\MoneySend;
use Pw\SlimApp\Model\MoneyCharge;
use Pw\SlimApp\Model\UserRepository;
use \DateTime;

final class MySQLUserRepository implements UserRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private PDOSingleton $database;

    public function __construct(PDOSingleton $database)
    {
        $this->database = $database;
    }

    public function save(User $user): void
    {
        $query = <<<'QUERY'
        INSERT INTO user(email, password, birthdate, created_at, updated_at, auth_token, is_activated, balance)
        VALUES(:email, :password, :birthdate, :created_at, :updated_at, :auth_token, :is_activated, :balance)

        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $email = $user->email();
        $password = $user->password();
        $birthdate = $user->birthdate()->format(self::DATE_FORMAT);
        $createdAt = $user->createdAt()->format(self::DATE_FORMAT);
        $updatedAt = $user->updatedAt()->format(self::DATE_FORMAT);
        $auth_token = $user->authToken();
        $is_activated = (int)$user->isActivated();
        $balance = $user->balance();

        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('birthdate', $birthdate, PDO::PARAM_STR);
        $statement->bindParam('created_at', $createdAt, PDO::PARAM_STR);
        $statement->bindParam('updated_at', $updatedAt, PDO::PARAM_STR);
        $statement->bindParam('auth_token', $auth_token, PDO::PARAM_STR);
        $statement->bindParam('is_activated',$is_activated, PDO::PARAM_STR);
        $statement->bindParam('balance',$balance, PDO::PARAM_STR);

        $statement->execute();
    }
    
    public function login(array $data): bool {

        $error = 0;

        $query = <<<'QUERY'
        SELECT * FROM user WHERE email = :email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $data['email'], PDO::PARAM_STR);
        $statement->execute();
        
        $user = $statement->fetch();
        //if the user doesn't exist
        if($user == null){
            return false;
        }

        //we check email, verify password and check if it's activated
        if(strcmp($data['email'],$user['email']) || !password_verify($data['password'], $user['password']) || !$user['is_activated']){
            return false;
        }

        return true;

    }

    public function getUser(string $email): User {

        $query = <<<'QUERY'
        SELECT * FROM user WHERE email = :email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();
        
        $data = $statement->fetch();

        $user = new User(
            $data['email'],
            $data['password'],
            new DateTime($data['birthdate']),
            $data['phone'],
            new DateTime($data['created_at']),
            new DateTime($data['updated_at']),
            $data['auth_token'],
            filter_var($data['is_activated'], FILTER_VALIDATE_BOOLEAN),
            floatval($data['balance']),
            $data['owner_name'],
            $data['iban']
        );
        $user->setId((int)$data['id']);

        return $user;
    }

    public function getUserId(string $email): string {

        $query = <<<'QUERY'
        SELECT id FROM user WHERE email = :email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();
        
        $data = $statement->fetch();

        return $data['id'];
    }

    public function getUserEmail(string $user_id): string {

        $query = <<<'QUERY'
        SELECT email FROM user WHERE id = :user_id
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('user_id', $user_id, PDO::PARAM_STR);
        $statement->execute();
        
        $data = $statement->fetch();

        return $data['email'];
    }

    public function updateUser(User $user){

        $query = <<<'QUERY'
        UPDATE user
        SET
            password = :password,
            phone = :phone,
            updated_at = :updated_at,
            is_activated = :is_activated,
            balance = :balance,
            owner_name = :owner_name,
            iban = :iban
        WHERE
            email = :email;
        QUERY;

        $password = $user->password();
        $phone = $user->phone();
        $updatedAt = new DateTime();
        $updatedAt = $updatedAt->format(self::DATE_FORMAT);
        $is_activated = (int)$user->isActivated();
        $email = $user->email();
        $balance = $user->balance();
        $owner_name = $user->ownerName();
        $iban = $user->iban();

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('updated_at', $updatedAt, PDO::PARAM_STR);
        $statement->bindParam('is_activated', $is_activated, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('balance', $balance, PDO::PARAM_STR);
        $statement->bindParam('owner_name', $owner_name, PDO::PARAM_STR);
        $statement->bindParam('iban', $iban, PDO::PARAM_STR);
        $statement->execute();
    }

    public function updateProfilePicture($email,$filename){
        $query = <<<'QUERY'
        UPDATE user
        SET
            profile_picture = :filename,
            updated_at = :updated_at
        WHERE
            email = :email;
        QUERY;

        $updatedAt = new DateTime();
        $updatedAt = $updatedAt->format(self::DATE_FORMAT);

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('filename', $filename, PDO::PARAM_STR);
        $statement->bindParam('updated_at', $updatedAt, PDO::PARAM_STR);
        $statement->execute();
    }

    public function getProfilePicturePath($email) :string{
        $query = <<<'QUERY'
        SELECT profile_picture FROM user WHERE email = :email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();
        
        $data = $statement->fetch();
        $profile_picture = $data['profile_picture'];

        //if there is no profile picture set, we return the path to the default profile picture
        if($profile_picture == ''){
            return 'assets/media/image/generic_profile_picture.png';
        } else {
            return 'uploads/' . $profile_picture;
        }
    }

    public function validateToken($token){
        $query = <<<'QUERY'
        SELECT auth_token FROM user WHERE auth_token = :token
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('token', $token, PDO::PARAM_STR);
        $statement->execute();

        if($statement->rowCount() > 0){
            $query = <<<'QUERY'
            UPDATE user
            SET
                is_activated = 1,
                auth_token = 0,
                updated_at = :updated_at
            WHERE
                auth_token = :token;
            QUERY;

            $updatedAt = new DateTime();
            $updatedAt = $updatedAt->format(self::DATE_FORMAT);

            $statement = $this->database->connection()->prepare($query);
            $statement->bindParam('token', $token, PDO::PARAM_STR);
            $statement->bindParam('updated_at', $updatedAt, PDO::PARAM_STR);
            $statement->execute();

            return 1;
        } else {
            return 0;
        }
    }

    public function checkIfUserExists(string $email): bool {

        $query = <<<'QUERY'
        SELECT * FROM user WHERE email = :email
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();
        
        $user = $statement->fetch();
        //if the user doesn't exist
        if($user == null){
            return false;
        } else {
            return true;
        }
    }

    public function checkIfRequestExists($id): bool {

        $query = <<<'QUERY'
        SELECT * FROM money_request WHERE id = :id AND NOT is_completed
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        
        $request = $statement->fetch();

        if($request == null){
            return false;
        } else {
            return true;
        }
    }

    public function createMoneyRequest(MoneyRequest $money_request){

        $query = <<<'QUERY'
        INSERT INTO transaction(user_id, amount, created_at)
        VALUES(:user_id, :amount, :created_at);
        INSERT INTO money_request(id, requester_id)
        VALUES(
            (SELECT LAST_INSERT_ID()), 
            :requester_id
        );
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $user_id = $money_request->userId();
        $amount = $money_request->amount();
        $created_at = $money_request->createdAt()->format(self::DATE_FORMAT);
        $requester_id = $money_request->requesterId();
        

        $statement->bindParam('user_id', $user_id, PDO::PARAM_STR);
        $statement->bindParam('amount', $amount, PDO::PARAM_STR);
        $statement->bindParam('created_at', $created_at, PDO::PARAM_STR);
        $statement->bindParam('requester_id', $requester_id, PDO::PARAM_STR);

        $statement->execute();
    }

    public function createMoneySend(MoneySend $money_send){

        $query = <<<'QUERY'
        INSERT INTO transaction(user_id, amount, created_at)
        VALUES(:user_id, :amount, :created_at);
        INSERT INTO money_send(id, recipient_id)
        VALUES(
            (SELECT LAST_INSERT_ID()), 
            :recipient_id
        );
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $user_id = $money_send->userId();
        $amount = $money_send->amount();
        $created_at = $money_send->createdAt()->format(self::DATE_FORMAT);
        $recipient_id = $money_send->recipientId();
        
        $statement->bindParam('user_id', $user_id, PDO::PARAM_STR);
        $statement->bindParam('amount', $amount, PDO::PARAM_STR);
        $statement->bindParam('created_at', $created_at, PDO::PARAM_STR);
        $statement->bindParam('recipient_id', $recipient_id, PDO::PARAM_STR);


        $statement->execute();
    }

    public function createMoneyCharge(MoneyCharge $money_charge){

        $query = <<<'QUERY'
        INSERT INTO transaction(user_id, amount, created_at)
        VALUES(:user_id, :amount, :created_at);
        INSERT INTO money_charge(id)
        VALUES(
            (SELECT LAST_INSERT_ID())
        );
        QUERY;

        $statement = $this->database->connection()->prepare($query);

        $user_id = $money_charge->userId();
        $amount = $money_charge->amount();
        $created_at = $money_charge->createdAt()->format(self::DATE_FORMAT);
        
        $statement->bindParam('user_id', $user_id, PDO::PARAM_STR);
        $statement->bindParam('amount', $amount, PDO::PARAM_STR);
        $statement->bindParam('created_at', $created_at, PDO::PARAM_STR);

        $statement->execute();
    }

    public function getReceivedRequests($user_id) : array{

        $query = <<<'QUERY'
        SELECT * FROM money_request AS mr, transaction AS t WHERE user_id = :id AND mr.id = t.id
        QUERY;
  
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $user_id, PDO::PARAM_STR);
        $statement->execute();

        $requests = [];
        
        $data = $statement->fetchAll();
        
        foreach($data as $row){
            $money_request = new MoneyRequest(
                (int)$row['user_id'],  
                floatval($row['amount']), 
                new DateTime($row['created_at']),
                (int)$row['requester_id'],  
                filter_var($row['is_completed'], FILTER_VALIDATE_BOOLEAN)
            );
            $money_request->setId((int)$row['id']);
            $money_request->setRequesterEmail($this->getUserEmail($row['requester_id']));

            $requests[] = $money_request;
        }

        return $requests;
    }

    public function getSentRequests($user_id) : array{
        
        $query = <<<'QUERY'
        SELECT * FROM money_request AS mr, transaction AS t WHERE mr.requester_id = :id AND mr.id = t.id
        QUERY;
        
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $user_id, PDO::PARAM_STR);
        $statement->execute();

        $requests = [];
        
        $data = $statement->fetchAll();
        
        foreach($data as $row){
            $money_request = new MoneyRequest(
                (int)$row['user_id'],  
                floatval($row['amount']), 
                new DateTime($row['created_at']),
                (int)$row['requester_id'],  
                filter_var($row['is_completed'], FILTER_VALIDATE_BOOLEAN)
            );
            $money_request->setId((int)$row['id']);
            $money_request->setRequesterEmail($this->getUserEmail($row['requester_id']));
            $money_request->setRequesterEmail($this->getUserEmail($row['user_id']));

            $requests[] = $money_request;
        }

        return $requests;
    }

    public function getMoneyRequest($id) : MoneyRequest {
        $query = <<<'QUERY'
        SELECT * FROM money_request AS mr, transaction AS t WHERE mr.id = :id AND t.id = :id AND NOT mr.is_completed
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();

        $data = $statement->fetch();

        $money_request = new MoneyRequest(
            (int)$data['user_id'],  
            floatval($data['amount']), 
            new DateTime($data['created_at']),
            (int)$data['requester_id'],  
            filter_var($data['is_completed'], FILTER_VALIDATE_BOOLEAN)
        );
        $money_request->setId((int)$data['id']);
        $money_request->setRequesterEmail($this->getUserEmail($data['requester_id']));
        
        return $money_request;

    }

    public function getAllMoneySends($user_id) : array{
        
        $query = <<<'QUERY'
        SELECT * FROM money_send AS ms, transaction AS t WHERE t.user_id = :id AND ms.id = t.id
        UNION
        SELECT * FROM money_send AS ms, transaction AS t WHERE ms.recipient_id = :id AND ms.id = t.id
        QUERY;
        
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $user_id, PDO::PARAM_STR);
        $statement->execute();

        $money_sends = [];
        
        $data = $statement->fetchAll();
        
        foreach($data as $row){
            $money_send = new MoneySend(
                (int)$row['user_id'],  
                floatval($row['amount']), 
                new DateTime($row['created_at']),
                (int)$row['recipient_id']
            );
            $money_send->setId((int)$row['id']);
            $money_send->setRecipientEmail($this->getUserEmail($row['recipient_id']));

            $money_sends[] = $money_send;
        }

        return $money_sends;
    }

    public function getAllMoneyCharges($user_id) : array{
        $query = <<<'QUERY'
        SELECT * FROM money_charge AS mc, transaction AS t WHERE user_id = :id AND mc.id = t.id
        QUERY;
        
        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $user_id, PDO::PARAM_STR);
        $statement->execute();

        $money_charges = [];
        
        $data = $statement->fetchAll();
        
        foreach($data as $row){
            $money_charge = new MoneyCharge(
                (int)$row['user_id'],  
                floatval($row['amount']), 
                new DateTime($row['created_at'])
            );
            $money_charge->setId((int)$row['id']);

            $money_charges[] = $money_charge;
        }

        return $money_charges;
    }

    public function getTransactions($user_id) : array{
    
        $sent_requests = $this->getSentRequests($user_id);
        $received_requests = $this->getReceivedRequests($user_id);
        $money_sends = $this->getAllMoneySends($user_id);
        $money_charges = $this->getAllMoneyCharges($user_id);

        $transactions = array_merge($sent_requests,$received_requests, $money_sends, $money_charges);

        return $transactions;
    }

    public function completeRequest($id) {
        $query = <<<'QUERY'
        UPDATE money_request
        SET
            is_completed = 1
        WHERE
            id = :id;
        QUERY;

        $statement = $this->database->connection()->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
    }
}