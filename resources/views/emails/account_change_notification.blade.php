
<html>
<head></head>
<body>
<h1>Dear {{$info['first_name']}}</h1>
<p>Your password has been changed for the below account: </p>
<p>Username: {{$info['username']}}</p>
<br/>
<p>Please follow the link to login: </p>
<p>Url: <a href="{{$info['host_url']}}">{{$info['host_url']}}</a></p>
<br/>
<p>Thanks</p>
<p>Ennovatech Solutions</p>

</body>
</html>