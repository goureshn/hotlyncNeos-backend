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
                                            <br /> <p>Dear {{$info['recv_user_name']}},</p>                                            
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <p><b>Notification event</b>: {{$info['status']}}</p>
                                            <p><b>Created On</b>: {{$info['date_val']}}</p>
                                            <p><b>Status</b>: {{$info['status_val']}}</p>
                                            <p><b>Alarm</b>: {{$info['alarm_name']}}</p>
                                            <p><b>Description</b>: {{$info['alarm_description']}}</p>
                                            <p><b>Location</b>: {{$info['location']}}</p>
                                            <p><b>Comment</b>: {{$info['comment']}}</p>
                                        </td>
                                </tr>
                                </table>
                            </td>
                        </tr>
                        <?php
            if(!empty($info['acknowledge'])) { 
            ?>
            <tr>
              <td valign="top" width="600" class="flexibleContainerCell">
                <table align=" center" border="0" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                  <tr>
                    <td align="center" valign="top" width="600" class="flexibleContainerCell bottomShim">
                      <table border="0" cellpadding="0" cellspacing="0" width="150" class="emailButton">
                        <tr>
                          <td align="center" valign="middle" class="buttonContent">
                            <a href="{{$info['acknowledge']}}" target="_blank">Acknowledge</a>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
                
              </td>
            </tr>
            
            <?php } ?>
                    </table>    
                </td>
            </tr>
        </table>
        
        </td>
    </tr>
        

    </center>
</body>

</html>