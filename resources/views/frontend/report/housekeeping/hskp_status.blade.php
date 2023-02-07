@if (!empty($data['hskp_summary']) && $data['report_type'] == 'Detailed')
<?php
$summary_header = $data['summary_header'];
?>
<div style="margin-top: 20px">
<table style="width: 100%;">
    <tr>
        <th>&nbsp;</th>
        @foreach ($summary_header as  $header)
        <th> {{$header}}</th>
        @endforeach    
    </tr>
    <?php
                    $total_vacant = 0;
                    $total_occupied = 0;
                    $total_total = 0;
                    $total_time = 0;
                    $total_avg = 0;
                   
    ?>
    @foreach ($data['hskp_summary'] as  $key =>$obj)
    <tr>
        <td align="center">{{$key}}</td>      
        <td align="center"> {{$obj[$data['summary_header'][0]]}} </td> 
        <td align="center"> {{$obj[$data['summary_header'][1]]}} </td>  
        <td align="center"> {{$obj[$data['summary_header'][2]]}} </td>             
        <td align="center"> {{gmdate("H:i:s", $obj[$data['summary_header'][3]])}} </td>  
        <td align="center"> {{gmdate("H:i:s", $obj[$data['summary_header'][4]])}} </td>
        
                           
                           
    </tr>  
    <?php
         $total_vacant += $obj[$data['summary_header'][0]];
         $total_occupied += $obj[$data['summary_header'][1]];
         $total_total += $obj[$data['summary_header'][2]];
         $total_time += $obj[$data['summary_header'][3]];
         $total_avg += $obj[$data['summary_header'][4]];
                        
     ?>      
    @endforeach
    <tr class="">
           
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>Total</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_vacant}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_occupied}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_total}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{gmdate("H:i:s",$total_time)}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{gmdate("H:i:s",$total_avg)}}</b></td>                   
     </tr>   
   
</table>
</div>
@endif
@if (!empty($data['hskp_summary1']) && $data['report_type'] == 'Detailed')
<?php
$summary_header = $data['summary_header1'];
?>
<div style="margin-top: 20px">
<table style="width: 100%;">
    <tr>
        <th>&nbsp;</th>
        @foreach ($summary_header as  $header)
        <th> {{$header}}</th>
        @endforeach    
    </tr>
    <?php
                    $total_dnd = 0;
                    $total_refuse = 0;
                    $total_total = 0;
                   
                   
    ?>
    @foreach ($data['hskp_summary1'] as  $key =>$obj)
    <tr>
        <td align="center">{{$key}}</td>      
        <td align="center"> {{$obj[$data['summary_header1'][0]]}} </td> 
        <td align="center"> {{$obj[$data['summary_header1'][1]]}} </td>  
        <td align="center"> {{$obj[$data['summary_header1'][2]]}} </td> 
        
        
                           
    </tr>  
    <?php
         $total_dnd += $obj[$data['summary_header1'][0]];
         $total_refuse += $obj[$data['summary_header1'][1]];
         $total_total += $obj[$data['summary_header1'][2]];
                        
     ?>    
    @endforeach
    <tr class="">
           
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>Total</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_dnd}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_refuse}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_total}}</b></td>
                      
     </tr> 
   
</table>
</div>
@endif

@if (!empty($data['hskp_list']) && $data['report_type'] == 'Detailed')
@foreach ($data['hskp_list'] as  $key => $data_group)
	<div style="margin-top: 10px">
        <p style="margin: 0px"><b>{{$key}}</b></p>
        <table class="grid print-friendly" border="0" style="width : 100%;" >
		<thead style="background-color:#3c6f9c">
			@if ($data['report_type'] == 'Detailed')
                <tr>
                    @foreach($data['fields'] as $key1=>$value)
                        
                            <th><b>{{$data['fields'][$key1]}}</b></th>
                       
                    @endforeach
                </tr>
            @endif
		</thead>
        <tbody>
			@foreach ($data_group as $row)
				<?php
				$data_row = array();
				
                        $data_row[] = substr($row->created_at, 0, 10);
                        $data_row[] = $row->room;
                        $data_row[] = $row->status;
                        $data_row[] = $row->wholename;
                        $data_row[] = $row->start_time;
                        $data_row[] = $row->end_time;
                        $data_row[] = gmdate("H:i:s", $row->duration);

				?>
				 <tr class="">
					@foreach($data_row as $key1=>$value)                        
                        <td align="center">{{$data_row[$key1]}}</td>
                    @endforeach
				</tr>
			@endforeach			
		</tbody>
        </table>                        
    </div> 
    @endforeach   
@endif

