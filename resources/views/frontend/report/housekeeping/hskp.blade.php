@if (!empty($data['hskp_list']) && $data['report_type'] == 'Detailed')
        <?php
            $omit_num = $data['omit_num'];
        ?>
        @foreach ($data['hskp_list'] as  $key => $data_group)
        <div style="margin-top: 20px">
            <p style="margin: 0px"><b>{{$data['report_by']}} : {{$key}}</b></p>
            <table class="grid" style = "width : 100%;">
                <thead style = "background-color:#2c3e50">
                @if ($data['report_type'] == 'Detailed')
                    <tr >
                        @foreach($data['fields'] as $key1=>$value)
                            @if( $key1 != $omit_num )
                                <th><b>{{$data['fields'][$key1]}}</b></th>
                            @endif
                        @endforeach
                    </tr>
                @endif
                </thead>
                <tbody>
                <?php
                    $total_count = 0;
                    $price = 0;
                ?>
                @foreach ($data_group as $row)
                    <?php
                       
                        $data_row = array();
                        $data_row[] = substr($row->created_at, 0, 10);
                        $data_row[] = $row->room;
                        $data_row[] = $row->status;
                        $data_row[] = $row->wholename;
                        $total_count++;
                    ?>
                    @if ($data['report_type'] == 'Detailed')
                        <tr class="">
                            @foreach($data_row as $key1=>$value)
                                @if( $key1 != $omit_num )
                                    <td align="center">{{$data_row[$key1]}}</td>
                                @endif
                            @endforeach
                        </tr>
                    @else
                        <tr style="display: none"><td colspan="3"></td></tr>
                    @endif
                @endforeach
               
                </tbody>
            </table>

        </div>
        @endforeach
@endif

@if($data['report_type'] == 'Summary')
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
    @foreach ($data['hskp_summary'] as  $key =>$obj)
    <tr>
        <td align="center">{{$key}}</td>      
        <td align="center"> {{$obj[$data['summary_header'][0]]}} </td> 
        <td align="center"> {{$obj[$data['summary_header'][1]]}} </td>  
        <td align="center"> {{$obj[$data['summary_header'][2]]}} </td>             
        <td align="center"> {{$obj[$data['summary_header'][3]]}} </td>  
        <td align="center"> {{$obj[$data['summary_header'][4]]}} </td>
        <td align="center"> {{$obj[$data['summary_header'][5]]}} </td>
        <td align="center"> {{$obj[$data['summary_header'][6]]}} </td>                    
    </tr>    
    @endforeach
</table>
</div>
@endif


