<?php
$count =0;
?>
@foreach ($data['data_list'] as  $key => $data)
    <div style="margin-top: 5px">
        <p style="margin: 0px"><b>{{$data['title']}} &nbsp;&nbsp;&nbsp;Total :{{count($data['detail'])}}</b> </p>
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
                <th width="10%"><b>First Name</b></th>
                <th width="10%"><b>Last Name</b></th>
                <th width="10%"><b>Username</b></th>
                <th width="10%"><b>Department</b></th>
                <th width="10%"><b>Mobile</b></th>
                <th width="10%"><b>Email</b></th>
                <th width="10%"><b>Permission</b></th>
                <th width="10%"><b>Status</b></th>
            </tr>
            </thead>
            <?php $count += count($data['detail']); ?>
            <tbody>
            @foreach ($data['detail'] as $row)
                <tr class="">
                    <td>{{$row->first_name}}</td>
                    <td>{{$row->last_name}}</td>
                    <td>{{$row->username}}</td>
                    <td>{{$row->department}}</td>
                    <td>{{$row->mobile}}</td>
                    <td>{{$row->email}}</td>
                    <td>{{$row->permission}}</td>
                    <td>{{$row->lock}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endforeach
<p style="margin: 0px"><b>Total : {{$count}}</b></p>