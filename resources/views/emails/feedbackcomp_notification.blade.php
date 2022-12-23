<html>
<body>
<p>Guest Name: {{$info['name']}}</p>
<p>Room No: {{$info['room']}}</p>

<table style="width : 60%;border:1px solid #c9c9c9">
    <thead>
        <tr style="background: #464647">
            <th colspan="4" style="color:#ffffff; text-align: left"><b>Complaint Information</b></th>
        </tr>
    </thead>
    <tbody>
         <tr>
                <td style="text-align: right" ><b>Complaint</b></td>
                <p>{{$info['comment']}}</p>
         </tr>
    </tbody>
</table>
</body>
</html>