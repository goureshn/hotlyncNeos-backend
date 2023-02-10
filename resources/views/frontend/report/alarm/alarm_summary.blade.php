<?php
$status_name = [" ", "Acknowledge", "Update", "Clear"];
$prev_date = '';
?>
@foreach ($data['log_list'] as $notification)
<?php
    $created_date = date('d-M-y', strtotime($notification->created_at));
    if( $created_date != $prev_date)
    {
        $prev_date = $created_date;
?>
            </tbody>
        </table>
    </div>
    <div style="margin-top: 20px">
        <p>{{$created_date}} Alarms</p>
        <table  border="0" style="width : 100%;">
            <thead style="background-color:#2c3e50, color: #fff">
                <tr class="plain">                                    
                    <th align="center"><b>Alarm</b></th>
                    <th align="center"><b>Time</b></th>                     
                    <th align="center"><b>Created by</b></th>
                    <th align="center"><b>Comment</b></th>                
                    <th align="center"><b>Location</b></th>                
                    <th align="center"><b>Ack</b></th>                
                    <th align="center"><b>Cleared By</b></th>                
                    <th align="center"><b>Cleared On</b></th>                
                </tr>   
            </thead>
            <tbody>
<?php
    }
?>                    
            <tr class="plain">                    
                <td align="center">{{$notification->alarm_name}}</td>                
                <td align="center">{{date("H:i", strtotime($notification->created_at))}}</td>                
                <td align="center">{{$notification->sender_user_name}}</td>                
                <td align="left">{{$notification->message}}</td>                
                <td align="center">{{$notification->location}}</td>                
                <td align="center">{{$notification->ack_count}}/{{$notification->notify_count}}</td>                
                <td align="center">{{$notification->clear_user_name}}</td>                                <td align="center">{{empty($notification->clear_at)  ? '' : date('d-M-y', strtotime($notification->clear_at))}}</td>                                
            </tr>  

@endforeach
        </tbody>
    </table>
</div>