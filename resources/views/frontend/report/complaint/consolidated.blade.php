<?php
function isCheckNull($val) {
    if($val == null) {
        return "";
    }else {
        return $val;
    }
}

function  getTicketNumber ($ticket){
    if(!$ticket)
        return 'F00000';
    return sprintf('F%05d', $ticket->parent_id);
}

// echo json_encode($data['hot_issue_summary']);
$kpi_summary = $data['kpi_summary'];
?>
<br><br>

<style type="text/Css">
    .horizontal-container {
        width: 100%;
        display: flex;
    }
    table.border {
        border-collapse: collapse;
        width: 100%;
        table-layout: fixed;
    }

    .border th, .border td {
        border: 1px solid black;
    } 

    .border p {
        margin: 0px;
    }

    p.sub-title {
        font-size:10px;
    }

    .first-column 
    {
        background: #d9e1f1;
    }

    .second-column 
    {
        background: #8eaad8;
    }

    .third-column 
    {
    }

    table.status-table td:not(:first-child) {
        text-align: center;
    }

    table.status-table td {
        height: 28px;
    }

    .head1-cell td, td.head1-cell 
    {
        background: #8eaad8;        
    }

    .head2-cell td:not(:first-child), td.head2-cell 
    {
        background: #d9e1f1;        
    }

    table.category-type-table {
        text-align: center;
    }

</style>

<br>

<?php
    $summary = $data['source_summary'];
    $source_list = $summary['source_list'];
    $summary_by_source = $summary['summary_by_source'];

    $col_width = round(100 / (count($source_list) + 1), 0);
?>
<div style="margin-top: 30px; padding: 5px">
    <table class="border category-type-table">
        <thead>
            <tr>
                <th align="center" colspan={{count($source_list) + 1}}>SOURCE BY SUMMARY REPORT</th>                
            </tr>
        </thead> 
        <tbody>
            <tr class="head1-cell">
                    <td>Total</td>
                @foreach($source_list as $key => $row)
                    <td>{{$row->name}}</td>
                @endforeach
            </tr>
            
            <tr>
                <td>{{$summary_by_source->total_cnt}}</td>
                @foreach($source_list as $key => $row)
                    <?php $cnt_key = 'cnt' . $key ?>
                    <td>{{$summary_by_source->$cnt_key}}</td>                
                @endforeach
            </tr>    
        </tbody>
    </table>
</div>    


<div class="horizontal-container">
    <!-- KPI Summary -->
    <div style="width: 33%; padding: 5px">
        <table class="border">
            <thead>
                <tr>
                    <th align="center" colspan=3>KPI SUMMARY REPORT</th>                
                </tr>
            </thead>    
            <tbody>
                <colgroup>
                    <col style="width:15%" />
                    <col style="width:70%" />
                    <col style="width:15%" />                    
                </colgroup>
                <tr>
                    <td align="center" rowspan=3 class="first-column">1</td>
                    <td class="second-column">Total no. of in-house guests</td>
                    <td align="center" class="third-column"> {{$kpi_summary['total_checkin_guest']}} </td>                
                </tr>
                <tr>                
                    <td class="second-column">
                        <p>Total of in-house guest complaints</p>
                        <p class="sub-title">(in-house, Post-Depature and Pre-Arrival count)</p>
                    </td>
                    <td align="center" class="third-column">{{$kpi_summary['inhouse_complaint']}}</td>
                </tr>
                <tr>                
                    <td class="second-column">Average no, of in-house guest complaints</td>
                    <td align="center" class="third-column">{{$kpi_summary['avg_inhouse_complaint']}}%</td>                
                </tr>

                <tr>
                    <td align="center" class="first-column">2</td>
                    <td class="second-column">
                        <p>Total of visitor  complaints</p>
                        <p class="sub-title">(Outside Visitor and Other count)</p>
                    </td>
                    <td align="center" class="third-column">{{$kpi_summary['walkin_complaint']}}</td>
                </tr>
                
                <tr>                
                    <td align="center" rowspan=2 class="first-column">3</td>
                    <td class="second-column">
                        <p>Total no. of CFS cases</p>
                        <p class="sub-title">(1 case guest profile)</p>
                    </td>
                    <td align="center" class="third-column">{{$kpi_summary['total_complaint']}}</td>
                </tr>
                <tr>                
                    <td class="second-column">Average closure time per closed complaint(Days)</td>
                    <td align="center" style="background:red" class="third-column">{{$kpi_summary['avg_closure_days']}}</td>                
                </tr>

                <tr>
                    <td align="center" rowspan=2 class="first-column">4</td>
                    <td class="second-column">Total amount of compensation cost(AED)</td>
                    <td align="center" class="third-column">AED {{number_format($kpi_summary['total_comp'],2)}}</td>                
                </tr>
                <tr>                
                    <td class="second-column">Average compensation cost per complaint(AED)</td>
                    <td align="center" class="third-column">AED {{number_format($kpi_summary['avg_comp'],2)}}</td>                
                </tr>
                
            </tbody>
        </table>    
    </div>    

    <!-- Status OF Complaints -->
