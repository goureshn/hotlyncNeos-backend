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
                                            <br /> <p>Dear {{$info['name']}},</p>                                            
                                        </td>
                                    </tr>
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p>The contract for {{$info['address']}} is due to expire on {{date('d M Y', strtotime($info['end_date']))}}.</p>                                           
                                        </td>
                                    </tr>    
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Contractor</b>: {{$info['leaser']}}</p>
                                            <p><b>Address</b>: {{$info['address']}}</p>
                                            <p><b>Email</b>: {{$info['email']}}</p>
                                            <p><b>Phone</b>: {{$info['phone']}}</p>
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Description</b>: {{$info['description']}}</p>
                                            <p><b>Start Date</b>: {{date('d M Y', strtotime($info['start_date']))}}</p>
                                            <p><b>End Date</b>: {{date('d M Y', strtotime($info['end_date']))}}</p>
                                            <p><b>Contract Value</b>: {{$info['contract_value']}}</p>                                            
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