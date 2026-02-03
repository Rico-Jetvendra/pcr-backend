<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
</head>

<body>
<div style="text-align: center;">
    <h2>{{ $data['title'] }}</h2>

    <p style="size:12px;">You are receiving this email because we received a password reset request for your account.</p>
    <h2><b>{{ $data['body'] }}</b></h2>
    <p style="size:12px;">This password reset code will expires after 60 minutes.</p>
    <p style="size:12px;">If you did not request a password reset, no further action is required.</p>
</div>

</body>

</html>
