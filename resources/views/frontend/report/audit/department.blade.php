
<?php
 $count =0;
?>
<div style="margin-top: 5px">
@foreach ($data['data_list'] as  $key => $data)
        <p style="margin-bottom: 2px"><b>{{$data['title']}} &nbsp;&nbsp;&nbsp;Total User :{{count($data['detail'])}}</b> </p>
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
                <th width="10%"><b>First Name</b></th>
                <th width="10%"><b>Last Name</b></th>
                <th width="10%"><b>Username</b></th>
                <th width="10%"><b>Job Role</b></th>
                <th width="10%"><b>Mobile</b></th>
                <th width="10%"><b>Email</b></th>
                <th width="10%"><b>Permission</b></th>
                <th width="10%"><b>Status</b></th>
            </tr>
            </thead>
            <tbody>
            <?php $count += count($data['detail']); ?>
            @foreach ($data['detail'] as $row)
                <tr class="">
                    <td>{{$row->first_name}}</td>
                    <td>{{$row->last_name}}</td>
                    <td>{{$row->username}}</td>
                    <td>{{$row->job_role}}</td>
                    <td>{{$row->mobile}}</td>
                    <td>{{$row->email}}</td>
                    <td>{{$row->permission}}</td>
                    <td>{{$row->lock}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
@endforeach
      <p style="margin: 0px"><b>Total User: {{$count}}</b></p>
</div>