<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=ucfirst($table->tableName)?> Table</title>
 
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
        shrinkToFit: true,
        height: 'auto',
        datatype: 'json',
        mtype: 'GET',
        cellEdit: true,
        cellsubmit: 'remote',
        cellurl: '<?=site_url('/datasheets/editcell').'/'.$this->uri->segment(3)?>',
        afterSubmitCell: function() { $('#list').trigger("reloadGrid"); return [true, '']; },
        editurl: '<?=site_url('/datasheets/editcell').'/'.$this->uri->segment(3)?>',
        colNames:[
        <?php foreach($table->columns as $field): ?>
        '<?=$field->printHeaderName()?>',
        <?php endforeach; ?>
        ],
        colModel :[
        <?php foreach($table->columns as $field): ?>
        <?=$field->printColModelCell()?>
            
        <?php endforeach; ?>
        ],
        pager: '#pager',
        rowNum:10,
        rowList:[10, 100, 1000],
        sortname: '<?=$table->columns[0]->name?>',
        sortorder: 'asc',
        viewrecords: true,
        gridview: true,
        caption: '<?=ucfirst($table->tableName)?>',
        subGrid: true,
        subGridRowExpanded: function(subGridDivId, rowId) {
            var subgrid_table_id, pager_id;
            subgrid_table_id = subGridDivId + '_t';
            pager_id = 'p_' + subgrid_table_id;            
            $('#' + subGridDivId).html('<table id="' + subgrid_table_id + '" ></table><div id="' + pager_id + '" ></div>');
            $('#' + subgrid_table_id).jqGrid({                
                url: jqSubGridOptions[DS_lastExpandCol].url + '&filterid=' + rowId,
                datatype: jqSubGridOptions[DS_lastExpandCol].datatype,
                colNames: jqSubGridOptions[DS_lastExpandCol].colNames,
                colModel: jqSubGridOptions[DS_lastExpandCol].colModel,
                rowNum: jqSubGridOptions[DS_lastExpandCol].rowNum,
                rowList:jqSubGridOptions[DS_lastExpandCol].rowList,
                sortname: jqSubGridOptions[DS_lastExpandCol].sortname,
                sortorder: jqSubGridOptions[DS_lastExpandCol].sortorder,
                height: jqSubGridOptions[DS_lastExpandCol].height,
                shrinkToFit: jqSubGridOptions[DS_lastExpandCol].shrinkToFit,
                caption: jqSubGridOptions[DS_lastExpandCol].caption
            });
            $('#' + subgrid_table_id).jqGrid('navGrid', '#' + pager_id,
                { edit: false, del: false, search: false }, {}, {}, {}, {}, {} 
            );
            $(window).bind('resize', function() {
                $('#' + subGridDivId + '_t').setGridWidth($(window).width() - 50);
            }).trigger('resize');
        }
    });
    $("#list").jqGrid('navGrid','#pager',
        { edit: false, del: false, search: false }, //options
        {}, // edit options
        { reloadAfterSubmit: true, closeAfterAdd: true }, // add options
        {}, // del options
        {}, // search options
        {}  // view options
    );
    $("#list").hideCol('subgrid');
    $(window).bind('resize', function() {
        $("#list").setGridWidth($(window).width());
    }).trigger('resize');
    
});
var jqSubGridOptions = new Array();
<?php foreach($table->subTables as $subTable): ?>
jqSubGridOptions.push({
    url: '<?=site_url('/datasheets/tabledata').'/'.$subTable->tableName.'?filtercol='.$table->tableName?>',
    cellEdit: true,
    cellsubmit: 'remote',
    cellurl: '<?=site_url('/datasheets/editcell').'/'.$this->uri->segment(3)?>',
    afterSubmitCell: function() { $('this').trigger("reloadGrid"); return [true, '']; },
    editurl: '<?=site_url('/datasheets/editcell').'/'.$this->uri->segment(3)?>',
    datatype: 'json',
    colNames: [
        <?php foreach($subTable->columns as $col): ?>
        '<?=$col->printHeaderName()?>',
        <?php endforeach; ?>
    ],
    colModel: [
        <?php foreach($subTable->columns as $col): ?>
        <?=$col->printColModelCell()?>
        
        <?php endforeach; ?>
    ],
    rowNum: 10,
    rowList:[10, 100, 1000],
    sortname: '<?=$subTable->columns[0]->name?>',
    sortorder: 'asc',
    height: 'auto',
    shrinkToFit: true,
    caption: '<?=ucfirst($subTable->tableName)?>'
});
<?php endforeach; ?>

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