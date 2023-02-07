<?php
$status_name = [" ", "Acknowledge", "Update", "Clear"];
?>
<div style="margin-top: 20px">   
@foreach ($data['log_list'] as $notification)
<div style="margin-top: 20px">   
    <table style="width : 100%; border-style : hidden!important;">
        <tbody> 
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Alarm Name :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" width="30%">
                    {{$notification->alarm_name}}                    
                </td>              
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Date&Time :&nbsp;&nbsp;</span></td>
                <td style="border-style : hidden!important;" width="30%">         
                    {{date('d M Y H:i:s', strtotime($notification->created_at))}}      
                </td>  
            </tr>   
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Description :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" colspan="9"> 
                    {{$notification->updated_description}}                    
                </td>                              
            </tr>
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Comment :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" colspan="9">
                    {{$notification->message}}                    
                </td>                              
            </tr>
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Status :&nbsp;&nbsp;<span></td>                
                <td style="border-style : hidden!important;" width="30%">  
                    {{$status_name[$notification->status]}}                
                </td>               
            </tr>
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Clear Notification :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" colspan="9">     
                    {{$notification->clear_message}}                
                </td>
            </tr>
            <tr>             
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Cleared By :&nbsp;&nbsp;</span></td>
                <td style="border-style : hidden!important;" width="30%">
                    {{$notification->clear_user_name}}                               
                </td>
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Cleared Date&Time :&nbsp;&nbsp;</span></td>
                <td style="border-style : hidden!important;" width="30%">
                @if ($notification->clear_at != '0000-00-00 00:00:00' && !empty($notification->clear_at) )
                {{date('d M Y H:i:s', strtotime($notification->clear_at))}}  
                @endif                           
                </td>  
            </tr>
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Location :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" width="30%">
                    {{$notification->location}}                    
                </td>                   
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">Created By :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" width="30%">
                    {{$notification->sender_user_name}}                    
                </td>                              
            </tr>
            <tr>                    
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">No. of Users :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" width="30%">
                    {{$notification->notify_count}}
                </td>                                                 
                <td style="border-style : hidden!important;" align="right" width="20%"><span style="font-weight:bold;">ACK received :&nbsp;&nbsp;</span></td>                
                <td style="border-style : hidden!important;" width="30%">
                    {{$notification->ack_count}}
                </td>                              
            </tr>
        </tbody>
    </table>
</div>
</div>
<div style="margin-top: 10px">       
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#2c3e50, color: #fff">
            <tr class="plain">                                    
                <th width="20%" align="center"><b>Recipient</b></th>
                <th width="20%" align="center"><b>Status</b></th>                     
                <th width="20%" align="center"><b>Notification Type</b></th>
                <th width="20%" align="center"><b>ACK Type</b></th>                
                <th width="20%" align="center"><b>ACK Date&Time</b></th>                
            </tr>   
        </thead>
        <tbody>
        @foreach($notification->user_list as $row)
            <tr class="plain">                    
                <td align="left">{{$row->user_name}}</td>
                <td align="center">{{$row->status_name . ($row->kind == 1 ? " (Escalation $row->escal_count/$notification->retry_count)" : "")}}</td>                
                <td align="center">{{implode(",", $row->notify_type_list)}}</td>
                <td align="center">{{$row->max_status >= 1 ? implode(",", $row->ack_type_list) : ""}}</td>
                <td align="center">{{$row->max_status >= 1 && !empty($row->ack_date) ? date('d M Y', strtotime($row->ack_date)): ""}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if( count($notification->update_list) > 0 ) 
<div style="margin-top: 10px">   
    <p>Update User List</p>
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#2c3e50, color: #fff">
            <tr class="plain">                                    
                <th align="center"><b>Date and Time</b></th>
                <th align="center"><b>Update Comment</b></th>                     
                <th align="center"><b>User</b></th>
                <th align="center"><b>Send to Users</b></th>                
            </tr>   
        </thead>
        <tbody>
        @foreach($notification->update_list as $row)
            <tr class="plain">                    
                <td align="center">{{date('d M Y H:i:s', strtotime($row->created_at))}}</td>                
                <td align="center">{{$row->message}}</td>
                <td align="center">{{$row->sender_user_name}}</td>
                <td align="center">{{$row->send_flag}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<style>
    .dotted {border: 2px dotted #808080; border-style: none none dotted; color: #fff; background-color: #fff; }
</style>
<hr class='dotted' />
@endforeach
