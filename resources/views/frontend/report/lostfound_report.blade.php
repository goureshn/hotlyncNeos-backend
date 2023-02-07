<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <style type="text/Css">
        @media screen {
            div.footer {
                position: fixed;
                bottom: 0;
            }
        }
        table, p, b{
            font-family: 'Titillium Web';
            <?php if(empty($data['font-size'])) {?>
              font-size: 12px;
            <?php }else { ?>
            font-size: {{$data['font-size']}};
            <?php } ?>
            -webkit-font-smoothing: antialiased;
            line-height: 1.42857143;
            border-spacing: 0;
        }
        tr, td {
            border: 1px solid #ECEFF1;
						color: #212121;
        }

        td.items {
            width: 100px;
            text-align: right;
            padding-right: 20px;;
            font-weight: bold;
        }
        td.right {
            text-align: right;
            padding-right: 5px;
        }

        td.sub_comp {
            padding-left: 25px;
            width: 150px;;
        }
        hr{
            line-height: 0.5;
            background:#00a8f3;
        }
        th {
            align:center;
            vertical-align:middle;
            background-color:#2c3e50 !important;
            color: #fff !important;
        }
        th.subtitle {
            align:center;
            vertical-align:middle;
            background-color:#d8d9db !important;
            color: #4a4949 !important;
        }

        .grid tr:nth-child(even) {
            background-color: #F5F5F5;
        }

        .plain1 {
            border:0 !important;
            <?php if(empty($data['font-size'])) {?>
             font-size: 12px;
            <?php }else { ?>
            font-size: {{$data['font-size']}};
            <?php } ?>
            vertical-align:middle;
            background-color:#fff !important;
            color: #212121 !important;
            
        }
        #block_container {
            text-align:center;
        }

        #bloc2 {
            display:inline-block;
            width:49%;
            float:right;
            color: #212121 !important;
        }
        #bloc1 {
            display:inline-block;
            width:49%;
           
        }

        .highlight {
            background: yellow;
        }
    </style>
</head>
<body>
<?php
$http = 'http://';
if( isset($_SERVER['HTTPS'] ) )
    $http = 'https://';

$port = $_SERVER['SERVER_PORT'];
$siteurl = $http . $_SERVER['SERVER_NAME'] . ':' . $port . '/';
?>
@include('frontend.report.lnf.lostfound_desc')
<div style="margin-top:0px;position: absolute;width: 98%;text-align: center;margin-bottom: 30px;">
    <p style="font-size:10px; font-weight: bold;text-align: center">{{$data['title']}}</p>
</div>
<br>

    {{-- @include('frontend.report.lnf.lostfound_desc') --}}

</body>
</html>