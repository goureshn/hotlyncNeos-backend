<div style="margin-top: 30px">
    <table class=""  style="width : 100%;">
        <thead  >
            <tr>
                <th width=10%><b>Pending</b></th>
                <th width=10%><b>In Progress</b></th>
                <th width=10%><b>On Hold</b></th>
                <th width=10%><b>Completed</b></th>
                <th width=10%><b>Total</b></th>
            </tr>
        </thead>
        <tbody>
               <tr class="">
                   <td class="left">{{$data['subcount']->pending}}</td>
                   <td class="left">{{$data['subcount']->progress}}</td>
                   <td class="left">{{$data['subcount']->hold}}</td>
                   <td class="left">{{$data['subcount']->completed}}</td>
                   <td class="left">{{$data['subcount']->total}}</td>
                </tr>
        </tbody>
    </table>

</div>

@if ($data['report_type'] == 'Detailed')

<div style="margin-top: 30px">
    <table class=""  style="width : 100%;">
        <thead  >
            <tr>
                <th width=10%><b>ID</b></th>
                <th width=10%><b>Name</b></th>
                <th width=10%><b>Description</b></th>
                <th width=10%><b>Priority</b></th>
                <th width=10%><b>Type</b></th>
                <th width=10%><b>Status</b></th>
                <th width=10%><b>Equipment</b></th>
                <th width=10%><b>Location</b></th>
                <th width=10%><b>Start Date</b></th>
                <th width=10%><b>End Date</b></th>
                <th width=10%><b>Total Time</b></th>
                <th width=10%><b>Actual Time</b></th>
                <th width=10%><b>Assigned Staff</b></th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            $total_mount = 0;
            ?>
            @foreach ($data['datalist'] as  $key => $row)
               <tr class="">
                   <td class="left">{{$row->wo_id}}</td>
                   <td class="center">{{$row->name}}</td>
                   <td class="left">{{$row->description}}</td>
                   <td class="left">{{$row->priority}}</td>
                   <td class="left">{{$row->work_order_type}}</td>
                   <td class="left">{{$row->status}}</td>
                   <td class="left">{{$row->eq_id}} - {{$row->equipment_name}}</td>
                   <td class="left">{{$row->location_name}} - {{$row->location_type}}</td>
                   @if ($row->status != 'Pending')
                        <td class="left">{{$row->start_date}}</td>
                   @else
                        <td></td>
                   @endif
                   @if ($row->status == 'Completed')
                        <td class="left">{{$row->end_date}}</td>
                   @else
                        <td></td>
                   @endif
                   <td class="left">{{$row->time_spent}}</td>
                   @if ($row->hold_time != '') 
                        <td class="left">{{gmdate('H:i:s' , $row->actual_time)}}</td>
                   @else
                        <td class="left">{{$row->time_spent}}</td>
                   @endif
                   <td class="left">{{$row->assigne_list_names}}</td>
                </tr>
				
            @endforeach
        </tbody>
    </table>

</div>
@endif
