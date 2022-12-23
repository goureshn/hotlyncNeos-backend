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
<head>

</head>
<body>
    <h2>Dear {{$info['first_name']}} {{$info['last_name']}}</h2>
    <h3>Ticket {{$info['ticket_no']}} has been created with the following details: </h3>
    <p>Building:   {{$info['building']}} </p>
    <p>Subject:   {{$info['subject']}} </p>
    <br/>
    <p>Category:   {{$info['category']}} </p>
    <p>Sub category:   {{$info['subcategory']}} </p>
    <br/>
    <p>Issue:   {{$info['issue']}} </p>
</body>
</html>