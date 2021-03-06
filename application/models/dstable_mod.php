<?php

/**
 *  This class represents the full displayed table with (1) fields, (2) lookup fileds, (3) lookup attributes & (4) many fields
 *  (3) & (4) are like pseudo-fields - they aren't 'actual' fields of the table in questions, but are looked up.
 *  (2) is special as only the lookup is real, but it's the lookup name that's actually shown.
 *
 *  Extended CI_Model so that I can use the standard CI classes, like query without having to pass references and stuff.  Also
 *  this is kinda like the model and stuff...
 */

class DSTable_Mod extends CI_Model {

    // Table meta
    public $tableName;
    public $columns;
    
    // Table data?
    public $count;
    public $totalPages;
    public $page;
    public $rows;
    
    function __construct() {
        
        parent::__construct();
        
        include('dscolumn.php');
        $this->columns = array();
    }
    
    /**
     *  This sets the columns for the named table
     */
    
    function setTable($tableName) {
        
        $this->tableName = $tableName;
        
        /* Get basic information on the table - fields & lookup tables */
        $fields = $this->db->list_fields($tableName);
        // For each field in the table
        foreach($fields as $field) {
            $islookup = false;
            // Check to see if the field is a lookup field
            if(preg_match('/ref_(.+)_id/', $field, $matches)) {
                // If so, get the table this links to
                $lookupTable = $matches[1];
                // Check the table exists
                if($this->db->table_exists($lookupTable)) {
                    $islookup = true;
                    // If so then store the field as a lookup field (type 2)
                    $this->columns[] = new DSColumn($this, $field, $lookupTable);
                    // THEN: get each other 'lookup attribute' column from the lookup table
                    // All columns other than name or id
                    $lookUpCols = $this->db->list_fields($lookupTable);
                    // Get all type 3 lookup attribute columns
                    $attributeCols = array_diff_key($lookUpCols, array('id', 'oper'));
                    foreach($attributeCols as $attribCol) {
                        // Is it also a lookup key column?
                        if(preg_match('/ref_(.+)_id/', $attribCol, $matches)) {
                            $attLookupTable = $matches[1];
                            if($this->db->table_exists($attLookupTable)) {
                                $this->columns[] = new DSColumn($this, $attribCol, $attLookupTable, 5, $lookupTable);
                            } else {
                                $this->columns[] = new DSColumn($this, $attribCol, $lookupTable, 3);
                            }
                        } else {
                            $this->columns[] = new DSColumn($this, $attribCol, $lookupTable, 3);
                        }
                    }
                } else {
                    // Else it's just a regular field;
                    $isLookup = false;
                }
            }
            if(!$islookup) {
                // Else it's just a regular field
                $this->columns[] = new DSColumn($this, $field);
            }
            
        }
        // Get the MANY-TABLES!
        // Use the info_schema to find the tables which have refs to it
        //  THEN iterate through the tables and create many columns for them.
        $dsn = 'mysql://root:root@localhost/information_schema';
        $infoDB = $this->load->database($dsn, TRUE);
        $query = $infoDB->query("SELECT COLUMN_NAME, TABLE_NAME FROM COLUMNS WHERE COLUMN_NAME = 'ref_".$this->tableName."_id' AND TABLE_SCHEMA = 'datasheets1'");
        foreach($query->result() as $row) {
            $this->columns[] = new DSColumn($this, $row->TABLE_NAME, $row->TABLE_NAME, 4);
        }
        $infoDB->close();
    }
    
    function getData($rows, $page, $sidx, $sord) {
        // calculate the number of rows for the query. We need this for paging the result
        $this->count = (string) $this->db->count_all($this->tableName);
        // calculate the total pages for the query 
        if( $this->count > 0 && $rows > 0) { 
            $this->totalPages = (string) ceil($this->count / $rows); 
        } else { 
            $this->totalPages = (string) 0; 
        }
        // if for some reasons the requested page is greater than the total 
        // set the requested page to total page
        $this->page = $page;
        if ($this->page > $this->totalPages) {
            $this->page = $this->totalPages;
        }
        // calculate the starting position of the rows 
        $start = $rows * $page - $rows;
        if($start < 0 ) {
            $start = 0;
        }
        $this->db->order_by($sidx, $sord);
        $this->db->limit($rows, $start);
        $selectLine = array();
        $joins = array();
        $manyTables = array();
        foreach($this->columns as $column) {
            switch($column->type) {
                case 1:
                    $selectLine[] = $this->tableName.'.'.$column->name;
                break;
                case 2:
                    $selectLine[] = $column->lookupTable.'.name AS '.$column->lookupTable.'_name';
                    $joins[] =  array($column->lookupTable, $this->tableName.'.'.$column->name.' = '.$column->lookupTable.'.id');
                    //$this->db->join($column->lookupTable, $this->tableName.'.'.$column->name.' = '.$column->lookupTable.'.id', 'left');
                break;
                case 3:
                    $selectLine[] = $column->lookupTable.".".$column->name." AS ".$column->lookupTable."_".$column->name;
                    //$this->db->join($column->lookupTable, $this->tableName.'.'.$column->name.' = '.$column->lookupTable.'.id', 'left');
                break;
                case 4:
                    //Lists all the many lookup columns, this will get added to the data array.
                    $manyTables[] = $column->lookupTable;
                break;
                case 5:
                    $selectLine[] = $column->lookupTable.".name AS ".$column->lookupTable."_name";
                    $joins[] = array($column->lookupTable, $column->joinTable.'.'.$column->name.' = '.$column->lookupTable.'.id');
                break;
                default:
                    $selectLine[] = $this->tableName.'.'.$column->name;
                break;
            }
        }
        $this->db->select(implode(', ', $selectLine));
        foreach($joins as $join) {
            $this->db->join($join[0], $join[1], 'left');
        }
        $query = $this->db->get($this->tableName);
    
        $jqRows = array();
        // For each row in the main table results
        foreach($query->result_array() as $row) {
            $manyCols = array();
            // For each 'many column' for this table...
            foreach($manyTables as $manyTable) {
                // Run a query for using row id to filter
                $this->db->select("name");
                $this->db->where("ref_".$this->tableName."_id", $row['id']);
                $this->db->limit(5);
                $manyQuery = $this->db->get($manyTable);
                $manyString = array();
                //For each row in the results
                foreach($manyQuery->result() as $manyRow) {
                    // Concatenate into a single row (like transpose)
                    $manyString[] = $manyRow->name;
                }
                $manyCols[] = '<input type="button" value="+" onclick=\'$("#list").toggleSubGridRow("'.$row['id'].'");\'/> '.implode(", ", $manyString);
            }
            $rowObj = array('id' => $row['id'], 'cell' => array_merge(array_values($row), $manyCols));
            $jqRows[] = $rowObj;
        }
        return $jqRows;
    }
    
    function updateRow($id, $editedCells) {
        $this->db->where('id', $id);
        $this->db->update($this->tableName, $editedCells);
    }
    
    function insertRow($editedCells) {
        $this->db->insert($this->tableName, $editedCells);
    }

    
    function dumpTable() {
        echo "Table Name: ".$this->tableName."\n";
        foreach($this->columns as $column) {
            echo " > Field: ".$column->name.", ".$column->type.", ".$column->lookupTable."\n";
        }
    }
    
    
    
}

