
<html>
<head></head>
<body>
<h1>Hello {{$info['first_name']}} {{$info['last_name']}}</h1>
<p> A new account has been created for you:</p>
<p> Username: {{$info['username']}}</p>
<p> Password: {{$info['password']}}</p>
<p> You can start using it by logging in here:</p>
<p> <a href="{{$info['host_url']}}">Go to this link</a></p>
<br>
<br>
<p>Thanks and Regards,</p>
<p>Ennovatech Solutions - {{$info['send_name']}}</p>

</body>
</html>
