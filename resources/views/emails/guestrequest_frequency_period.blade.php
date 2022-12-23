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
                                            <br /> <p>Dear {{$info['first_name']}} {{$info['last_name']}},</p>
                                            <p>
                                                {{$info['task']}} {{$info['category_name']}} has been raised {{$info['frequency']}} times for {{$info['location']}} during the last {{$info['period']}} days.    
                                            </p>   
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell" style="padding-bottom: 10px;" >
                                <table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%" class="flexibleContainer">
                                    <tr class="table-data">
                                        <td class="table-header" style="width: 25%">Date</td>
                                        <td class="table-header" style="width: 20%">Request ID</td>
                                        <td class="table-header" style="width: 15%">Assigned</td>
                                        <td class="table-header" style="width: 15%">Status</td>
                                        <td class="table-header" style="width: 25%">Comment</td>
                                    </tr>
                                    @foreach($info['request_list'] as $row)
                                        <tr class="table-data">
                                            <td style="font-size:14px;">{{date('d-M-Y', strtotime($row->created_time))}}</td>
                                            <td style="font-size:14px;">{{sprintf("%05d", $row->id)}}</td>
                                            <td style="font-size:14px;">{{$row->assigned}}</td>
                                            @if( $row->closed_flag == 1 && $row->status_id == 3 )
                                                <td style="font-size:14px;">Closed</td>
                                            @else
                                                <td style="font-size:14px;">{{$info['status_name'][$row->status_id]}}</td>
                                            @endif    
                                            <td style="font-size:14px;">{{$row->comment}}</td>        
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