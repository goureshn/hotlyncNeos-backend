<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

@include('emails.guestrequest_header')
<?php
    $keys = array_keys($info);
?>
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
                                            <br /> <p>Dear {{$info1['name']}},</p>                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top" class="textContent">
                                        <br /> <p>The Work Request on {{$info1['equip_name']}} has been updated with the following details:</p>                                            
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="600" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                        @foreach($keys as $key)    
                                            <p><b>{{$key}}</b>: {{$info[$key]}}</p>                                            
                                        @endforeach    
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