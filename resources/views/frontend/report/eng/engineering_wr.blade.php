<div style="margin-top: 30px">
    <table class=""  style="width : 100%;">
        <thead  >
            <tr>
                <th width=10%><b>Pending</b></th>
                <th width=10%><b>Assigned</b></th>
                <th width=10%><b>In Progress</b></th>
                <th width=10%><b>On Hold</b></th>
                <th width=10%><b>Completed</b></th>
                <th width=10%><b>Closed</b></th>
                <th width=10%><b>Rejected</b></th>
                <th width=10%><b>Total</b></th>
            </tr>
        </thead>
        <tbody>
               <tr class="">
                   <td class="left">{{$data['subcount']->pending}}</td>
                   <td class="center">{{$data['subcount']->assigned}}</td>
                   <td class="left">{{$data['subcount']->progress}}</td>
                   <td class="left">{{$data['subcount']->hold}}</td>
                   <td class="left">{{$data['subcount']->completed}}</td>
                   <td class="left">{{$data['subcount']->closed}}</td>
                   <td class="left">{{$data['subcount']->rejected}}</td>
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
                <th width=10%><b>Created Date</b></th>
                <th width=10%><b>Requestor</b></th>
                <th width=10%><b>Priority</b></th>
                <th width=10%><b>Category</b></th>
                <th width=10%><b>Location</b></th>
                <th width=10%><b>Summary</b></th>
                <th width=10%><b>Status</b></th>
                <th width=10%><b>Start Date</b></th>
                <th width=10%><b>End Date</b></th>
                <th width=10%><b>Equipment</b></th>
                <th width=10%><b>Assignee</b></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_mount = 0;
            ?>
            @foreach ($data['datalist'] as  $key => $row)
               <tr class="">
                   <td class="left">{{$row->wr_id}}</td>
                   <td class="center">{{$row->created_at}}</td>
                   <td class="left">{{$row->requestor_name}}</td>
                   <td class="left">{{$row->priority}}</td>
                   <td class="left">{{$row->category_name}}</td>
                   <td class="left">{{$row->location_name}} - {{$row->location_type}}</td>
                   <td class="left">{{$row->repair}}</td>
                   <td class="left">{{$row->status_name}}</td>
                   @if (($row->status_name != 'Pending') || ($row->status != 'Assigned'))
                        <td class="left">{{$row->start_date}}</td>
                   @else
                        <td></td>
                   @endif
                   @if ($row->status_name == 'Completed' || $row->status_name == 'Closed')
                        <td class="left">{{$row->end_date}}</td>
                   @else
                        <td></td>
                   @endif
                   <td class="left">{{$row->equip_id}} - {{$row->equip_name}}</td>
                   @if ($row->supplier_id > 0)
                        <td class="left">{{$row->supplier}}</td>
                   @else
                        <td class="left">{{$row->assignee_name}}</td>
                   @endif
                  
                </tr>
				
            @endforeach
        </tbody>
    </table>

</div>

@endif

@if ($data['wo_flag'] == 'true')

<div style="margin-top:10px;position: absolute;width: 95%;" align="center">
		
			<p align="center"; style="font-size:9px; margin-top:0; text-align: center">Work Order Report</p>
		
</div>
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
                   <td class="left">{{$data['wo_subcount']->pending}}</td>
                   <td class="left">{{$data['wo_subcount']->progress}}</td>
                   <td class="left">{{$data['wo_subcount']->hold}}</td>
                   <td class="left">{{$data['wo_subcount']->completed}}</td>
                   <td class="left">{{$data['wo_subcount']->total}}</td>
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
                <th width=5%><b>Type</b></th>
                <th width=10%><b>WR ID</b></th>
                <th width=10%><b>Status</b></th>
                <th width=10%><b>Equipment</b></th>
                <th width=10%><b>Location</b></th>
                <th width=10%><b>Start Date</b></th>
                <th width=10%><b>End Date</b></th>
                <th width=10%><b>Total Time</b></th>
            {{--   <th width=10%><b>Actual Time</b></th> --}}
                <th width=10%><b>Assigned Staff</b></th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            $total_mount = 0;
            ?>
            @foreach ($data['wo_datalist'] as  $key => $row)
               <tr class="">
                   <td class="left">{{$row->wo_id}}</td>
                   <td class="center">{{$row->name}}</td>
                   <td class="left">{{$row->description}}</td>
                   <td class="left">{{$row->priority}}</td>
                   <td class="left">{{$row->work_order_type}}</td>
                   <td class="left">{{$row->ref_id}}</td>
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
                   
                   <td class="left">{{$row->assigne_list_names}}</td>
                </tr>
				
            @endforeach
        </tbody>
    </table>

</div>
@endif




@endif

