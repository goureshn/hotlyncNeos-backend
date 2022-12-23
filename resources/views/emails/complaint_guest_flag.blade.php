<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width" />
  <title>HotLync Complaints</title>

  <style type="text/css">
    th {
      text-align: left
    }
  </style>
</head>

<body>
  <h4>Dear {{$info['first_name']}}</h4>  
  <p>Guest {{$info['guest_name']}} has been previously flagged by {{$info['flag_by']}} on {{$info['flag_date']}} with following details:
  
  <br>
  <br>
  <div style="font-size: 15px">
    <p>Guest Name: {{$info['guest_name']}}</p>
    <p>Room: {{$info['room']}}</p>
    <p>Checkin: {{$info['arrival']}}</p>
    <p>Checkout: {{$info['departure']}}</p>

    <br>

    <p>Previous Room: {{$info['prev_room']}}</p>
    <p>Checkin: {{$info['prev_arrival']}}</p>
    <p>Checkout: {{$info['prev_departure']}}</p>

    <br>
    <p>Comment: {{$info['guest_comment']}}</p>
    <p>Preference: {{$info['guest_pref']}}</p>

    <br>

    <hr>
    <p><b>Previous complaints:</b></p>
    
    <table>
      <thead>
        <tr>
          <th width="100">ID</th>
          <th>Complaint</th>
          <th width="100">Compensation</th>          
        </tr>
      </thead>
      <tbody>
      @foreach($info['history_list'] as $row)
        <tr>
          <td>{{sprintf('C%05d', $row->id)}}</td> 
          <td>{{$row->comment}}</td>
          <td>{{$row->total_count}}</td>
        </tr>
      @endforeach  
      </tbody>
    </table>
    
    <hr>
    <p>Thanks</p>
    <p>HotLync</p>
  </div>

</body>

</html>