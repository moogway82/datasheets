<!DOCTYPE html>
<html>
<head>
    <title>Contents</title>
</head>

<body>
    <h1>Welcome to Datasheets</h1>
    <p>Here you will be able to select from a table below...</p>
    <ul>
    <?php foreach($tables as $table): ?>
        <li><?=anchor('/datasheets/table/'.$table, $table)?></li>
    <?php endforeach ?>
    </ul>
    <p>Create table</p>
    <?=form_open('datasheets/createtable')?>
        <label for="name">Name</label>
        <input name="name" id="name" />
        <input name="submit" id="submit" type="submit" value="Create" />
    </form>
</body>
</html>
