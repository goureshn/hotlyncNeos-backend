<br/><br/>
<div>
    <table class="grid" style="width : 100%;">
        <thead>
        <tr>
            <th><b>Agent ID</b></th>
            <th><b>Agent Name</b></th>
            <th><b>Activity Time</b></th>
            <th><b>Activity Type</b></th>
            <th><b>Activity Detail</b></th>
            <th><b>Call Type</b></th>
            <th><b>Caller Number</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['detail'] as $key => $row)
            <tr class="">
                <td>{{$row->id}}</td>
                <td>{{$row->wholename}}</td>
                <td>{{$row->created_at}}</td>
                @if( $row->status == 'Ringing' || $row->status == 'Answered' || $row->status == 'Abandoned' || $row->status == 'Callback' || $row->status == 'Modify')
                    <td>CALL</td>
                @else
                    <td>STATE</td>
                @endif
                <td>{{$row->status}}</td>
                <td>{{$row->call_type}}</td>
                <td>{{$row->callerid}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top: 70px">
        <p align="center"; style="font-size:14px; margin-top:0; text-align: center">Agent Activity Duration</p>

    </div>

    <table class="grid" style="width : 100%;">
        <thead>
        <tr>
        @foreach($data['summary'] as $row)
            <th><b>{{$row->status}}</b></th>
        @endforeach
        </tr>
        </thead>
        <tbody>
            <tr class="">
                @foreach($data['summary'] as $row)
                    <td>{{$row->duration}}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>