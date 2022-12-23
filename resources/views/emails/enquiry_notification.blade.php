<html>
<body>
<p>Guest Name: {{$info['name']}}</p>
<p>Room No: {{$info['room']}}</p>
<p>Number of Pax: {{$info['pax']}}</p>
<p>Date & Time: {{$info['start_date']}}</p>
<p>Email: {{$info['email']}}</p>
<p>Contact No: {{$info['contact_no']}}</p>
<p>Comment: {{$info['comment']}}</p>
<p>Disclaimer: {{$info['disclaimer']}}</p>
<table style="width : 60%;border:1px solid #c9c9c9">
    <thead>
        <tr style="background: #464647">
            <th colspan="4" style="color:#ffffff; text-align: left"><b>Promotion Information</b></th>
        </tr>
    </thead>
    <tbody>
         <tr>
                <td style="text-align: right" ><b>Outlet Name:</b></td>
                <td>{{$info['outlet_name']}}</td>
                <td style="text-align: right"><b>Promotion ID:</b></td>
                <td>{{$info['promotion_id']}}</td>
         </tr>
         <tr>
             <td style="text-align: right"><b>Title:</b></td>
             <td colspan="3">{{$info['title']}}</td>
         </tr>
         <tr>
             <td style="text-align: right"><b>Price:</b></td>
             <td>{{$info['price']}}</td>
             <td style="text-align: right"><b>Discount:</b></td>
             <td>{{$info['discnt']}}</td>
         </tr>
         <tr>
             <td style="text-align: right"><b>Start Date:</b></td>
             <td >{{date_format(new DateTime($info['start_date']),'d-M-Y')}}</td>
             <td style="text-align: right"><b>End Date:</b></td>
             <td>{{date_format(new DateTime($info['end_date']),'d-M-Y')}}</td>
         </tr>
    </tbody>
</table>
</body>
</html>