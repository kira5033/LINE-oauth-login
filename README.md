# LINE Login 試作範例

參考LINE Login文件實作一個簡易登入套件  

## 說明

```php=
$Line = new LineOAuthLogin(CLIENT_ID,CLIENT_SECRET);
```

1. 產生登入連結
```php=
// 可以產生一個UUID作為$state，可以callback回來做驗證
$Line->createOAuthUrl(CALLBACK_URL, $state)
```
2. 在`callback.php`取得`Access Token`及`ID Token`
```php
// 這裡可以丟入$state做檢查
$result = $Line->catchResponse()->Authorization(CALLBACK_URL, $state);
if($result){
    // success
    $id_token = $Line->getUserIdToken();
    $access_token = $Line->getUserAccessToken();
}else{
    // failed
}
```
3. 檢查`ID token`
```php
$Line->validateViaIDToken($id_token);
```

## 其他

1. 取得使用者資訊
```php
$Line->getProfile($access_token);
$userId = getUserId();
$userName = getUserName();
$userPicture = getUserPicture();
```

2. 撤除`Access Token`
```php
$Line->revoke($access_token);
```
