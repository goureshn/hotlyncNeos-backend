<table>
    @if(isset($data['logo'])) 
        <tr>
            <th rowspan="4" colspan="4">
                {{-- test sdgfdfg sdf dsfjhdsg fjsdhf gdsfjgdfj dsf gdsf gdsjf --}}
                {{-- <img src="{{public_path($data['logo'])}}" width="400px" alt=""> --}}
            </th>
        </tr>
        <tr> <th></th> <th colspan="2">Date Generated :</th> <th colspan="2" style="text-align: left">{{ date('d-M-Y') }}</th> </tr>
        @if(isset($data['period']))
        <tr> <th></th> <th colspan="2">Period :</th> <th colspan="3" style="text-align: left">{{ $data['period'] }}</th> </tr>
        @elseif(isset($data['property']))
        <tr> <th></th> <th colspan="2">Property :</th> <th colspan="3">{{ $data['property'] }}</th> </tr>
        @endif
        @php $total = is_array($data['datalist']) ? $data['datalist'] : $data['datalist']->toArray() @endphp
        <tr> <th></th> <th colspan="2">Total :</th> <th colspan="2" style="text-align: left">{{ count($total) }}</th> </tr>
        <tr> <th></th> <th></th> <th></th> <th></th> </tr>
    @endif
    @if(isset($data['datalist'][0])) 
        <tr>
            @if(isset($data['heading_list']))
                <th colspan="{{count($data['heading_list'])}}">{{ $data['sub_title'] }}</th>
            @elseif(isset($data['datalist'][0]))
                <th colspan="{{count($data['datalist'][0])}}">{{ $data['sub_title'] }}</th>
            @endif
        </tr>
        <tr>
            @if(isset($data['heading_list']))
                @foreach($data['heading_list'] as $value) <th>{{ $value }}</th> @endforeach
            @elseif(isset($data['datalist'][0]))
                @foreach($data['datalist'][0] as $key => $value) <th>{{ $key }}</th> @endforeach
            @endif
        </tr>
        @foreach($data['datalist'] as $value)
            <tr>
                @foreach($value as $val)
                    <td>{{ $val }}</td>
                @endforeach
            </tr>
        @endforeach
    @endif
</table>