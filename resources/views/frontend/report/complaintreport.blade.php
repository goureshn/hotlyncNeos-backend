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
        td.items {
            width: 50px;
            text-align: right;
            padding-right: 5px;;
            font-weight: bold;
        }
        td.item {
            width: 150px;
            text-align: left;
            padding-right: 5px;
           
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

            border: 1px solid black;
            border-collapse: collapse;
        }

        td.subtitle {
            text-align:center;
            vertical-align:middle;
            border: 1px solid black;
            border-collapse: collapse;
            border-width: thin;
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
        .table1 {
            border: 1px solid black;
            border-collapse: collapse;
            table-layout: fixed;
            width: 200px;
           
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
// echo json_encode($data);    
?>
@include('frontend.report.complaint.complaint_desc')

@if($data['report_type'] == 'Frequency' || $data['report_type'] == 'Category')
    <div style="margin-top:10px;position: absolute;width: 98%;text-align: center;margin-bottom: 10px;">
        <p style="font-size:10px; font-weight: bold;text-align: center">Guest Feedback Report by {{$data['report_type']}}</p>
    </div>
@else
    @if($data['report_by'] == 'Periodical')
        <div style="margin-top:10px;position: absolute;width: 98%;text-align: center;margin-bottom: 10px;">
            <p style="font-size:10px; font-weight: bold;text-align: center">Guest Feedback Periodical Summary Report</p>
        </div>        
          
    @elseif($data['report_by'] == 'Consolidated')    
        <div style="margin-top:10px;position: absolute;width: 98%;text-align: center;margin-bottom: 10px;">
            <p style="font-size:10px; font-weight: bold;text-align: center">Guest Feedback Consolidated Report</p>
        </div>    
    @elseif($data['report_by'] == 'Compensation' && $data['report_type'] == 'Summary' )    
        <div style="margin-top:10px;position: absolute;width: 98%;text-align: center;margin-bottom: 10px;">
            <p style="font-size:10px; font-weight: bold;text-align: center">Summary Compensation</p>
        </div>    
    @elseif($data['report_by'] == 'Complaint' && $data['report_type'] == 'Detailed'  && $data['group_by'] == 'Executive' )    
        <div style="margin-top:10px;position: absolute;width: 98%;text-align: center;margin-bottom: 10px;">
            <p style="font-size:10px; font-weight: bold;text-align: center">GUEST FEEDBACK REPORT</p>
        </div>    
    @else
        <div style="margin-top:10px;position: absolute;width: 98%;text-align: center;margin-bottom: 10px;">
            <p style="font-size:10px; font-weight: bold;text-align: center">Guest Feedback Report  by {{$data['group_by']}}</p>
        </div> 
    @endif        
@endif

@if($data['report_type'] == 'Frequency')
    @include('frontend.report.complaint.complaint_frequency')
@endif
@if($data['report_type'] == 'Category')
    @include('frontend.report.complaint.complaint_category')
@endif

@if($data['report_by'] == 'Sub-complaint' && $data['report_type'] == 'Summary' && $data['group_by'] == 'Location' )
    @include('frontend.report.complaint.location_subcategory_summary')
@elseif($data['report_by'] == 'Sub-complaint' && $data['report_type'] == 'Summary' && $data['group_by'] == 'Response Rate' )
    @include('frontend.report.complaint.location_response_rate_summary')
@elseif($data['report_by'] == 'Complaint' && $data['report_type'] == 'Summary' && $data['group_by'] == 'Monthly' )
    @include('frontend.report.complaint.complaint_monthly_summary')    
@elseif($data['report_by'] == 'Complaint' &&  $data['group_by'] == 'Executive')
    @include('frontend.report.complaint.executive')
@elseif($data['report_by'] == 'Complaint' || $data['report_by'] == 'Sub-complaint' )
    @include('frontend.report.complaint.complaint')
@elseif(($data['report_by'] == 'Compensation') && ($data['report_type'] == 'Summary'))
    @include('frontend.report.complaint.compensation_summary')
@elseif(($data['report_by'] == 'Compensation') && ($data['group_by'] != 'Department'))
    @include('frontend.report.complaint.compensation')
@elseif(($data['report_by'] == 'Compensation') && ($data['group_by'] == 'Department'))
    @include('frontend.report.complaint.compensation_dept')
@elseif($data['report_by'] == 'Consolidated')
    @include('frontend.report.complaint.consolidated')
@elseif($data['report_by'] == 'Periodical')
    @include('frontend.report.complaint.dailysummary')
@endif

</body>
</html>

