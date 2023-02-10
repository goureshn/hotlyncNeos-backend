<?php
$grand_total  = 0;
$grand_totalcharge = 0;
?>
@if(!empty($data['by_dept_data']))
<div style="margin-top: 15px">
   
    <table class="" style="width : 100%">
        <thead>
        <tr>
                <th><b>Department</b></th>
                <th><b>Approved(Call)/Charge</b></th>
                <th><b>Rejected(Call)/Charge</b></th>
                <th><b>Awaiting(Call)/Charge</b></th>
                <th><b>Unmarked(Call)/Charge</b></th>
                <th><b>Total Calls</b></th>
                <th><b>Total Amount</b></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['by_dept_data'] as  $key => $row)
            <?php
                                $row_total = $row['unmarkedcount'] + $row['approvedcount'] + $row['awaitingcount'] + $row['unapprovedcount'];
                                $row_totalcharge =  $row['approvedcharge'] + $row['unapprovedcharge'] + $row['awaitingcharge'] + $row['unmarkedcharge'];
                                ?>
            <tr class="">
                <td>{{$row['name']}}</td>
                <td class="right">{{$row['approvedcount']}}/{{number_format($row['approvedcharge'],2)}}</td>
                <td class="right">{{$row['unapprovedcount']}}/{{number_format($row['unapprovedcharge'],2)}}</td>
                <td class="right">{{$row['awaitingcount']}}/{{number_format($row['awaitingcharge'],2)}}</td>
                <td class="right">{{$row['unmarkedcount']}}/{{number_format($row['unmarkedcharge'], 2)}}</td>
                <td class="right">{{$row_total}}</td>
                <td class="right">{{number_format($row_totalcharge,2)}}</td>
               
            </tr>
            <?php
                                $grand_total += $row_total;
                                $grand_totalcharge +=  $row_totalcharge;
            ?>

        @endforeach
        <tr class="total-amount">
            <td align="right"><b>Total</b></td>
            <td align="right"><b>{{$data['total_value']['approvedcount']}}/{{number_format($data['total_value']['approvedcharge'], 2)}}</b></td>
            <td align="right"><b>{{$data['total_value']['unapprovedcount']}}/{{number_format($data['total_value']['unapprovedcharge'], 2)}}</b></td>
            <td align="right"><b>{{$data['total_value']['awaitingcount']}}/{{number_format($data['total_value']['awaitingcharge'], 2)}}</b></td>
            <td align="right"><b>{{$data['total_value']['unmarkedcount']}}/{{number_format($data['total_value']['unmarkedcharge'], 2)}}</b></td>
            <td align="right"><b>{{$grand_total}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_totalcharge, 2)}}</b></td>
           
        </tr>
        </tbody>
    </table>
</div>
@endif