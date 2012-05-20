<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=ucfirst($title)?> Table</title>
 
<link rel="stylesheet" type="text/css" media="screen" href="<?=base_url()?>css/ui-lightness/jquery-ui-1.8.19.custom.css" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=base_url()?>css/ui.jqgrid.css" />
 
<style type="text/css">
html, body {
    margin: 0;
    padding: 0;
}
</style>
 
<script src="<?=base_url()?>js/jquery-1.7.2.min.js" type="text/javascript"></script>
<script src="<?=base_url()?>js/i18n/grid.locale-en.js" type="text/javascript"></script>
<script src="<?=base_url()?>js/jquery.jqGrid.min.js" type="text/javascript"></script>
 
<script type="text/javascript">
$(function(){ 
    $("#list").jqGrid({
        url:'<?=site_url('/datasheets/tabledata').'/'.$this->uri->segment(3)?>',
        datatype: 'json',
        mtype: 'GET',
        cellEdit: true,
        cellsubmit: 'remote',
        cellurl: '<?=site_url('/datasheets/editcell').'/'.$this->uri->segment(3)?>',
        editurl: '<?=site_url('/datasheets/editcell').'/'.$this->uri->segment(3)?>',
        colNames:[
        <?php foreach($tablefields as $field): ?>
            '<?=$field?>',
        <?php endforeach; ?>
        ],
        colModel :[
        <?php foreach($tablefields as $field): ?>
            <?php if($field == 'id'): ?>
        {name:'<?=$field?>', index:'<?=$field?>', width:200},
            <?php else: ?>
        {name:'<?=$field?>', index:'<?=$field?>', width:200, editable: true, edittype: 'text'}, 
            <?php endif; ?>
        <?php endforeach; ?>
        ],
        pager: '#pager',
        rowNum:10,
        rowList:[10, 100, 1000],
        sortname: '<?=$tablefields[0]?>',
        sortorder: 'asc',
        viewrecords: true,
        gridview: true,
        caption: '<?=ucfirst($title)?>'
    });
    jQuery("#list").jqGrid('navGrid','#pager',
        { edit: false, del: false, search: false }, //options
        {}, // edit options
        { height:280, reloadAfterSubmit: true, closeAfterAdd: true }, // add options
        {}, // del options
        {}, // search options
        {}  // view options
    );
}); 
</script>
 
</head>
<body>
    <table id="list">
        <tr><td/></tr>
    </table> 
    <div id="pager"></div>
    <p>Add a column:</p>
    <?=form_open('datasheets/addcolumn/'.$this->uri->segment(3))?>
        <p>
            <label for="name">Name</label>
            <input name="name" id="name" />
        </p>
        <p>
            <label for="type">Type</label>
            <select name="type" id="type">
                <option value="text">Text</option>
            </select>
        </p>
        <p>
            <input name="submit" id="submit" type="submit" value="Create" />
        </p>
    </form>
    <p><?=anchor('/datasheets/tables', '< Back to table list')?></p>
</body>
</html>