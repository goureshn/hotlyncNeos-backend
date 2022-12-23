<html>
<body>
<p>Guest Name: {{$info['name']}}</p>
<p>Room No: {{$info['room']}}</p>

<table style="width : 60%;border:1px solid #c9c9c9">
    <thead>
        <tr style="background: #464647">
            <th colspan="4" style="color:#ffffff; text-align: left"><b>Feedback Information</b></th>
        </tr>
    </thead>
    <tbody>
         <tr>
                <td style="text-align: right" ><b>How friendly was the front desk staff?</b></td>
                
				@if($info['q1'] == 1)
    <td>Excellent</td>
@elseif($info['q1'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
<td style="text-align: right"><b>How clean did the housekeeping staff keep your room throughout your stay?</b></td>
                @if($info['q2'] == 1)
    <td>Excellent</td>
@elseif($info['q2'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
         </tr>
         <tr>
             <td style="text-align: right"><b>How well-equipped was your room?</b></td>
             @if($info['q3'] == 1)
    <td>Excellent</td>
@elseif($info['q3'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
<td style="text-align: right"><b>How helpful was the concierge throughout your stay?</b></td>
                @if($info['q4'] == 1)
    <td>Excellent</td>
@elseif($info['q4'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
         </tr>
         <tr>
             <td style="text-align: right"><b>How comfortable were your bed linens?</b></td>
                @if($info['q5'] == 1)
    <td>Excellent</td>
@elseif($info['q5'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
             <td style="text-align: right"><b>Overall, how satisfied were you with our hotel?</b></td>
                @if($info['q6'] == 1)
    <td>Excellent</td>
@elseif($info['q6'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
         </tr>
         <tr>
             
             <td style="text-align: right"><b>How likely are you to recommend our hotel to others?</b></td>
                @if($info['q7'] == 1)
    <td>Excellent</td>
@elseif($info['q7'] == 2)
    <td>Moderate</td>
@else 
<td>Extremely Poor</td>	
@endif
             <td style="text-align: right"><b>Comment:</b></td>
             <p>Comment: {{$info['comment']}}</p>
         </tr>
    </tbody>
</table>
</body>
</html>