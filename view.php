<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=10.0, user-scalable=yes">
    <title>LINE Login</title>
</head>
<body>

<? if($isLogin): ?>

    Original Size：<br>
    <img alt="original" src="<?=$Line->getUserPicture() . ''?>"><br>
    Small Size：<br>
    <img alt="small" src="<?=$Line->getUserPicture() . '/small'?>"><br>
    UserId： <?=$Line->getUserId()?> <br>
    UserName： <?=$Line->getUserName()?> <br>
    AccessToken： <?=$access_token?><br>

    <button type="button" onclick="location.href = '/?act=logout'">Logout</button>

<? else: ?>
    Welcome to LINE Login <br>
    You can press the down button to use LINE login, enjoy it. <br>
    <button type="button" onclick="location.href = '/?act=oauth'">Login</button>
<? endif; ?>
</body>
</html>
