<?php

abstract class Controller {

    /**
     * The name of the database table used for queries
     * @var Database
     */
    private $table;

    /**
     * The database variable used for queries
     * @var Database
     */
    private $db;

    /**
     * Construct a new Controller
     * @param int $table The name of the DB table used for queries
     */
    function __construct($table=null) {

        $this->db = Database::getInstance();
        $this->table = $table;

    }

}
