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

        $response = $Line->getOAuthResponse();

        if(isset($_SESSION['state']) && $_SESSION['state'] != '' && $_SESSION['state'] == $response['state']){

            if($Line->Authorization($response['code'],CALLBACK_URL)){

                $_SESSION['access_token'] = $Line->getAccessToken();

            }
        }

        header('Location: /');

    }

}

new Callback_Controller();

