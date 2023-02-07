<br/>
<?php
function timetostr($duration) {
    $hour = 0;
    $min = 0;
    $sec = 0;

    if($duration <= 0 ) return '00:00:00';

    $hour = floor($duration / 3600);
    $mod = $duration % 3600;
    if($mod < 60) {
        $sec = $mod;
    }else  {
        $min = floor($mod / 60);
        $sec = $mod % 60 ;
    }
    if($hour == 0)  {
        if($min == 0) return "00:00:".timeStr($sec);
        else return "00:".timeStr($min).":".timeStr($sec);
    }else {
        return timeStr($hour).":".timeStr($min).":".timeStr($sec);
    }
 }

  function timeStr($val) {
     return sprintf("%'.02d", $val);
  }
?>
@if(!empty($data['summary_report']))
    <p style="margin: 0px;margin-top :10px"  class="table-header"><b>Summary</b></p>
    <div style="margin-top: 5px">
    <table class="grid" style="width : 100%">
        <thead >
        <tr style="background-color: #c2dbec;">
            <th><b>Staff</b></th>
            <th><b>Job Role</b></th>
            <th><b>Tasks Assigned</b></th>
            <th><b>On time (%)</b></th>
            <th><b>Duration</b></th>
 <th><b>Time on Task</b></th>
			<th><b>Utilization (%)</b></th>
        </tr>
        </thead>
        <tbody>
            @foreach ($data['summary_report'] as  $key => $data_group)

                <tr class="">
                    <td>{{$key}}</td>
                    <td>{{$data_group['job_role']}}</td>
                    <td align="right">{{$data_group['total']}}</td>
                    <td align="right">{{$data_group['ontime']}} ({{$data_group['percent']}}%)</td>
                    <td align="right">{{timetostr($data_group['duration'])}}</td>
                    <td align="right">{{timetostr($data_group['timeontask'])}}</td>
		            <td align="right">{{$data_group['utilization']}}%</td>
                </tr>

            @endforeach
        </tbody>

    </table>
    </div>
@endif
@if(!empty($data['detail_report']))
    <p style="margin: 0px;margin-top :10px" class="table-header"><b>Details</b></p>
    <div style="margin: 0px">
        <table class="grid" style="width : 100%">
            <thead >
            <tr style="background-color: #c2dbec;">
                <th><b>Date</b></th>
                <th><b>Staff</b></th>
                <th><b>Job Role</b></th>
                <th><b>Login Time</b></th>
                <th><b>Logout Time</b></th>
                <th><b>Duration</b></th>
            </tr>
            </thead>
            @foreach ($data['detail_report'] as  $key => $data_group)
                <?php
                $before_key = '';
                $before_job_role = '';
                $before_staff = '';
                $count_number = 0;
                $duration_sum = 0;
                ?>
                <tbody>
                @foreach ($data_group as $row)
                    <tr class="">
                        <td>
                            @if($before_key != $key)
                                {{date("d-M-Y", strtotime($key))}}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td>
                            @if($before_staff != $row['staffname'] )
                                {{$row['staffname']}}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td>
                            @if($before_job_role != $row['job_role'] )
                                {{$row['job_role']}}
                            @elseif($before_staff != $row['staffname'])
                                {{$row['job_role']}}
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td align="right">{{ date("d-M-y H:i", strtotime($row['login_time']))}}</td>
                        <td align="right">{{ date("d-M-y H:i", strtotime($row['logout_time']))}}</td>
                        <td align="right">{{timetostr($row['duration'])}}</td>
                    </tr>
                    <?php
                    $duration = $row['duration'];
                    $duration_sum = $duration + $duration_sum;
                    ?>
                    @if(!empty($data_group[$count_number+1]) && $row['staffname'] != $data_group[$count_number+1]['staffname'])
                        <tr class="">
                            <td colspan="4">&nbsp;</td>
                            <td align="right"><b>Total</b></td>
                            <td align="right">
                                {{timetostr($duration_sum)}}
                                <?php
                                $duration_sum = 0 ;
                                 ?>
                            </td>
                        </tr>
                    @elseif(count($data_group) == $count_number+1 )
                        <tr class="">
                            <td colspan="4">&nbsp;</td>
                            <td align="right"><b>Total</b></td>
                            <td align="right">
                                {{timetostr($duration_sum)}}
                                <?php
                                $duration_sum = 0;
                                ?>
                            </td>
                        </tr>
                    @endif
                    <?php
                    $count_number++;
                    $before_key = $key;
                    $before_job_role = $row['job_role'];
                    $before_staff = $row['staffname'];
                    ?>
                @endforeach
                </tbody>
            @endforeach
        </table>
    </div>
@endif

