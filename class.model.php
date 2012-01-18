<?php
print "<div>BoF ".rand()."</div>";
print "ENV VAR: ".getenv("VCAP_SERVICES");
define('_DEBUG_', isset($_REQUEST['dbg']));
    print _DEBUG_;
if(_DEBUG_) require_once("../lib.dbg/class.dbg.php");

/**
 * Model class for personal MVC framework
 * Only class with knowledge of the database connections
 *
 * @author jds
 */

class model
{
    private $conn;
    //private $tbl;
    //private $type;
    //private $cols;
    //private $where;
    //private $values;
    //private $updateOnDup;
    //private $rowCount;

    function __construct ($tbl=NULL, $type=NULL)
    {
        $services = getenv("VCAP_SERVICES");
        $services_json = json_decode($services,true);
        $mysql_config = $services_json["mysql-5.1"][0]["credentials"];
        define('_DB_NAME_', $mysql_config["name"]);
        define('_DB_USER_', $mysql_config["user"]);
        define('_DB_PASSWORD_', $mysql_config["password"]);
        define('_DB_HOST_', $mysql_config["hostname"]);
        define('_DB_PORT_', $mysql_config["port"]);

        $server = _DB_HOST_.':'._DB_PORT_;
        $this->conn = mysql_connect($server, _DB_USER_, _DB_PASSWORD_);
        print mysql_error();

        //if (_DEBUG_) dbg::msg("model.conn opened", __METHOD__);

        // set model.tbl to current table if it is passed on object initialization
        /*
        if(!is_null($tbl))
        {
            $tbl = '`'._DB_NAME_.'`.`'.$tbl.'`';
            $this->from($tbl);
            if (_DEBUG_) dbg::msg("model.tbl set to $tbl", __METHOD__);
        }
         */
        // set model.type to current type if it is passed on object initialization
        /*
        if(!is_null($type))
        {
            $this->type($type);
            if (_DEBUG_) dbg::msg("model.type set to $type", __METHOD__);
        }
         * 
         */
    }
}

$model = new model();
print "EoF";

?>