<?php
 $extension_call = $data['guest_extension_call'];
 $duration_call = $data['guest_duraion_call'];
 $charge_call = $data['guest_charge_call'];
 $extension_list = $data['guest_extension_list'];
 $admin_admin_extension_receive_list = $data['admin_admin_extension_receive_list'];
 $admin_guest_extension_receive_list = $data['admin_guest_extension_receive_list'];
 $guest_guest_extension_receive_list = $data['guest_guest_extension_receive_list'];
 $guest_admin_extension_receive_list = $data['guest_admin_extension_receive_list'];
 $title_show = false ;
 if(!empty($extension_call))      $title_show = true;
?>
<br/>
    <div style="margin-top: 10px">
        <table class="grid" style="width : 100%">
            <thead >
            @if($title_show == true)
            <tr style="">
                <th width="10%"><b>&nbsp;</b></th>
                <th width="10%"><b>Intl</b></th>
                <th width="10%"><b>Mobile</b></th>
                <th width="10%"><b>Local</b></th>
                <th width="10%"><b>National</b></th>
                <th width="10%"><b>Internal</b></th>
                <th width="10%"><b>Incoming</b></th>
                <th width="10%" colspan="2"><b>Grand Total</b></th>
            </tr>
            @endif
            </thead>
        </table>
        @if(!empty($extension_call))
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width="10%">Calls</td>
                        <td width="10%" class="right">{{$extension_call->International}}</td>
                        <td width="10%" class="right">{{$extension_call->Mobile}}</td>
                        <td width="10%" class="right">{{$extension_call->Local}}</td>
                        <td width="10%" class="right">{{$extension_call->National}}</td>
                        <td width="10%" class="right">{{$extension_call->Internal}}</td>
                        <td width="10%" class="right">{{$extension_call->Incoming}}</td>
                        <td width="10%" colspan="2" class="right">{{$extension_call->Grand}}</td>
                    </tr>
                </tbody>
            </table>
        @endif
        @if(!empty($duration_call))
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width="10%">Duration</td>
                        <td width="10%" class="right">{{$duration_call->International}}</td>
                        <td width="10%" class="right">{{$duration_call->Mobile}}</td>
                        <td width="10%" class="right">{{$duration_call->Local}}</td>
                        <td width="10%" class="right">{{$duration_call->National}}</td>
                        <td width="10%" class="right">{{$duration_call->Internal}}</td>
                        <td width="10%" class="right">{{$duration_call->Incoming}}</td>
                        <td width="10%" colspan="2" class="right">{{$duration_call->Grand}}</td>
                    </tr>
                </tbody>
            </table>
        @endif
        @if(!empty($charge_call))
            <table class="grid" style="width : 100%">
                <tbody>
                    <tr class="">
                        <td width="10%">Charges(Aed)</td>
                        <td width="10%" class="right">{{$charge_call->International}}</td>
                        <td width="10%" class="right">{{$charge_call->Mobile}}</td>
                        <td width="10%" class="right">{{$charge_call->Local}}</td>
                        <td width="10%" class="right">{{$charge_call->National}}</td>
                        <td width="10%" class="right">{{$charge_call->Internal}}</td>
                        <td width="10%" class="right">{{$charge_call->Incoming}}</td>
                        <td width="10%" colspan="2" class="right">{{$charge_call->Grand}}</td>
                    </tr>
                </tbody>
            </table>
        @endif
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="">
                <th width="10%"><b>Extension</b></th>
                <th width="10%"><b>Name</b></th>
                <th width="10%"><b>Department</b></th>
                <th width="10%" ><b>Intl(Call/Total)</b></th>
                <th width="10%" ><b>Mobile(Call/Total)</b></th>
                <th width="10%" ><b>Local(Call/Total)</b></th>
                <th width="10%" ><b>National(Call/Total)</b></th>
                <th width="10%" ><b>Internal(Call/Total)</b></th>
                <th width="10%" ><b>Incoming(Call/Total)</b></th>
                <th width="10%" ><b>Total Call</b></th>
                <th width="10%" ><b>Total Charge</b></th>
            </tr>
            </thead>
        </table>
            @foreach ($extension_list as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                    <tr class="">
                        <td width="10%">{{$row->extension}}</td>
                        <td width="10%">{{$row->description}}</td>
                        <td width="10%">{{$row->department}}</td>
                        <td width="10%" class="right">{{$row->International_count}}/{{$row->International}}</td>
                        <td width="10%" class="right">{{$row->Mobile_count}}/{{$row->Mobile}}</td>
                        <td width="10%" class="right">{{$row->Local_count}}/{{$row->Local}}</td>
                        <td width="10%" class="right">{{$row->National_count}}/{{$row->National}}</td>
                        <td width="10%" class="right">{{$row->Internal_count}}/{{$row->Internal}}</td>
                        <td width="10%" class="right">{{$row->Incoming_count}}/{{$row->Incoming}}</td>
                        <td width="10%" class="right">{{$row->Grand_count}}</td>
                        <td width="10%" class="right">{{$row->Grand}}</td>
                    </tr>
                    </tbody>
                </table>
            @endforeach
        <table>
            <thead class="grid" style="width : 100%">
            <tr style="">
                <th width="10%"><b>Extension</b></th>
                <th width="10%"><b>Received From</b></th>
                <th width="10%"><b>Received Call</b></th>
            </tr>
            </thead>
        </table>
            @foreach ($admin_admin_extension_receive_list as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                    <tr class="">
                        <td width="10%">{{$row->extension}}</td>
                        <td width="10%">Admin</td>
                        <td width="10%" class="right">{{$row->Receive_count}}</td>
                    </tr>
                    </tbody>
                </table>
            @endforeach
            
             @foreach ($admin_guest_extension_receive_list as $row)
                 <table class="grid" style="width : 100%">
                     <tbody>
                     <tr class="">
                         <td width="10%">{{$row->extension}}</td>
                         <td width="10%">Guest</td>
                         <td width="10%" class="right">{{$row->Receive_count}}</td>
                     </tr>
                     </tbody>
                 </table>
            @endforeach
            @foreach ($guest_guest_extension_receive_list as $row)
                <table class="grid" style="width : 100%">
                    <tbody>
                    <tr class="">
                        <td width="10%">{{$row->extension}}</td>
                        <td width="10%">Guest</td>
                        <td width="10%" class="right">{{$row->Receive_count}}</td>
                    </tr>
                    </tbody>
                </table>
            @endforeach

            @foreach ($guest_admin_extension_receive_list as $row)
             <table class="grid" style="width : 100%">
                 <tbody>
                 <tr class="">
                     <td width="10%">{{$row->extension}}</td>
                     <td width="10%">Admin</td>
                     <td width="10%" class="right">{{$row->Receive_count}}</td>
                 </tr>
                 </tbody>
             </table>
            @endforeach
    </div>