@if (!empty($data['hskp_list1']) && $data['report_type'] == 'Detailed')
<p align="center" style="margin: 10px"><b>Turndown Room Status</b></p>
@foreach ($data['hskp_list1'] as  $key => $data_group)
	<div style="margin-top: 10px">
        <p style="margin: 0px"><b>{{$key}}</b></p>
        <table class="grid print-friendly" border="0" style="width : 100%;" >
		<thead style="background-color:#3c6f9c">
			@if ($data['report_type'] == 'Detailed')
                <tr>
                    @foreach($data['fields1'] as $key1=>$value)
                        
                            <th><b>{{$data['fields1'][$key1]}}</b></th>
                       
                    @endforeach
                </tr>
            @endif
		</thead>
        <tbody>
			@foreach ($data_group as $row)
				<?php
				$data_row = array();
                        if ($row->td_state == 2) {
                            $state = 'Cleaning Done';
                        }
                        $data_row[] = substr($row->created_at, 0, 10);
                        $data_row[] = $row->room;
                        $data_row[] = $state;
                        $data_row[] = $row->wholename;
                        $data_row[] = $row->start_time;
                        $data_row[] = $row->end_time;
                        $data_row[] = gmdate("H:i:s", $row->duration);

				?>
				 <tr class="">
					@foreach($data_row as $key1=>$value)                        
                        <td align="center">{{$data_row[$key1]}}</td>
                    @endforeach
				</tr>
			@endforeach			
		</tbody>
        </table>                        
    </div> 
    @endforeach   
@endif

@if (!empty($data['hskp_summary']) && $data['report_type'] == 'Summary')
<?php
$summary_header = $data['summary_header'];
?>
<div style="margin-top: 20px">
<table style="width: 100%;">
    <tr>
        <th>&nbsp;</th>
        @foreach ($summary_header as  $header)
        <th> {{$header}}</th>
        @endforeach    
    </tr>
    <?php
                    $total_vacant = 0;
                    $total_occupied = 0;
                    $total_total = 0;
                    $total_time = 0;
                    $total_avg = 0;
                   
    ?>
    @foreach ($data['hskp_summary'] as  $key =>$obj)
    <tr>
        <td align="center">{{$key}}</td>      
        <td align="center"> {{$obj[$data['summary_header'][0]]}} </td> 
        <td align="center"> {{$obj[$data['summary_header'][1]]}} </td>  
        <td align="center"> {{$obj[$data['summary_header'][2]]}} </td> 
        <td align="center"> {{gmdate("H:i:s", $obj[$data['summary_header'][3]])}} </td>  
        <td align="center"> {{gmdate("H:i:s", $obj[$data['summary_header'][4]])}} </td>
        
                           
    </tr>  
    <?php
         $total_vacant += $obj[$data['summary_header'][0]];
         $total_occupied += $obj[$data['summary_header'][1]];
         $total_total += $obj[$data['summary_header'][2]];
         $total_time += $obj[$data['summary_header'][3]];
         $total_avg += $obj[$data['summary_header'][4]];
        
                        
     ?>    
    @endforeach
    <tr class="">
           
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>Total</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_vacant}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_occupied}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_total}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{gmdate("H:i:s",$total_time)}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{gmdate("H:i:s",$total_avg)}}</b></td>                   
     </tr> 
   
</table>
</div>
@endif

@if (!empty($data['hskp_summary1']) && $data['report_type'] == 'Summary')
<?php
$summary_header = $data['summary_header1'];
?>
<div style="margin-top: 20px">
<table style="width: 100%;">
    <tr>
        <th>&nbsp;</th>
        @foreach ($summary_header as  $header)
        <th> {{$header}}</th>
        @endforeach    
    </tr>
    <?php
                    $total_dnd = 0;
                    $total_refuse = 0;
                    $total_total = 0;
                   
                   
    ?>
    @foreach ($data['hskp_summary1'] as  $key =>$obj)
    <tr>
        <td align="center">{{$key}}</td>      
        <td align="center"> {{$obj[$data['summary_header1'][0]]}} </td> 
        <td align="center"> {{$obj[$data['summary_header1'][1]]}} </td>  
        <td align="center"> {{$obj[$data['summary_header1'][2]]}} </td> 
        
        
                           
    </tr>  
    <?php
         $total_dnd += $obj[$data['summary_header1'][0]];
         $total_refuse += $obj[$data['summary_header1'][1]];
         $total_total += $obj[$data['summary_header1'][2]];
        
        
                        
     ?>    
    @endforeach
    <tr class="">
           
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>Total</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_dnd}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_refuse}}</b></td>
        <td style="text-align:center; background-color:#CFD8DC" class="right"><b>{{$total_total}}</b></td>
                      
     </tr> 
   
</table>
</div>
@endif



 
 
 