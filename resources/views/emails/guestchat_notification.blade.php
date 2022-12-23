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
                                        <br /> <h1 style="color: dodgerblue">Dear {{$info['user_name']}}</h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>The below chats has reached the maximum waiting time without any agents accepting it:</p>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top" width="600" class="flexibleContainerCell">
                            <table border="0" align="Left" cellpadding="0" cellspacing="0" width="100%" class="flexibleContainer">
                                <thead>
                                <tr style="font-weight: bolder">
                                    <th style="padding: 10px">Guest Type</th>
                                    <th style="padding: 10px">Guest Name</th>
                                    <th style="padding: 10px">Room</th>
                                    <th style="padding: 10px">Mobile Number</th>
                                    <th style="padding: 10px">Start Time</th>
                                    <th style="padding: 10px">Waiting Time</th>
                                </tr>
                                </thead>
                                <tbody>
                                
                                @foreach($info['session_arr'] as $session_info)

                                    <?php
                                    $sdate = strtotime($session_info->updated_at);
                                    $now_datetime = date_create_from_format('Y-m-d H:i:s', date('Y-m-d H:i:s'))->getTimestamp();
                                    $real_start_time = date('Y-m-d H:i:s', $sdate);

                                    $date_difference = "difference";
                                    $waiting_time = $now_datetime - $sdate;

                                    $waiting_hours = $waiting_time / 3600;
                                    $hours_rounded = floor($waiting_time / 3600); 
                                    $waiting_minutes = ($waiting_hours - $hours_rounded) * 60;
                                    $minutes_rounded = floor($waiting_minutes);
                                    $waiting_seconds = ($waiting_minutes - $minutes_rounded) * 60;
                                    $seconds_rounded = floor($waiting_seconds);

                                    $format_waiting_time = $hours_rounded . ":" . $minutes_rounded . ":" . $seconds_rounded;

                                    ?>
                                    <tr>

                                    

                                        <td style="text-align: center; padding: 10px;">{{$session_info->guest_type}}</td>
                                        <td style="text-align: center; padding: 10px;">{{$session_info->guest_name}}</td>
                                        <td style="text-align: center; padding: 10px;">{{$session_info->room}}</td>
                                        <td style="text-align: center; padding: 10px;">{{$session_info->mobile_number}}</td>
                                        <td style="text-align: center; padding: 10px;">{{$real_start_time}}</td>
                                        <td style="text-align: center; padding: 10px;">{{$format_waiting_time}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</center>
</body>

</html>
