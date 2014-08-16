<?php

/**
 *  This class represents the full displayed table with (1) fields, (2) lookup fileds, (3) lookup attributes & (4) many fields
 *  (3) & (4) are like pseudo-fields - they aren't 'actual' fields of the table in questions, but are looked up.
 *  (2) is special as only the lookup is real, but it's the lookup name that's actually shown.
 *
 *  The only features it was using from CI_Model was the 'db' and 'load' objects, so I'll
 *  need to pass them in to keep working the way I am...
 */

class DSTable {

    // CI refs
    public $db;
    public $load;

    // Table meta
    public $tableName;
    /**
     *  @var array $columns Array of DSColumn objects which describe the table
     */
    public $columns;
    /**
     *  @var array $subTables   Array of DSTable objects which represent the sub-tables (many columns) of this table.
     */
    public $subTables;
    
    // Table data info
    public $count;
    public $totalPages;
    public $page;
    public $rows;
    
    /**
     * Constructor which create the DataSheets Table object used to show a table.
     *
     * @param CI_Database $db   The database to make SQL calls to populate the table object
     * @param CI_Loader $load   Reference to the CI loader class.  Currently only needed to run a separate query on the information_schema DB
     * @param String $tableName The name of the table to load from the DB into the object.
     *
     */
    
    function __construct($db, $load, $tableName) {
        $this->columns = array();
        $this->subTables = array();
        
        $this->db = $db;
        $this->load = $load;
        
        $this->tableName = $tableName;
        
        // Gets the raw fields for this DSTable which are expanded on
        $fields = $this->db->list_fields($tableName);
        // For each field in the table
        foreach($fields as $field) {
            $islookup = false;
            // Check to see if the field is a lookup field (based on column name)
            if(preg_match('/ref_(.+)_id/', $field, $matches)) {
                // If so, get the tablename this links to
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
            $this->subTables[] = new DSTable($this->db, $this->load, $row->TABLE_NAME);
        }
        $infoDB->close();
    }
    
    /**
     *  Returns the data for the table in an associated array that can be converted to JSON easily.
     *
     *  @param int $rows    The number of rows to return
     *  @param int $page    The page of rows to return
     *  @param int $sidx    The column to sort the data on
     *  @param string $sord The sort order, either 'asc' or 'desc' for ascending or descending orders repectively.
     *  @param string $filtercol    The column to filter the data on (WHERE)
     *  @param string $filterid     The value to filter the column on
     *
     *  @return array The associated array which has the same structure as a standard JSON jqGrid data object
     */
    
    function getData($rows, $page, $sidx, $sord, $filtercol = false, $filterid = false) {
        // STEP 1 - Check and set the rows, pages and sorting parameters
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
        // STEP 2 - Build up the query components depending on the columns of the table: Select line and joins
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
                    //DEPRECATED, should be using subTables now
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
        // STEP 3 - Add filter conditions if specified (WHERE component of SQL)
        if($filtercol && $filterid) {
            $this->db->where($this->tableName.'.ref_'.$filtercol.'_id', $filterid);
        }
        // STEP 4 - Run the SQL query and count rows returned!
        $query = $this->db->get($this->tableName);
    
        // calculate the number of rows for the query. We need this for paging the result
        $this->count = (string) $query->num_rows();
        // calculate the total pages for the query 
        if( $this->count > 0 && $rows > 0) { 
            $this->totalPages = (string) ceil($this->count / $rows); 
        } else { 
            $this->totalPages = (string) 0; 
        }
        // STEP 5 - Modify the query results (the data) to add Many tables data summary
        $jqRows = array();
        // For each row in the main table results
        foreach($query->result_array() as $row) {
            $manyColInd = 0;
            $manyCols = array();
            // For each 'sub table' for this table...
            foreach($this->subTables as $subTable) {
                $manyCols[] = '<input type="button" value="+" onclick=\'DS_lastExpandCol = '.$manyColInd++.'; $("#list").toggleSubGridRow("'.$row['id'].'");\'/> '.$subTable->getAsManyColumn($row['id'], $this->tableName);
            }
            $rowObj = array('id' => $row['id'], 'cell' => array_merge(array_values($row), $manyCols));
            $jqRows[] = $rowObj;
        }
        return $jqRows;
    }
    
    /**
     *  Display's the table as a cell in a row for the Many Columns
     *  @param int $id   The id to filter the lookup column used here
     *  @param string $looupCol The name of the column (table) to filter on.
     *
     *  @return string  The comma separted names of the top 5 results.
     */
    
    function getAsManyColumn($id, $lookupCol) {
        $this->db->select("name");
        $this->db->where('ref_'.$lookupCol.'_id', $id);
        $this->db->limit(5);
        $manyQuery = $this->db->get($this->tableName);
        $manyString = array();
        //For each row in the results
        foreach($manyQuery->result() as $manyRow) {
            // Concatenate into a single row (like transpose)
            $manyString[] = $manyRow->name;
        }
        return implode(", ", $manyString);
    }
    
    function getSubTable($tableName) {
        foreach($this->subTables as $subTable) {
            if($subTable->tableName == $tableName) {
                return $subTable;
            }
        }
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

