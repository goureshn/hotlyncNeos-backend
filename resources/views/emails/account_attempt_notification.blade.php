
<html>
<head></head>
<body>
<h1>Dear {{$info['first_name']}}</h1>
<p>You account has been locked out due to {{$info['attempt_count']}} continues incorrect login. To access your account please click the below link and set a new password. </p>
<p>Url: <a href="{{$info['host_url']}}">{{$info['host_url']}}</a></p>
<br/>
<br/>
<p>Thanks</p>
<p>Ennovatech Solutions</p>

</body>
</html>