@if( $data['filter_by'] == 'Mobile')
@foreach ($data['summary'] as $key => $monthgroup)
      
            <div style="margin-top: 0px;">
            <?php
           $monthName = date("F", mktime(0, 0, 0, $key, 10));
            ?>
            @if(!empty($monthgroup['detail']))
            <p style="margin: 0px"><b>{{$monthName}}</b> </p>
            @foreach ($monthgroup['detail'] as  $dept_key => $dept_group)
            <p style="margin: 0px"><b>Department :</b> {{$dept_key}}</p>
                <table class="grid"  style="width : 100%;float: left;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th><b>Number</b></th>
                        <th><b>User</b></th>
                        <th><b>Total calls</b></th>
                        <th><b>Total Cost</b></th>
                        <th><b>Unclassified</b></th>
                        <th><b>Personal </b></th>
                        <th><b>Business</b></th>
                    </tr>
                    </thead>
                   
                    <tbody>
                    @foreach ($dept_group['mobile'] as $row2)
                        <tr class="">
                            <td align = "left">{{$row2->extension}}</td>
                            <td align = "left">{{$row2->user}}</td>
                            <td align = "right">{{$row2->totalcount}}</td>
                            <td class = "right">{{number_format($row2->totalcost,2)}}</td>
                            <td class = "right">{{number_format($row2->unclassify,2)}}</td>
                            <td class = "right">{{number_format($row2->personal,2)}}</td>
                            <td class = "right">{{number_format($row2->business,2)}}</td>                           
                        </tr>
                    @endforeach
                    </tbody>
                   
                </table>
                @endforeach
                @endif
            </div>
@endforeach 
@endif
@if( $data['filter_by'] == 'Extension')
@foreach ($data['summary'] as $key => $row)
      
            <div style="margin-top: 0px;">
            <?php
           $monthName = date("F", mktime(0, 0, 0, $row['name'], 10));
            ?>
            <p style="margin: 0px"><b>{{$monthName}}</b> </p>
                <table class="grid"  style="width : 100%;float: left;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th><b>Number</b></th>
                        <th><b>User</b></th>
                        <th><b>Total calls</b></th>
                        <th><b>Total Cost</b></th>
                        <th><b>Unclassified</b></th>
                        <th><b>Personal </b></th>
                        <th><b>Business</b></th>
                    </tr>
                    </thead>
                   
                    <tbody>
                    @foreach ($row['detail'] as $row2)
                        <tr class="">
                            <td align = "left">{{$row2->extension}}</td>
                            <td align = "left">{{$row2->user}}</td>
                            <td align = "right">{{$row2->totalcount}}</td>
                            <td class = "right">{{number_format($row2->totalcost,2)}}</td>
                            <td class = "right">{{number_format($row2->unclassify,2)}}</td>
                            <td class = "right">{{number_format($row2->personal,2)}}</td>
                            <td class = "right">{{number_format($row2->business,2)}}</td>                           
                        </tr>
                    @endforeach
                    </tbody>
                   
                </table>
            </div>
@endforeach 
@endif

@if($data['filter_by'] == 'All')
@foreach ($data['summary'] as $key => $row)
      
            <div style="margin-top: 0px;">
            <?php
           $monthName = date("F", mktime(0, 0, 0, $row['name'], 10));
            ?>
            <p style="margin: 0px"><b>{{$monthName}}</b> </p>
                <table class="grid"  style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th colspan="7"><b>Land Line</b></th> 
                    </tr>
                    <tr>
                        <th><b>Number</b></th>
                        <th><b>User</b></th>
                        <th><b>Total calls</b></th>
                        <th><b>Total Cost</b></th>
                        <th><b>Unclassified</b></th>
                        <th><b>Personal </b></th>
                        <th><b>Business</b></th>
                    </tr>
                    </thead>
                   
                    <tbody>
                    @foreach ($row['detail'] as $row2)
                        <tr class="">
                        @if(!empty($row['detail']))
                            
                            <td align = "left">{{$row2->extension}}</td>
                            <td align = "left">{{$row2->user}}</td>
                            <td align = "right">{{$row2->totalcount}}</td>
                            <td class = "right">{{number_format($row2->totalcost,2)}}</td>
                            <td class = "right">{{number_format($row2->unclassify,2)}}</td>
                            <td class = "right">{{number_format($row2->personal,2)}}</td>
                            <td class = "right">{{number_format($row2->business,2)}}</td>  
                        
                        @endif
                                                   
                        </tr>
                    @endforeach
                    </tbody>
                   
                </table>
                
               
            </div>

