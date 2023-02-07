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
        hr{
            line-height: 0.5;
            background:#00a8f3;
        }
        tr, td {
            border: 1px solid #ECEFF1;
            color: #212121;
        }
        .inform {
            border: 0px ;
            text-align: left;
            height:25px;
        }
        td.right {
            text-align: right;
            padding-right: 5px;;
        }

        th {
            align:center;
            vertical-align:middle;
            background-color:#2c3e50 !important;
            color: #fff !important;
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
            text-align: left;
        }
        .total-amount {
            background-color:#E0E0E0 !important;
            color:#fff !important;
            font-weight:bold;
        }
        #block_container {
            text-align:center;
        }

        #bloc2 {
            display:inline-block;
            width:29%;
            float:right;
            color: #212121 !important;
        }
        #bloc1 {
            display:inline-block;
            width:49%;
        }
        li {
            list-style:none;
        }
        li.closed {
            color:#5a9437;
        }
        li.rejected {
            color:#e63017;
        }
        li.resolved {
            color:#1791e6;
        }
        li.completed {
            color:#e69617;
        }
        label {
            padding-left: 3px;;
            padding-right: 7px;
            font-size: 13px;
        }
        .yellow {
            color:#f8c77a;
        }
        .red {
            color:#f13d5b;;
        }
        .green {
            color:#30bb29;
        }
    </style>
</head>