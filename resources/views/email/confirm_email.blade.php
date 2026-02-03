<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Email</title>
</head>

<body>
<div style="text-align: center;">
    <h2>{{ $data['title'] }}</h2>

    <p style="size:12px;">Before you finish creating your account, you must confirm your email address. <br/> On the verification page, please enter this verification code.</p>
    <h2><b>{{ $data['body'] }}</b></h2>
    <p style="size:10px;">Your verification code will expires after 60 minutes.</p>
</div>

</body>

</html>
