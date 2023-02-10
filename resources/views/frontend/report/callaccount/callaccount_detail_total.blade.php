<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <style type="text/Css">
        .pagenum:after {
            content: counter(page);
        }
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
				.total-amount {
						background-color:#E0E0E0 !important;
            color:#fff !important;
						font-weight:bold;
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
<?php
$http = 'http://';
if( isset($_SERVER['HTTPS'] ) )
    $http = 'https://';

$port = $_SERVER['SERVER_PORT'];
$siteurl = $http . $_SERVER['SERVER_NAME'] . ':' . $port . '/';
$path = $_SERVER['DOCUMENT_ROOT'] . $data['property']->logo_path;
$type1 = pathinfo($path, PATHINFO_EXTENSION);
$image_data = file_get_contents($path);
$logo_image_data = 'data:image/' . $type1 . ';base64,' . base64_encode($image_data);

?>
  
<div id="block_container">
    <div id="bloc1" align="left" valign="top">
        <img src="<?php echo $logo_image_data?>"
             alt=""  width = 150>
    </div>
    <div id="bloc2" align = "right">
        <table class="plain" style="width:100%" align = "right">
            <tr>
                <th class="plain1"  align="right"><b>Date Generated :</b></th>
                <th class="plain1"  align="left"><?php echo date('d-M-Y')?></th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Period :</b></th>
                <th class="plain1"   align="left"><?php echo $data['month']?></th>
            </tr>
            <tr>
                <th class="plain1" align="right"><b>Property :</b></th>
                <th class="plain1"  align="left"><?php echo $data['property']->name ?></th>
            </tr>
          </table>
    </div>
</div>
  
  <div style="margin-top:20px;position: absolute;width: 98%;text-align: center;margin-bottom: 30px;">
<p align="right"; style="font-size:15px; margin-top:0;text-align:center"> Deductions for <b>{{$data['user']->first_name}} {{$data['user']->last_name}} </b>for the month of <b>{{$data['month']}}</b></p>

@if(!empty($data['admin']))
    <div style="margin-top: 20px">
        
<p style="margin: 0px">
        
            <b>Admin</b>
        </p>        
         
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
           
                <th><b>Date</b></th>
           
                <th><b>Time</b></th>
                <th><b>Extension</b></th>
              
                
                
                    <th><b>Called No</b></th>
               
                    
                   
                <th><b>Duration</b></th>
               
                <th><b>Call Type</b></th>
                <th><b>Country</b></th>
                <th><b>Cost</b></th>
            
                
            </tr>
            </thead>
            <tbody>
           @foreach ($data['admin'] as  $key => $row)
                <tr class="">
               
                      <td>{{date("d-M-y",  strtotime($row->call_date))}}</td>
             
                    <td>{{$row->start_time}}</td>
                    <td>{{$row->extension}}</td>
                  
                    <td>{{$row->called_no}}</td>
                
                     
                    <td>{{gmdate("H:i:s", $row->duration)}}</td>
                   
                        <td>{{$row->call_type}}</td>
                        <td>{{$row->country}}</td>
                   
                    <td class="right">{{sprintf('%.2f', $row->carrier_charges)}}</td>
                </tr>
                
            @endforeach
           
            
            </tbody>
        </table>
    </div>
@endif
@if(!empty($data['mobile']))
    <div style="margin-top: 25px">
        
<p style="margin: 0px">
        
            <b>Mobile </b>
        </p>        
         
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
           
                <th><b>Date</b></th>
           
                <th><b>Time</b></th>
                <th><b>From</b></th>
              
                
                
                    <th><b>Called No</b></th>
               
                    
                   
                <th><b>Duration</b></th>
               
                <th><b>Call Type</b></th>
                <th><b>Country</b></th>
                <th><b>Cost</b></th>
            
                
            </tr>
            </thead>
            <tbody>
           @foreach ($data['mobile'] as  $key1 => $row1)
                <tr class="">
               
                      <td>{{date("d-M-y",  strtotime($row1->date))}}</td>
             
                    <td>{{$row1->time}}</td>
                    <td>{{$row1->call_from}}</td>
                  
                    <td>{{$row1->call_to}}</td>
                
                     
                    <td>{{gmdate("H:i:s", $row1->duration)}}</td>
                   
                        <td>{{$row1->call_type}}</td>
                        <td>{{$row1->country}}</td>
                   
                    <td class="right">{{sprintf('%.2f', $row1->charges)}}</td>
                </tr>
                
            @endforeach
           
            
            </tbody>
        </table>
    </div>
@endif


{{--<div id="chart1" style="height: 300px; width: 500px; position: relative;" class="jqplot-target">--}}

{{--</div>--}}

{{--<script type="text/javascript" src="/frontpage/bower_components/jquery/dist/jquery.min.js"></script>--}}
{{--<script type="text/javascript" src="/frontpage/bower_components/jqplot/jquery.jqplot.js"></script>--}}
{{--<script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.json2.js"></script>--}}

{{--<script>--}}
{{--//    $(document).ready(function(){--}}
        {{--// Our data renderer function, returns an array of the form:--}}
        {{--// [[[x1, sin(x1)], [x2, sin(x2)], ...]]--}}
        {{--var sineRenderer = function() {--}}
            {{--var data = [[]];--}}
            {{--for (var i=0; i<13; i+=0.5) {--}}
                {{--data[0].push([i, Math.sin(i)]);--}}
            {{--}--}}
            {{--return data;--}}
        {{--};--}}

        {{--// we have an empty data array here, but use the "dataRenderer"--}}
        {{--// option to tell the plot to get data from our renderer.--}}
        {{--var plot1 = $.jqplot('chart1',[],{--}}
            {{--title: 'Sine Data Renderer',--}}
            {{--dataRenderer: sineRenderer--}}
        {{--});--}}
{{--//    });--}}


{{--</script>--}}
</body>
</html>