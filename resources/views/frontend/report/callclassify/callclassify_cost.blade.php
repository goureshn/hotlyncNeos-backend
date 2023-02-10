@if(($data['report_type'] == 'Detailed') && ($data['report_by'] == 'Cost Comparison'))
@if(!empty($data['by_user_data']))
@foreach ($data['by_user_data'] as  $key => $data_group)
<div style="margin-top: 15px">
<?php
           $monthName = date("F", mktime(0, 0, 0, $key, 10));
?>
<b style="margin-bottom: 5px">{{$monthName}}</b>
    <table class="" style="width : 100%">
        <thead>
        <tr>
                <th><b>User</b></th>
           
                <th><b>Extension</b></th>
           
                <th><b>Mobile</b></th>
          
                <th><b>Department</b></th>
                <th><b>Personal</b></th>
                <th><b>Business</b></th>
                <th><b>Unclassified</b></th>
                <th><b>Total Cost</b></th>
        </tr>
        </thead>
        <?php
            $grand_per  = 0;
            $grand_bus  = 0;
            $grand_unclassify  = 0;
            $grand_totalcharge = 0;
        ?>
        <tbody>
        @foreach ($data_group as $row)
            <?php
                                
                $row_totalcharge =  $row['personal'] + $row['business'] + $row['unclassified'];
             ?>
            <tr class="">
                <td>{{$row['name']}}</td>
              
                    <td>{{$row['extension']}}</td>
              
                    <td>{{$row['mobile']}}</td>
             
                <td>{{$row['department']}}</td>
                <td class="right">{{number_format($row['personal'],2)}}</td>
                <td class="right">{{number_format($row['business'],2)}}</td>
                <td class="right">{{number_format($row['unclassified'],2)}}</td>
                <td class="right">{{number_format($row_totalcharge,2)}}</td>
               
            </tr>
            <?php
                $grand_per += $row['personal'];
                $grand_bus += $row['business'];    
                $grand_unclassify += $row['unclassified'];        
                $grand_totalcharge +=  $row_totalcharge;
            ?>

        @endforeach
       
        <tr class="total-amount">
            <td align="right"></td>
          
                <td align="right"></td>
          
                <td align="right"></td>
         
            <td align="right"><b>Total</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_per, 2)}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_bus, 2)}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_unclassify, 2)}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_totalcharge, 2)}}</b></td>
           
        </tr>
     
        </tbody>
    </table>
</div>
@endforeach
@endif









@else

@if($data['report_by'] == 'Cost Comparison')
@if(!empty($data['by_dept_data']))
@foreach ($data['by_dept_data'] as  $key => $data_group)
<div style="margin-top: 15px">
<?php
           $monthName = date("F", mktime(0, 0, 0, $key, 10));
?>
<b style="margin-bottom: 5px">{{$monthName}}</b>
    <table class="" style="width : 100%">
        <thead>
        <tr>
                <th><b>Department</b></th>
                <th><b>Personal</b></th>
                <th><b>Business</b></th>
                <th><b>Unclassified</b></th>
                <th><b>Total Cost</b></th>
        </tr>
        </thead>
        <?php
            $grand_per  = 0;
            $grand_bus  = 0;
            $grand_unclassify  = 0;
            $grand_totalcharge = 0;
        ?>
        <tbody>
        @foreach ($data_group as $row)
            <?php
                                
                $row_totalcharge =  $row['personal'] + $row['business'] + $row['unclassified'];
             ?>
            <tr class="">
                <td>{{$row['name']}}</td>
                <td class="right">{{number_format($row['personal'],2)}}</td>
                <td class="right">{{number_format($row['business'],2)}}</td>
                <td class="right">{{number_format($row['unclassified'],2)}}</td>
                <td class="right">{{number_format($row_totalcharge,2)}}</td>
               
            </tr>
            <?php
                $grand_per += $row['personal'];
                $grand_bus += $row['business'];    
                $grand_unclassify += $row['unclassified'];        
                $grand_totalcharge +=  $row_totalcharge;
            ?>

        @endforeach
       
        <tr class="total-amount">
            <td align="right"><b>Total</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_per, 2)}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_bus, 2)}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_unclassify, 2)}}</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_totalcharge, 2)}}</b></td>
           
        </tr>
     
        </tbody>
    </table>
</div>
@endforeach
@endif
@else
@if(!empty($data['by_dept_data']))
@foreach ($data['by_dept_data'] as  $key => $data_group)
<div style="margin-top: 15px">
<?php
           $monthName = date("F", mktime(0, 0, 0, $key, 10));
?>
<b style="margin-bottom: 5px">{{$monthName}}</b>
    <table class="" style="width : 100%">
        <thead>
        <tr>
                <th><b>Department</b></th>
                <th><b>Total Cost</b></th>
        </tr>
        </thead>
        <?php
            $grand_totalcharge = 0;
        ?>
        <tbody>
        @foreach ($data_group as $row)
            <?php
                                
                $row_totalcharge =  $row['personal'] + $row['business'] + $row['unclassified'];
             ?>
            <tr class="">
                <td>{{$row['name']}}</td>
                <td class="right">{{number_format($row_totalcharge,2)}}</td>
               
            </tr>
            <?php    
                $grand_totalcharge +=  $row_totalcharge;
            ?>

        @endforeach
       
        <tr class="total-amount">
            <td align="right"><b>Total</b></td>
            <td align="right"><b>{{$data['currency']}} {{number_format($grand_totalcharge, 2)}}</b></td>
           
        </tr>
     
        </tbody>
    </table>
</div>
@endforeach
@endif
@endif
@endif

