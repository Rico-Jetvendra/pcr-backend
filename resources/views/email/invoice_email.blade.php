<!DOCTYPE html>
<html lang="en">

<head>
    <title>Invoice Email</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <p style="size:12px;">There is a new invoice from {{ $data['data'][13] }} with invoice number {{ $data['data'][1] }} and detail purchase : </p>

    <table class="header" width="100%">
        <thead>
            <tr>
                <th><h2><b>Vehicle</b></h2></th>
                <th style="text-align:right;"><h2><b>Invoice</b></h2></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $data['data'][2] }}<td>
                <td style="text-align:right;">{{ $data['data'][1] }}<td>
            </tr>
            <tr>
                <td>{{ $data['data'][3] }}<td>
                <td style="text-align:right;"><?= date("d-m-Y", strtotime($data['data'][6])); ?><td>
            </tr>
            <tr>
                <td colspan="2">{{ $data['data'][4] }}<td>
            </tr>
            <tr>
                <td colspan="2">{{ $data['data'][10] }}<td>
            </tr>
            <tr>
                <td colspan="2">{{ $data['data'][11] }}<td>
            </tr>
            <tr>
                <td colspan="2"><?= date("Y", strtotime($data['data'][12])); ?><td>
            </tr>
        </tbody>
    </table>

    <br/>

    <table id="items">
        <thead>
            <tr>
                <th>No</th>
                <th>Item</th>
                <th>Serial Number</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach($data['details'] as $item){ ?>
                <tr>
                    <td><?= $i ?></td>
                    <td><?= $item['item_display'] ?></td>
                    <td><?= array_key_exists('serialno', $item) ? $item['serialno']: ''; ?></td>
                </tr>
            <?php $i++; } ?>
        </tbody>
    </table>

    <br/>

    <center>
        <a href="<?= env('APP_FRONT_URL') ?>/#/approve/<?= $data['invoice_id'] ?>" class="button-success">Approved</a>
        <a href="<?= env('APP_FRONT_URL') ?>/#/reject/<?= $data['invoice_id'] ?>" class="button-error">Reject</a>
    </center>

    <p>Thank you</p>
</div>

</body>
</html>
