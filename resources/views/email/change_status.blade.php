<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Status Invoice</title>
</head>

<body>
<div>
    <h2>{{ $data['subject'] }}</h2>

    <p style="size:12px;">Dear {{ $data['user'] }},</p>
    <p>We wanted to inform you that the invoice with invoice number ({{ $data['invoice_no'] }}) {{ $data['status_text'] }} <b>{{ $data['status'] }}.</b></p>
    <p>Thank you</p>
</div>

</body>

</html>
