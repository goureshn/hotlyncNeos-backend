<br/><br/>
<style>
.highlight {
    background: yellow;
}
</style>

<div>
    <table  style="width : 100%" border="0">
        <tbody >
        <tr style="height:35px;border:0">
            <td  style="width:100%;border: 0; text-align: center; font-size: 13px;">
                <strong>Guest Relations Log Report</strong>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div>
    <table  style="width : 100%">
        <tbody>
        @foreach($data['datalist'] as $row)
        <tr class="">
            <td style="width: 15%">
			<li class="blue"><b>{{sprintf('GR%05d', $row->id)}}</b></li>
                <li>{{date('d M Y', strtotime($row->created_at))}}</li>
                <li>{{date('H:i', strtotime($row->created_at))}}</li>
                
                @if($row->category == 'Guest Interaction')
                    <li class="red"><b>{{$row->category}}
                @elseif($row->category == 'Courtesy Calls')  
                    <li class="yellow"><b>{{$row->category}}
                @elseif($row->category == 'Room Inspection')  
                    <li style="color: #0000FF"><b>{{$row->category}}
                @elseif($row->category == 'Escorted to Room')  
                    <li style="color: #580c16"><b>{{$row->category}}
                @else  
                    <li style="color: #808080"><b>  
                {{$row->category}}
                @endif
                </b></li>   
            </td>
            <td>
			
                <li><label class="red">&#9899;</label><b>Created by: </b>{{$row->wholename}}</li>
				
                @if(  $row->category == 'Room Inspection' )
                    <li><label class="red">&#9899;</label><b>Room: </b>{{$row->new_room}}</li>
                @else
                    <li><label class="red">&#9899;</label><b>Room: </b>{{$row->room}}</li>
				@endif
				@if(  $row->category == 'Room Inspection')
                    <li><label class="red">&#9899;</label><b>Guest: </b>{{$row->new_guest}}</li>
                @else
                    <li><label class="red">&#9899;</label><b>Guest: </b>{{$row->guest_name}}</li>
                @endif    
                @if( !empty($row->arrival) && !empty($row->departure) )
				<li><label class="red">&#9899;</label><b>Stay: </b>{{date('d-M-Y', strtotime($row->arrival))}} to {{date('d-M-Y', strtotime($row->departure))}}</li>
                @endif
               
                    <li><label class="red">&#9899;</label><b>Location: </b>{{$row->lgm_name}} - {{$row->lgm_type}}</li>
                @if(($row->category == 'Guest Interaction') || ($row->category == 'Courtesy Calls') || ($row->category == 'Room Inspection'))
                    <li><label class="red">&#9899;</label><b>Sub-Category: </b>{{$row->sub_category}}</li>
                @endif
                @if( !empty($row->occasion))
                    <li><label class="red">&#9899;</label><b>Occasion: </b>{{$row->occasion}}</li>
                @endif
                @if(($row->category == 'Guest Interaction') || ($row->category == 'Courtesy Calls'))
                    <li><label class="red">&#9899;</label><b>Feedback: </b>{{$row->comment}}</li>
                @else
                    <li><label class="red">&#9899;</label><b>Comments: </b>{{$row->comment}}</li>
                @endif
                
                              
                
                
			
            </td>
        </tr>
        @endforeach

        
        </tbody>
    </table>
</div>
