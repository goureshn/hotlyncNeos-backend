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
                                            <br /> <p>Dear Team,</p>                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top" class="textContent">
                                        <p>{{$info['content']}}</p>                                            
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