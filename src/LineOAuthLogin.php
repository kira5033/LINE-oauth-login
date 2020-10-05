<?php

namespace src;

date_default_timezone_set("Asia/Taipei");

class LineOAuthLogin {

    private $LINE_ID;

    private $LINE_SECRET;

    private $userId;

    private $displayName;

    private $pictureUrl;

    private $accessToken;

    private $verifyData;

    public function __construct($ID, $SECRET){
        $this->LINE_ID = $ID;
        $this->LINE_SECRET = $SECRET;
    }

    /**
     * Create OAuth Link
     * @param $callback
     * @param $state
     * @param bool $forceAccount
     * @return string
     */
    public function createOAuth($callback, $state, $forceAccount = FALSE){

        $parameter = [
            'response_type' => 'code',
            'client_id'     => $this->LINE_ID,
            'state'         => $state,
            'redirect_uri'  => $callback,
            'bot_prompt'    => (($forceAccount) ? 'aggressive' : 'normal')
        ];

        $scope = 'openid%20profile%20email';

        return 'https://access.line.me/oauth2/v2.1/authorize?'.http_build_query($parameter).'&scope='.$scope;
    }

    /**
     * Handle OAuth Callback Data
     * @return array
     */
    public function getOAuthResponse(){

        $response = [];

        if(http_response_code() == 200){

            if(isset($_GET['code'])){
                $response = [
                    'success'           => TRUE,
                    'code'              => $_GET['code'],
                    'state'             => (isset($_GET['state'])) ? $_GET['state'] : '',
                    'status_changed'    => (isset($_GET['friendship_status_changed'])) ? $_GET['friendship_status_changed'] : false,
                ];
            }else if(isset($_GET['error'])){
                $response = [
                    'success'   => FALSE,
                    'code'      => $_GET['error'],
                    'state'     => (isset($_GET['state'])) ? $_GET['state'] : '',
                    'error_msg' => (isset($_GET['error_description'])) ? $_GET['error_description'] : '',
                ];
            }
        }

        return $response;
    }

    /**
     * Get Access Token and Go Get User Profile
     * @param $code
     * @param $redirect
     * @return bool
     */
    public function Authorization($code, $redirect){

        $header = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $content = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirect,
            'client_id'     => $this->LINE_ID,
            'client_secret' => $this->LINE_SECRET,
        ];

        $result = $this->post($header, "https://api.line.me/oauth2/v2.1/token", $content);

        if(empty($result->access_token)){
            return FALSE;
        }

        $this->verifyAccessToken($result->id_token);

        $this->accessToken = $result->access_token;

        return $this->getProfile($result->access_token);
    }

    /**
     * Refer From Authorization function
     * It will set profile data to params when callback's data is not empty.
     * @param $access_token
     * @return bool
     */
    public function getProfile($access_token){

        $header = [
            "content-type: application/x-www-form-urlencoded",
            "charset=UTF-8",
            'Authorization: Bearer ' . $access_token,
        ];

        $result = $this->get($header, "https://api.line.me/v2/profile");

        $this->userId = (isset($result->userId)) ? $result->userId : '';
        $this->displayName = (isset($result->displayName)) ? $result->displayName : '';
        $this->pictureUrl = (isset($result->pictureUrl)) ? $result->pictureUrl : '';

        return isset($result->userId) && $result->userId != '';
    }

    /**
     * @param $token
     */
    private function verifyAccessToken($token){

        $header = [
            "Content-Type: application/x-www-form-urlencoded",
        ];

        $content = [
            'id_token'  => $token,
            'client_id' => $this->LINE_ID,
        ];

        $result = $this->post($header, "https://api.line.me/oauth2/v2.1/verify", $content);

        $this->verifyData = $result;

    }

    /**
     * To check user and office account are friend or not.
     * @param $uerId
     * @param $channel_access_token
     * @return bool
     */
    public function checkFriend($uerId, $channel_access_token){

        $header = [
            'Authorization: Bearer ' . $channel_access_token,
        ];

        $result = $this->get($header, "https://api.line.me/v2/bot/profile/".$uerId);

        return !empty($result->userId);
    }

    /**
     * Logout LINE
     * @param $access_token
     * @return bool
     */
    public function revoke($access_token){

        $header = [
            "Content-Type: application/x-www-form-urlencoded",
        ];

        $content = [
            'access_token'  => $access_token,
            'client_id'     => $this->LINE_ID,
            'client_secret' => $this->LINE_SECRET,
        ];

        $result = $this->post($header, 'https://api.line.me/oauth2/v2.1/revoke', $content);

        return http_response_code() == 200;
    }

    private function get($header, $url){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $curl_response = curl_getinfo($ch);
        $result = json_decode($result);
        curl_close($ch);

        if ( isset($curl_response['http_code']) && $curl_response['http_code'] != '200' )
        {
            return FALSE;
        }

        return $result;

    }

    private function post($header, $url, $content){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
        $result = curl_exec($ch);
        $curl_response = curl_getinfo($ch);
        $result = json_decode($result);
        curl_close($ch);

        if ( isset($curl_response['http_code']) && $curl_response['http_code'] != '200' )
        {
            return FALSE;
        }

        return $result;
    }

    public function getUserId(){
        return $this->userId;
    }

    public function getUserName(){
        return $this->displayName;
    }

    public function getUserPicture(){
        return $this->pictureUrl;
    }

    public function getAccessToken(){
        return $this->accessToken;
    }

    public function getVerifyData(){
        return $this->verifyData;
    }
}
