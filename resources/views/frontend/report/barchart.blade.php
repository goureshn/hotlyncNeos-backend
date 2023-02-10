<html>
<head>
    <title>Lavacharts Dashboard</title>
</head>
<body>
<div id="my-dash">
    <div id="chart">
    </div>
    <div id="control">
    </div>
</div>

<?= Lava::render('Dashboard', 'Donuts', 'my-dash'); ?>
@dashboard('Donuts', 'my-dash')
</body>
</html>