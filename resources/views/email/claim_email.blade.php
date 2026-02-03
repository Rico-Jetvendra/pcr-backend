<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Status Claim</title>
    <style>
        #items {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        #items td, #items th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        #items th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
        }

        .button-success {
            background-color: #04AA6D;
            border: none;
            color: white;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            padding: 16px;
            cursor: pointer;
        }

        a:link, a:visited, a:active {
            color:white;
        }

        .button-error {
            background-color: red;
            border: none;
            color: white;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            padding: 16px;
            cursor: pointer;
        }

        .header{
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        .header thead{
            border-top:1px solid red;
            border-bottom:1px solid red;
        }

    </style>
</head>

<body>
<div>
    <h2>{{ $data['subject'] }}</h2>

    <p style="size:12px;">Dear {{ $data['user'] }},</p>
    <p>There's a new claim with details :</b></p>

    <table>
        <tr>
            <td>Oddo</td>
            <td>:</td>
            <td>{{ $data['data'][4] }}</td>
        </tr>
        <tr>
            <td>Retailer</td>
            <td>:</td>
            <td>{{ $data['shop'] }}</td>
        </tr>
    </table>

    <br/>

    <center>
        <a href="<?= env('APP_FRONT_URL') ?>/#/approveClaim/<?= $data['claim_id'] ?>" class="button-success">Approved</a>
        <a href="<?= env('APP_FRONT_URL') ?>/#/rejectClaim/<?= $data['claim_id'] ?>" class="button-error">Reject</a>
    </center>

    <p>Thank you</p>
</div>

</body>

</html>
