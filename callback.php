<?php

require_once('src/LineOAuthLogin.php');
require_once('src/config.php');

use src\LineOAuthLogin;

isset($_SESSION) or session_start();

class Callback_Controller {

    function __construct()
    {
        $this->main();
    }

    function main(){

        $Line = new LineOAuthLogin(CLIENT_ID,CLIENT_SECRET);

        $state = (isset($_SESSION['state']) && $_SESSION['state'] != '') ? $_SESSION['state'] : '';

        $result = $Line->catchResponse()->Authorization(CALLBACK_URL, $state);

        if($result){

            $_SESSION['id_token'] = $Line->getUserIdToken();
            $_SESSION['access_token'] = $Line->getUserAccessToken();

        }

        header('Location: /');

    }

}

new Callback_Controller();

