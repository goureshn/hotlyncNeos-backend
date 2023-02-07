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
    </style>
</head>
<body>
    @include('frontend.report.minibar.minibar_desc')

    <div style="margin-top:5px;position: absolute;width: 90%;margin-bottom: 30px;" align="center">
		@if (($data['report_type'] == 'Detailed' ) || ($data['report_type'] == 'Summary' ))
			<p align="center"; style="font-size:10px; margin-top:0; text-align: center">{{$data['report_type']}} Report by {{$data['report_by']}}</p>
		@else
			<p align="center"; style="font-size:10px; margin-top:0; text-align: center">{{$data['report_type']}} Report</p>
		@endif
    </div>
    <br>
   @if (($data['report_type'] != 'Summary' ) || ($data['report_by'] != 'Service Item' )) 
        @include('frontend.report.minibar.date')
    @endif
    @if (($data['report_type'] == 'Summary' ) && ($data['report_by'] == 'Service Item' )) 
        @include('frontend.report.minibar.summary')
    @endif

</body>
</html>

