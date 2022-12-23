<html>
<head></head>
<body>
<h1>Dear {{$info['first_name']}} {{$info['last_name']}}</h1>
<p>Your password will expire in {{$info['expiry_day']}} days. To change your password, please click the link below. </p>
<p>Url: <a href="{{$info['host_url']}}">{{$info['host_url']}}</a></p>
<br/>
<br/>
<p>Thanks</p>
<p>Ennovatech Solutions</p>

</body>
</html>