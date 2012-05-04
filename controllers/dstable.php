<?php

/**
 *  This class represents the full displayed table with (1) fields, (2) lookup fileds, (3) lookup attributes & (4) many fields
 *  (3) & (4) are like pseudo-fields - they aren't 'actual' fields of the table in questions, but are looked up.
 *  (2) is special as only the lookup is real, but it's the lookup name that's actually shown.
 *
 *  Extended CI_Model so that I can use the standard CI classes, like query without having to pass references and stuff.  Also
 *  this is kinda like the model and stuff...
 */

class DSTable extends CI_Model {

    public $tableName;
    private $columns;
    
    function __construct($tableName) {
        
        parent::__construct();
        $this->tableName = $tableName;
        
    }
    
    /**
     *  Builds the query with the lookup selects and joins
     */
    
    private function _setTableQueryLookups() {
        //Array to build up all the select fields
        //$selectFields = array();
        
        // 1. Go through each column in table and see what extras we need
        $fields = $this->db->list_fields($this->tableName);
        foreach($fields as $field) {
            //Boolean switch to revert back to default if join can't be built
            $lookup = false;
            //If the field starts 'ref_' and ends '_id' then we'll try and join it
            if(preg_match('/ref_(.+)_id/', $field, $matches)) {
                $lookupTable = $matches[1];
                //Does the table exist, if so then we'll join to it, if not go back to default.
                if($this->db->table_exists($lookupTable)) {
                    $lookup = true;
                    $selectFields[] = $lookupTable.'.name AS '.$lookupTable.'_name';
                    $this->db->join($lookupTable, $lookupTable.'.id = '.$tableName.'.'.$field, 'left');
                } else {
                    //Go back to default just show the RAW field
                    $lookup = false;
                }
            }
            if(!$lookup) {
                // Fallback if the lookup building failed, just show the RAW field, no join needed.
                $selectFields[] = $tableName.'.'.$field;
            }
        }
        $this->db->select(implode(', ', $selectFields));
    }
    
    
}

