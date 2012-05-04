<!DOCTYPE html>
<html>
<head>
    <title>Table</title>
</head>

<body>
    <h1><?=$title?></h1>
    <p>
    <?php foreach($tablefields as $field): ?>
        <?=$field?>, 
    <?php endforeach; ?>
    </p>
    <?php foreach($query->result_array() as $row): ?>
    <p>
        <?php foreach($row as $cell): ?>
            <?=$cell?>,
        <?php endforeach; ?>
    </p>
    <?php endforeach; ?>
</body>
</html>