@endforeach   
@foreach ($data['sum'] as $key => $row)
      
            <div style="margin-top: 0px;">
            <table class="grid"  style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th colspan="6"><b>Mobile</b></th> 
                    </tr>
                    <tr>
                        <th><b>Number</b></th>
                        <th><b>Total calls</b></th>
                        <th><b>Total Cost</b></th>
                        <th><b>Unclassified</b></th>
                        <th><b>Personal </b></th>
                        <th><b>Business</b></th>
                    </tr>
                    </thead>
                   
                    <tbody> 
                    @foreach ($row['detail'] as $row3)
                        <tr class="">
                        @if(!empty($row['detail']))
                            <td align = "left">{{$row3->extension}}</td>
                            <td align = "right">{{$row3->totalcount}}</td>
                            <td class = "right">{{number_format($row3->totalcost,2)}}</td>
                            <td class = "right">{{number_format($row3->unclassify,2)}}</td>
                            <td class = "right">{{number_format($row3->personal,2)}}</td>
                            <td class = "right">{{number_format($row3->business,2)}}</td>  
                           
                        @endif
                                                    
                        </tr>
                    @endforeach
                   
                    </tbody>
                   
                </table>
                </div>
@endforeach  
@endif

@if (($data['filter_by'] == 'User') || ($data['filter_by'] == 'Department'))
@foreach ($data['summary'] as  $key => $user_group)
@if(!empty($user_group->month_details))
            <div style="margin-top: 0px;">
            @if ($data['filter_by'] == 'User')
                <p style="margin: 0px;color:Red;font-size: 30px;"><b>{{$user_group->user}}</b> </p>
            @else
            <p style="margin: 0px;color:Red;font-size: 30px;"><b>{{$user_group->department}}</b> </p>
            @endif
            @foreach ($user_group->month_details as  $month_name => $month_group)
            <?php
          
           $monthName = date("F", mktime(0, 0, 0, $month_name, 10));
            ?>
            <p style="margin: 0px;"><b>{{$monthName}}</p>
                <table class="grid" border = 1 style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th colspan="6"><b>Land Line</b></th> 
                        
                    </tr>
                    <tr>
                        <th><b>Number</b></th>
                        <th><b>Total calls</b></th>
                        <th><b>Total Cost</b></th>
                        <th><b>Unclassified</b></th>
                        <th><b>Personal </b></th>
                        <th><b>Business</b></th>
                       
                    </tr>
                    </thead>
                   
                    <tbody>
                    @foreach ($month_group as $row)
                    <tr class="">
                        @if(!empty($row['extinform1']))
                            
                            <td align = "left" >{{$row['extinform1']->extension}} - {{($row['extinform1']->user)}}</td>
                            <td align = "right" >{{$row['extinform1']->totalcount}}</td>
                            <td class = "right" >{{number_format($row['extinform1']->totalcost,2)}}</td>
                            <td class = "right" >{{number_format($row['extinform1']->unclassify,2)}}</td>
                            <td class = "right" >{{number_format($row['extinform1']->personal,2)}}</td>
                            <td class = "right" >{{number_format($row['extinform1']->business,2)}}</td>  
                       
                       
                         @endif  
                    </tr>
                    @endforeach
                    </tbody>  
                </table>
                @foreach ($month_group as $row)
                @if(!empty($row['mobileinform']))
                <table class="grid" border = 1 style="width : 100%;">
                    <thead style="background-color:#3c6f9c">
                    <tr>
                        <th colspan="6"><b>Mobile</b></th>   
                    </tr>
                    <tr>
                        <th><b>Number</b></th>
                        <th><b>Total Calls</b></th>
                        <th><b>Total Cost</b></th>
                        <th><b>Unclassified</b></th>
                        <th><b>Personal </b></th>
                        <th><b>Business</b></th>
                       
                    </tr>
                    </thead>
                    <tbody>
                    
                    <tr class="">
                        
                            @if(!empty($row['mobileinform']))
                            <td align = "left" >{{$row['mobileinform']->caller}} - {{($row['mobileinform']->user)}}</td>
                            <td align = "right" >{{$row['mobileinform']->totalcount1}}</td>
                            <td class = "right" >{{number_format($row['mobileinform']->totalcost1,2)}}</td>
                            <td class = "right" >{{number_format($row['mobileinform']->unclassify1,2)}}</td>
                            <td class = "right" >{{number_format($row['mobileinform']->personal1,2)}}</td>
                            <td class = "right" >{{number_format($row['mobileinform']->business1,2)}}</td> 
                           
                       
                            @endif
                    </tr>
                    
                    </tbody>
                </table>
                @endif
                @endforeach
                @endforeach    
            </div>         
    @endif
    @endforeach
@endif




