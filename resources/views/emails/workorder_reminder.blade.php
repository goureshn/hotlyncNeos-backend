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
                            <div style="text-align:left;margin-top = '10px'; margin-bottom = '10px'; background-color: #FF8C00;" ><img src="https://ennovatech.com/assets/images/company-logo/hotlync.svg" width="200" height = "30"><br/>
                        </div>                  
                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <br /> <p>Dear {{$info['first_name']}},</p> 
                                            <br /> <p>{{$info['desc']}}</p>                                            
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr >
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Name</b>: {{$info['name']}}</p>
                                            <p><b>Location</b>: {{$info['location']}}</p>  
                                            <p><b>Equipment</b>: {{$info['equipment']}}</p>
                                            <p><b>Equipment ID</b>: {{$info['equip_id']}}</p>                                          
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Scheduled Date</b>: {{date('d M Y H:i', strtotime($info['schedule_date']))}}</p>
                                            <p><b>Assignee</b>: {{$info['assignee_list']}}</p>
                                            @if($info['notify'] != 'Reopened')
                                            @if(!empty($info['start_date']))
                                                <p><b>Start Date</b>: {{date('d M Y H:i', strtotime($info['start_date']))}}</p>
                                            @endif
                                            @if(!empty($info['end_date']))
                                                <p><b>End Date</b>: {{date('d M Y H:i', strtotime($info['end_date']))}}</p>
                                            @endif
                                            @endif
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                           
                                       <p><b>Description</b>: {{$info['description']}}</p>
                                        </td>
                                    </tr>                                    
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