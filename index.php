<?php

require_once('src/LineOAuthLogin.php');
require_once('src/config.php');

use src\LineOAuthLogin;

isset($_SESSION) or session_start();

class LINE_Controller {

    protected $act;

    function __construct()
    {
        $this->main();
    }

    function main(){

        $this->act = (isset($_REQUEST['act']) && $_REQUEST['act'] != '') ? $_REQUEST['act'] : '';

        switch ($this->act){

            case "oauth":
                $this->oauth();
                break;
            case "logout":
                $this->logout();
                break;
            default:
                $this->index();
        }

    }

    function index(){

        $access_token = (isset($_SESSION['access_token']) && $_SESSION['access_token'] != '') ? $_SESSION['access_token'] : '';

        $Line = new LineOAuthLogin(CLIENT_ID,CLIENT_SECRET);

        $isLogin = $Line->getProfile($access_token);

        include_once dirname(__FILE__) . '/view.php';

    }

    function oauth(){
        $Line = new LineOAuthLogin(CLIENT_ID,CLIENT_SECRET);

        $_SESSION['state'] = md5(time());

        header('Location: '.$Line->createOAuth(CALLBACK_URL, $_SESSION['state']));
    }

    function logout(){

        $access_token = (isset($_SESSION['access_token']) && $_SESSION['access_token'] != '') ? $_SESSION['access_token'] : '';

        $Line = new LineOAuthLogin(CLIENT_ID,CLIENT_SECRET);

        $Line->revoke($access_token);

        unset($_SESSION['access_token']);

        header('Location: /');
    }

}

new LINE_Controller();

