<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

@include('emails.guestrequest_header')

<body> 
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
            <tr>
                <td align="center" valign="top" id="bodyCell">                
                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="emailBody">                    
                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <br /> <p>Dear {{$info['wholename']}}</p>    
                                            <p>All Sub-Complaints mentioned below are Completed. You can now close the Main Complaint.</p>                                        
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell" style="padding-bottom: 10px;" >
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="flexibleContainer">
                                    <tr class="table-data">
                                        <td class="table-header" style="width: 25%">Sub-Complaint</td>
                                        <td class="table-header" style="width: 25%">Department</td>
                                        <td class="table-header" style="width: 25%">Completed By</td>
                                        <td class="table-header" style="width: 25%">Date Completed</td>                                        
                                    </tr>
                                    @foreach($info['subcomplaint_list'] as $row)
                                        <tr class="table-data">
                                            <td style="font-size:14px;">{{$info['main_id']}}{{$row->sub_label}}</td>
                                            <td style="font-size:14px;">{{$row->department}}</td>
                                            <td style="font-size:14px;">{{$row->wholename}}</td>
                                            <td style="font-size:14px;">{{$row->completed_at}}</td>
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