<?php
    $summary = $data['status_summary'];
    $in_house = $summary['in_house'];
    $post_departure = $summary['post_departure'];
    $pre_arrival = $summary['pre_arrival'];
    $total1 = $summary['total1'];

    $outside_visitor = $summary['outside_visitor'];
    $others = $summary['others'];
    $total2 = $summary['total2'];

    $grand_total = $summary['grand_total'];
?>    
    <div style="width: 33%; padding: 5px">
        <table class="border status-table">
            <thead>
                <tr>
                    <th align="center" colspan=5>STATUS OF COMPLAINTS</th>                
                </tr>
            </thead>  
            <tbody>
                <colgroup>
                    <col style="width:30%" />
                    <col style="width:20%" />
                    <col style="width:20%" />
                    <col style="width:20%" />
                    <col style="width:20%" />                    
                </colgroup>
                <tr class="head1-cell">
                    <td>Guest Status</td>
                    <td>Open</td>
                    <td>Re-open</td>
                    <td>Closed</td>
                    <td>Total Cases</td>
                </tr>
                <tr>
                    <td class="head2-cell">In-house Guest</td>
                    <?php $row = $in_house; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                
                </tr>
                <tr>
                    <td class="head2-cell">Post Departure</td>
                    <?php $row = $post_departure; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                
                </tr>
                <tr>
                    <td class="head2-cell">Pre-arrival</td>
                    <?php $row = $pre_arrival; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                
                </tr>

                <tr class="head2-cell">
                    <td class="head1-cell">Total</td>
                    <?php $row = $total1; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                     
                </tr>

                <tr>
                    <td class="head2-cell">Outside Visitor</td>
                    <?php $row = $outside_visitor; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                
                </tr>
                <tr>
                    <td class="head2-cell">Others*</td>
                    <?php $row = $others; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                
                </tr>

                <tr class="head2-cell">
                    <td class="head1-cell">Total</td>
                    <?php $row = $total2; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td>                
                </tr>

                <tr class="head1-cell">
                    <td>Grand Total</td>
                    <?php $row = $grand_total; ?>
                    <td>{{$row->open_cnt}}</td>
                    <td>{{$row->reopen_cnt}}</td>
                    <td>{{$row->closed_cnt}}</td>
                    <td>{{$row->total_cnt}}</td> 
                </tr>
            </tbody>        
        </table>   
    </div>    

    <!-- Closure rate OF Complaints -->
<?php
    $summary = $data['closure_rate_summary'];
    $in_house = $summary['in_house'];
    $post_departure = $summary['post_departure'];
    $pre_arrival = $summary['pre_arrival'];
    $total1 = $summary['total1'];
    
    $outside_visitor = $summary['outside_visitor'];
    $others = $summary['others'];
    $total2 = $summary['total2'];
    
    $grand_total = $summary['grand_total'];
