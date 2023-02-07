<?php
    $prev_category_name = '';
    $lnf = $data['lnf'];
?>

<div style="text-align: center">
    <b>{{$data['name']}}</b>
</div>    

<div>       
    <table  border="0" style="width : 100%;">
        <tbody>
            <tr class="plain">                    
                <td width="25%">Type</td>
                <td width="25%">{{$lnf->lnf_type}}</td>
                <td width="25%">Date Found</td>
                <td width="25%">{{$lnf->lnf_time}}</td>                
            </tr>        
            <tr class="plain">                    
                <td>Found By</td>
                <td>{{$lnf->common_firstname}} {{$lnf->common_lastname}}</td>
                <td>Status</td>
                <td></td>                
            </tr>        
            <tr class="plain">                    
                <td>Received By</td>
                <td>{{$lnf->receiver_firstname}} {{$lnf->receiver_lastname}}</td>
                <td>Received Date</td>
                <td>{{$lnf->received_time}}</td>                                   
            </tr>        
            <tr class="plain">                    
                <td>Location</td>
                <td>{{$lnf->location_type}}: {{$lnf->location_name}}</td>                                   
                <td>Guest Name</td>
                <td>{{$lnf->guest_name}}</td>                                                                
            </tr>        
        </tbody>
    </table>
</div>

<div>        
    <p style="margin: 5px 0px 0px 0px">Items</p>
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                                    
                <th align="center"><b>Item Type</b></th>
                <th align="center"><b>Brand</b></th>                     
                <th align="center"><b>Category</b></th>
                <th align="center"><b>Stored Location/Time</b></th>
                <th align="center"><b>Description</b></th>
            </tr>   
        </thead>
        <tbody>
        @foreach ($data['item_list'] as $row)
            <tr class="plain">                    
                <td align="center">{{$row->item_type}}</td>
                <td align="center">{{$row->brand}}</td>
                <td align="center">{{$row->category}}</td>
                <td align="center">{{$row->stored_loc}}/{{$row->stored_time}}</td>
                <td align="center">{{$row->comment}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>    

<div>        
    <p style="margin: 5px 0px 0px 0px">Comment</p>
    <table  border="0" style="width : 100%;">
        <thead style="background-color:#ffffff">
            <tr class="plain">                                    
                <th align="center" width="90"><b>Date</b></th>
                <th align="center"><b>Commnet</b></th>                     
                <th align="center" width="100"><b>User</b></th>                
            </tr>   
        </thead>
        <tbody>
        @foreach ($data['comment_list'] as $row)
            <tr class="plain">                    
                <td align="center">{{$row->created_at}}</td>
                <td align="center">{{$row->comment}}</td>
                <td align="center">{{$row->first_name}} {{$row->first_name}}</td>                
            </tr>
        @endforeach
        </tbody>
    </table>
</div>    
