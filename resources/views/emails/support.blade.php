<html>
<style>
    .left{
        width: 100px;
    }
    div {
        margin-top:10px;
        font-size: 12px;;
    }
</style>
<head></head>
<body>
    <h1>Subject : #{{$info['id']}} {{$info['property_shortcode']}} {{$info['subject']}}</h1>
    <div> {{$info['message']}}</div>
    <div>Dear Support, </div>
    <div>&nbsp;&nbsp;&nbsp;A new support ticket has been raised.</div>
    <div><label class="left">Client :</label> {{$info['client']}}</div>
    <div><label class="left"> Property:</label> {{$info['property']}} </div>
    <div><label class="left"> Module:</label> {{$info['module']}}</div>
    <div><label class="left"> Severity:</label> {{$info['severity']}}</div>
    <div><label class="left"> Submitted by:</label> {{$info['user_name']}}</div>
    <div><label class="left"> Mobile: </label> {{$info['mobile']}} </div>
    <div><label class="left"> Email:</label> {{$info['from_email']}} </div>
    <div><label class="left"> CC: </label> {{$info['cc_email']}} </div>
    <div><label class="left"> Issue: </label> {{$info['issue']}} </div>
    <div><a href="{{$info['link']}}">when this link is clicked, it will go to the Central Server</a></div>
</body>
</html>