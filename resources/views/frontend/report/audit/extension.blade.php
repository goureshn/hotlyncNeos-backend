<div style="margin-top: 5px">
    <p style="margin: 0px"><b>Admin &nbsp;&nbsp;Total : {{count($data['admin'])}}</b> </p>
    <table class="grid" style="width : 100%">
        <thead >
        <tr style="background-color: #c2dbec;">
            <th  width="10%"><b>Department</b></th>
            <th  width="10%"><b>Section</b></th>
            <th  width="10%"><b>User</b></th>
            <th  width="10%"><b>Extension</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['admin'] as  $key => $row)
            <tr class="">
                <td>{{$row->department}}</td>
                <td>{{$row->section}}</td>
                <td>{{$row->user}}</td>
                <td>{{$row->extension}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<br>
<div style="margin-top: 5px">
    <p style="margin: 0px"><b>Guest &nbsp;&nbsp; Total : {{count($data['guest'])}}</b> </p>
    <table class="grid" style="width : 100%">
        <thead >
        <tr style="background-color: #c2dbec;">
            <th  width="10%"><b>Building</b></th>
            <th  width="10%"><b>Room Number</b></th>
            <th  width="10%"><b>Extension</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['guest'] as  $key => $row)
            <tr class="">
                <td>{{$row->name}}</td>
                <td>{{$row->room}}</td>
                <td>{{$row->extension}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
