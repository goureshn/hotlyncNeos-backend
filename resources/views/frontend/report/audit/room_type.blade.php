
<?php
$count =0;
?>
@foreach ($data['data_list'] as  $key => $data)
    <div style="margin-top: 5px">
        <p style="margin: 0px"><b>{{$data['title']}} &nbsp;&nbsp;&nbsp;Total :{{count($data['detail'])}}</b> </p>
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
                <th width="10%"><b>Building</b></th>
                <th width="10%"><b>Floor</b></th>
                <th width="10%"><b>Room Number</b></th>
            </tr>
            </thead>
            <tbody>
            <?php $count += count($data['detail']); ?>
            @foreach ($data['detail'] as $row)
                <tr class="">
                    <td>{{$row->building}}</td>
                    <td>{{$row->floor}}</td>
                    <td>{{$row->room}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endforeach
<p style="margin: 0px"><b>Total : {{$count}}</b></p>