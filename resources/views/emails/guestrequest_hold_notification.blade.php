<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

@include('emails.guestrequest_header')

<body>
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
            <tr>
                <td align="center" valign="top" id="bodyCell">                
                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="emailBody">          
                        <div class="table-header" width="100%">
                            <div style="text-align:left;margin-top = '10px'; margin-bottom = '10px'; background-color: #FF8C00;" ><img src="https://ennovatechae-my.sharepoint.com/personal/shen_baylon_ennovatech_ae/Documents/Public/hotlync_w.png" width="200" height = "30"><br/>
                        </div>            
                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <br /> <p>Dear {{$info['first_name']}} {{$info['last_name']}},</p>
                                            <p>
                                                There are currently {{count($info['request_list'])}} requests which are put on Hold.                                                
                                            </p>   
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="100%" class="flexibleContainerCell" style="padding-bottom: 10px;" >
                                <table border="0" cellpadding="0" cellspacing="0" width="1000px" height="100%" class="flexibleContainer">
                                    <tr class="table-data">
                                        <td class="table-header" style="width: 22%">Date/ID</td>
                                        
                                        <td class="table-header" style="width: 15%">Request</td>
                                        <td class="table-header" style="width: 12%">Hold Duration</td>
                                        <td class="table-header" style="width: 14%">Assigned To</td>
                                        <td class="table-header" style="width: 22%">Comment</td>
                                    </tr>
                                    @foreach($info['request_list'] as $row)
                                        <tr class="table-data">
                                            <td style="font-size:14px;">{{date('d/M/Y H:i', strtotime($row->created_time))}} {{sprintf("%05d", $row->id)}}</td>
                                            
                                            <td style="font-size:14px;">{{$row->task_name}}</td>
                                            <td style="font-size:14px;">{{$row->hold_time}}</td>
                                            <td style="font-size:14px;">{{$row->wholename}}</td>
                                            <td style="font-size:14px;">{{$row->custom_message}}</td>        
                                        </tr>
                                    @endforeach    

                                </table>
                            </td>
                        </tr>
                    </table>    
                </td>
            </tr>
        </table>
        @include('emails.guestrequest_footer')
    </center>
</body>

</html>