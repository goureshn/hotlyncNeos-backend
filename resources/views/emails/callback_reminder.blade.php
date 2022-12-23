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

                                    <tr>
                          <td valign="top" width="600" class="flexibleContainerCell">
						  <table align="Left" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                              <tr>
                                <td valign="top" class="textContent">
                                  <p><b>Agent</b>: {{$info['agent']}}</p>
                                  <p><b>Origin</b>: {{$info['origin']}}</p>
                                  <p><b>Caller ID</b>: {{$info['callerid']}}</p>
                                  <p><b>Call Type</b>: {{$info['call_type']}}</p>
                                  <p><b>Channel</b>: {{$info['channel']}}</p>
                                </td>
                              </tr>
                            </table>
                            <table align="Right" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                              <tr>
                                <td valign="top" class="textContentLast">
                                  <p><b>Status</b>: {{$info['status']}}</p>
                                  <p><b>Call Time</b>: {{$info['call_time']}}</p>
                                  <p><b>Type</b>: {{$info['type']}}</p>
                                 
                                </td>
                              </tr>
                            </table></td>
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