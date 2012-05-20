<?php

/**
 *  Represents a column in the table.  Columns have different types:
 *  0 - id column: just like a normal column (1), but should not be editable
 *  1 - normal column: is editable and should just be displayed like normal
 *  2 - lookup key: looks up the value in a lookup table, is editable in a select drop-down
 *  3 - lookup attribute: looks up the other values in the lookup table, is not editable
 *  4 - many table column: looks up the top 5 columns from a many table
 *  5 - lookup attribute lookup: the attibute column should also be lookedup in a further lookup table..
 */

class DSColumn {
    
    public $type;
    public $name;
    public $lookupTable;
    public $table;
    public $joinTable;
    
    /**
     *  Simple constructor to set the correct attributes for the column.
     *  Auto sets the types for 0, 1 or 2 if a lookup column is provided and type isn't otherwise specified.
     */
    
    function __construct($table, $name, $lookupTable = false, $type = -1, $joinTable = false) {
        $this->table = $table;
        $this->name = $name;
        if($this->name == 'id') {
            $this->type = 0;
        } else {
            if($lookupTable == false) {
                $this->type = 1;
            } else {
                $this->lookupTable = $lookupTable;
                if($type != -1) {
                    $this->type = $type;
                } else {
                    $this->type = 2;
                }
            }
        }
        $this->joinTable = $joinTable;
    }
    
    /**
     *  This function returns the jqGrid colModel string line for the column when building the page view
     *  Basically looks at the column type and then returns an appropriate enrty for that type.
     */
    
    function printColModelCell() {
        switch($this->type) {
            // 0 - id column
            case 0:
                $widthQuery = $this->table->db->query("SELECT MAX(LENGTH(".$this->name.")) AS width FROM ".$this->table->tableName);
                $widthQueryResults = $widthQuery->result_array();
                $width = $widthQueryResults[0]['width'];
                if($width < strlen($this->name)) { $width = strlen($this->name); }
                return "{name:'".$this->name."', width: ".$width.", index:'".$this->name."'},";
            break;
            // 1 - regular attribute column
            case 1:
                $widthQuery = $this->table->db->query("SELECT MAX(LENGTH(".$this->name.")) AS width FROM ".$this->table->tableName);
                $widthQueryResults = $widthQuery->result_array();
                $width = $widthQueryResults[0]['width'];
                if($width < strlen($this->name)) { $width = strlen($this->name); }
                return "{name:'".$this->name."', width: ".$width.", index:'".$this->name."', editable: true, edittype: 'text'},";
            break;
            // 2 - lookup key column
            case 2:
                //Run SQL to lookup drop-down options
                $selectOptions = $this->table->db->get($this->lookupTable);
                $selectOptionsObjStr = "";
                foreach($selectOptions->result() as $row) {
                    $selectOptionsObjStr .= $row->id.":"."'".$row->name."',";
                }
                $widthQuery = $this->table->db->query("SELECT MAX(LENGTH(name)) AS width FROM ".$this->lookupTable);
                $widthQueryResults = $widthQuery->result_array();
                $width = $widthQueryResults[0]['width'];
                if($width < strlen($this->name)) { $width = strlen($this->name); }
                return "{name:'".$this->name."', width: ".$width.", index:'".$this->name."', editable: true, edittype: 'select', editoptions: {value:{".$selectOptionsObjStr."}}},";
            break;
            // TODO: 3 - lookup attribute column
            case 3:
                $widthQuery = $this->table->db->query("SELECT MAX(LENGTH(".$this->name.")) AS width FROM ".$this->lookupTable);
                $widthQueryResults = $widthQuery->result_array();
                $width = $widthQueryResults[0]['width'];
                if($width < strlen($this->name)) { $width = strlen($this->name); }
                return "{name:'".$this->name."', width: ".$width.", index:'".$this->name."'},";
            break;
            // 4 - many table column
            case 4:
                return "{name:'".$this->name."', width: 20, index:'".$this->name."', sortable: false},";
            break;
            case 5:
                $widthQuery = $this->table->db->query("SELECT MAX(LENGTH(name)) AS width FROM ".$this->lookupTable);
                $widthQueryResults = $widthQuery->result_array();
                $width = $widthQueryResults[0]['width'];
                if($width < strlen($this->name)) { $width = strlen($this->name); }
                return "{name:'".$this->name."', width: ".$width.", index:'".$this->name."'},";
            break;
            default:
                return "{name:'".$this->name."', width: 20, index:'".$this->name."'},";
            break;
        
        }
    }
    
    function printHeaderName() {
        switch($this->type) {
            case 2:
                return ucfirst($this->lookupTable);
            break;
            case 3:
                return ucfirst($this->lookupTable.": ".$this->name);
            break;
            case 4:
                return ucfirst($this->name."...");
            break;
            case 5:
                return ucfirst($this->joinTable.": ".$this->lookupTable);
            break;
            default:
                return ucfirst($this->name);
            break;
        }
    }
}