?>      
    <div style="width: 33%; padding: 5px">
        <table class="border status-table">
            <thead>
                <tr>
                    <th align="center" colspan=5>CLOSURE RATE OF COMPLAINTS</th>                
                </tr>
            </thead>  
            <tbody>
                <colgroup>
                    <col style="width:30%" />
                    <col style="width:20%" />
                    <col style="width:20%" />
                    <col style="width:20%" />
                    <col style="width:20%" />                    
                </colgroup>
                <tr class="head1-cell">
                    <td>Guest Status</td>
                    <td>Within 24hr</td>
                    <td>%</td>
                    <td>About 24hrs</td>
                    <td>%</td>
                </tr>
                <tr>
                    <td class="head2-cell">In-house Guest</td>
                    <?php $row = $in_house; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                
                </tr>
                <tr>
                    <td class="head2-cell">Post Departure</td>
                    <?php $row = $post_departure; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                
                </tr>
                <tr>
                    <td class="head2-cell">Pre-arrival</td>
                    <?php $row = $pre_arrival; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                
                </tr>

                <tr class="head2-cell">
                    <td class="head1-cell">Total</td>
                    <?php $row = $total1; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                     
                </tr>

                <tr>
                    <td class="head2-cell">Outside Visitor</td>
                    <?php $row = $outside_visitor; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                
                </tr>
                <tr>
                    <td class="head2-cell">Others*</td>
                    <?php $row = $others; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                
                </tr>

                <tr class="head2-cell">
                    <td class="head1-cell">Total</td>
                    <?php $row = $total2; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>                
                </tr>

                <tr class="head1-cell">
                    <td>Grand Total</td>
                    <?php $row = $grand_total; ?>
                    <td>{{$row->within_cnt}}</td>
                    <td>{{$row->within_percent}}%</td>
                    <td>{{$row->about_cnt}}</td>
                    <td>{{$row->about_percent}}%</td>
                </tr>
            </tbody>        
        </table>   
    </div>    
</div>  

<?php
    $summary = $data['category_type_summary'];
    $compensation_type_list = $summary['compensation_type_list'];
    $total_type_data = $summary['total_type_data'];
    $category_type_list = $summary['category_type_list'];

    $col_width = round((100 - 15) / (2 * count($compensation_type_list) + 1), 0);
?>
<div style="margin-top: 30px; padding: 5px">
    <table class="border category-type-table">
        <colgroup>
                <col style="width:15%" />
            @foreach($compensation_type_list as $key => $row)
                <col style="width:{{$col_width}}%" />
            @endforeach                
        </colgroup>
        <tr>
                <td rowspan=3>Category</td>
                <td>Recurring issues</td>
            @foreach($compensation_type_list as $key => $row)
                <td colspan=2>{{$row}}</td>
            @endforeach
        </tr>
        <tr>                
                <td>Total Issues</td>
            @foreach($compensation_type_list as $key => $row)
                <td>Number</td>
                <td>Amount</td>
            @endforeach
        </tr>
        
        <tr>                
                <td>{{$total_type_data->total_cnt}}</td>
            @foreach($compensation_type_list as $key => $row)
                <?php $cnt_key = 'cnt' . $key; $amount_key = 'amount' . $key; ?>
                <td>{{$total_type_data->$cnt_key}}</td>
                <td>AED {{number_format($total_type_data->$amount_key,2)}}</td>
            @endforeach
        </tr>

        @foreach( $category_type_list as $row )
        <tr>
            <td>{{$row->category}}</td>
            <td>{{$row->total_cnt}}</td>
            @foreach($compensation_type_list as $key => $row1)
                <?php $cnt_key = 'cnt' . $key; $amount_key = 'amount' . $key; ?>
                <td>{{$row->$cnt_key}}</td>
                <td>AED {{number_format($row->$amount_key,2)}}</td>
            @endforeach
        </tr>    
        @endforeach

    </table>
</div>    

<br/><br/><br/><br/>
<div class="horizontal-container" style="display:none">
    <?php
        $summary = $data['satisfaction_summary'];
        $graph = $summary['graph'];
        $graph_style = $summary['graph_style'];
    ?>

    <div style="text-align: center; width: 50%; padding: 5px">
        <img src="data:image/png;base64,{{ $graph}}" {{$graph_style}}>
    </div>

    <?php
        $summary = $data['hot_issue_summary'];
        $graph = $summary['graph'];
        $graph_style = $summary['graph_style'];
    ?>

    <div style="text-align: center; width: 50%; padding: 5px">
        <img src="data:image/png;base64,{{ $graph}}" {{$graph_style}}>
    </div>
</div>