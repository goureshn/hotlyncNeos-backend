<br/><br/>
<style>
    .highlight {
        background: yellow;
    }
</style>

<div>
    <table  style="width : 100%" border="0">
        <tbody >
        <tr style="height:35px;border:0">
            <td  style="width:100%;border: 0; text-align: center; font-size: 13px;">
                <strong>Engineering Request Report</strong>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<div>
    <table  style="width : 100%">
        <tbody>
        @foreach($data['request_list'] as $row)
            <tr class="">
                <td style="width: 15%">
                    <li class="red"><b>{{sprintf('E%05d', $row->id)}}</b></li>
                    <li>{{date('d M Y', strtotime($row->created_at))}}</li>
                    <li>{{date('H:i', strtotime($row->created_at))}}</li>

                    @if($row->status == 'Rejected')
                        <li class="rejected"><b>
                    @elseif($row->status == 'Pending')
                        <li class="resolved"><b>
                    @elseif($row->status == 'Completed')
                        <li class="completed"><b>
                    @else
                        <li><b>
                    @endif
                          {{$row->status}}
                         </b></li>
                </td>
                <td>
                    <li><label class="yellow">&#9899;</label><b>Raised by: </b>{{$row->wholename}}[{{$row->job_role}}]</li>
                    <li>
                        <label class="red">&#9899;</label><b>Engineering request Location: </b>{{$row->lgm_name}} - {{$row->lgm_type}}
                        &nbsp;&nbsp;<b>Category:</b> {{$row->category_name}}
                        &nbsp;&nbsp;<b>Sub Category:</b> {{$row->sub_category_name}}
                    </li>
                    @if($row->status == 'Rejected')
                        <li>
                            <label class="yellow">&#9899;</label>Update: [{{date('d-M-Y H:i', strtotime($row->updated_at))}}]
                        </li>
                    @elseif ($row->status == 'Completed')
                        <li>
                            <label class="green">&#9899;</label>Update: [{{date('d-M-Y H:i', strtotime($row->updated_at))}}]
                        </li>
                    @else
                        <li>
                            <label>&#9899;</label>Update: [{{date('d-M-Y H:i', strtotime($row->updated_at))}}]
                        </li>
                    @endif
                    <li><label class="red">&#9899;</label><b>Category: </b>{{$row->lgm_name}} - {{$row->lgm_type}}</li>
                    <li><label>&#9899;</label><b>Subject: </b>{{$row->subject}}</li>
                    <li><label>&#9899;</label><b>Issue: </b>{{$row->comment}}</li>
                </td>
            </tr>
        @endforeach

        <tr class="">
            <td style="width: 15%">
                <li>Total</li>
                <li>Request:  {{count($data['request_list'])}}</li>
            </td>
        </tr>
        </tbody>
    </table>
</div>

