<?php

use \Pw\SlimApp\Controller\HomeController;
use \Pw\SlimApp\Controller\SignupController;
use \Pw\SlimApp\Controller\SigninController;
use \Pw\SlimApp\Controller\VisitsController;
use \Pw\SlimApp\Controller\CookieMonsterController;
use \Pw\SlimApp\Controller\FlashController;
use \Pw\SlimApp\Controller\PostUserController;
use \Pw\SlimApp\Middleware\StartSessionMiddleware;
use \Pw\SlimApp\Controller\FileController;
use \Pw\SlimApp\Controller\ProfileController;
use \Pw\SlimApp\Controller\AccountController;
use \Pw\SlimApp\Controller\MoneyController;

$app->add(StartSessionMiddleware::class);

$app->get(
    '/',
    HomeController::class . ":showHomePage"
)->setName('home');

$app->get(
    '/sign-up',
    SignupController::class . ":showSignup"
)->setName('signup');

$app->post(
    '/sign-up',
    SignupController::class . ":signupAction"
);

$app->get(
    '/activate',
    SignupController::class . ":activateUser"
);

$app->get(
    '/sign-in',
    SigninController::class . ":showSignin"
)->setName('signin');

$app->post(
    '/sign-in',
    SigninController::class . ":signinAction"
);

$app->post(
    '/log-out',
    SigninController::class . ":signOut"
)->setName('signout');

$app->get(
    '/profile',
    ProfileController::class . ":showProfile"
)->setName('profile');

$app->post(
    '/profile',
    ProfileController::class . ":profileAction"
)->setName('profile');

$app->get(
    '/profile/security',
    ProfileController::class . ":showSecurity"
)->setName('security');

$app->post(
    '/profile/security',
    ProfileController::class . ":changePassword"
);

$app->get(
    '/account/summary',
    AccountController::class . ":showDashboard"
)->setName('dashboard');

$app->get(
    '/account/bank-account',
    AccountController::class . ":showBankAccount"
)->setName('bank_account');

$app->post(
    '/account/bank-account',
    AccountController::class . ":checkBankAccount"
);

$app->post(
    '/account/bank-account/load',
    AccountController::class . ":loadMoney"
)->setName('load_money');

$app->get(
    '/account/money/send',
    MoneyController::class . ":showSendForm"
)->setName('send_money');

$app->post(
    '/account/money/send',
    MoneyController::class . ":sendMoney"
);

$app->get(
    '/account/money/requests',
    MoneyController::class . ":showRequestForm"
)->setName('request_money');

$app->post(
    '/account/money/requests',
    MoneyController::class . ":requestMoney"
);

$app->get(
    '/account/money/requests/pending',
    MoneyController::class . ":showPending"
)->setName('pending');

$app->get('/account/money/requests/{id}/accept',
    MoneyController::class . ":acceptMoneyRequest"
);

$app->get(
    '/account/transactions',
    AccountController::class . ":showTransactions"
)->setName('transactions');