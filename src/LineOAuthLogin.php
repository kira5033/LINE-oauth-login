<?php

namespace src;

class LineOAuthLogin {

    private $LINE_ID;

    private $LINE_SECRET;

    private $userId;

    private $userName;

    private $userPicture;

    private $userAccessToken;

    private $userIdToken;

    private $jwtData;

    private $response;

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
    public function createOAuthUrl($callback, $state, $forceAccount = FALSE){

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
     * @return $this
     */
    public function catchResponse(){

        $this->response = [];

        if(http_response_code() == 200){

            if(isset($_GET['code'])){
                $this->response = [
                    'success'           => TRUE,
                    'code'              => $_GET['code'],
                    'state'             => (isset($_GET['state'])) ? $_GET['state'] : '',
                    'status_changed'    => (isset($_GET['friendship_status_changed'])) ? $_GET['friendship_status_changed'] : false,
                ];
            }else if(isset($_GET['error'])){
                $this->response = [
                    'success'   => FALSE,
                    'code'      => $_GET['error'],
                    'state'     => (isset($_GET['state'])) ? $_GET['state'] : '',
                    'error_msg' => (isset($_GET['error_description'])) ? $_GET['error_description'] : '',
                ];
            }
        }

        return $this;
    }

    /**
     * Get access token and ID token , then get user profile to check
     * @param $redirect
     * @param $state
     * @return bool
     */
    public function Authorization($redirect, $state = ''){

        if( empty($redirect) ||
            empty($this->response) ||
            !$this->response['success'] ||
            $this->response['state'] != $state
        )
        {
            return false;
        }

        $header = [
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $content = [
            'grant_type'    => 'authorization_code',
            'code'          => $this->response['code'],
            'redirect_uri'  => $redirect,
            'client_id'     => $this->LINE_ID,
            'client_secret' => $this->LINE_SECRET,
        ];

        $result = $this->sendRequest('post', $header, "https://api.line.me/oauth2/v2.1/token", $content);

        if(empty($result->access_token)){
            return FALSE;
        }

        $this->setUserIdToken($result->id_token);

        $this->setUserAccessToken($result->access_token);

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

        $result = $this->sendRequest('get', $header, "https://api.line.me/v2/profile");

        $userId = (isset($result->userId)) ? $result->userId : '';
        $this->setUserId($userId);

        $userName = (isset($result->displayName)) ? $result->displayName : '';
        $this->setUserName($userName);

        $userPicture = (isset($result->pictureUrl)) ? $result->pictureUrl : '';
        $this->setUserPicture($userPicture);

        return isset($result->userId) && $result->userId != '';
    }

    /**
     * @param $token
     * @return bool
     */
    public function validateViaIDToken($token){

        $header = [
            "Content-Type: application/x-www-form-urlencoded",
        ];

        $content = [
            'id_token'  => $token,
            'client_id' => $this->LINE_ID,
        ];

        $result = $this->sendRequest('post', $header, "https://api.line.me/oauth2/v2.1/verify", $content);

        if(isset($result->error)){
//            {
//                "error": "invalid_request",
//                "error_description": "IdToken expired."
//            }
            $this->jwtData = null;
            return false;
        }else{
            $this->setJwtData($result);
            return true;
        }
    }

    /**
     * To check user and office account are friend or not.
     * @param $uerId
     * @param $channel_access_token
     * @return bool
     */
    public function checkBotFriend($uerId, $channel_access_token){

        $header = [
            'Authorization: Bearer ' . $channel_access_token,
        ];

        $result = $this->sendRequest('get', $header, "https://api.line.me/v2/bot/profile/".$uerId);

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

        $result = $this->sendRequest('post',$header, 'https://api.line.me/oauth2/v2.1/revoke', $content);

        return http_response_code() == 200;
    }

    private function sendRequest($method, $header, $url, $content = array()){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if(strtolower($method) == 'post'){
            curl_setopt($ch, CURLOPT_POST, true);
        }
        if(!empty($content)){
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
        }

        $result = curl_exec($ch);
        $curl_response = curl_getinfo($ch);
        $result = json_decode($result);
        curl_close($ch);

        if ( isset($curl_response['http_code']) && $curl_response['http_code'] != 200 )
        {
            return FALSE;
        }

        return $result;

    }

    /**
     * @param mixed $userId
     */
    private function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @param mixed $userName
     */
    private function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @param mixed $userPicture
     */
    private function setUserPicture($userPicture)
    {
        $this->userPicture = $userPicture;
    }

    /**
     * @param mixed $userAccessToken
     */
    private function setUserAccessToken($userAccessToken)
    {
        $this->userAccessToken = $userAccessToken;
    }

    /**
     * @param mixed $userIdToken
     */
    private function setUserIdToken($userIdToken)
    {
        $this->userIdToken = $userIdToken;
    }

    /**
     * @param mixed $jwtData
     */
    private function setJwtData($jwtData)
    {
        $this->jwtData = $jwtData;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return mixed
     */
    public function getUserPicture()
    {
        return $this->userPicture;
    }

    /**
     * @return mixed
     */
    public function getUserAccessToken()
    {
        return $this->userAccessToken;
    }

    /**
     * @return mixed
     */
    public function getUserIdToken()
    {
        return $this->userIdToken;
    }

    /**
     * @return mixed
     */
    public function getJwtData()
    {
        return $this->jwtData;
    }


}
