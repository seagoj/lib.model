<?php
print rand();
define(_DEBUG_, isset($_REQUEST['debug']));
    print _DEBUG_;

//if(_DEBUG_) require_once("../lib.dbg/class.dbg.php");

/**
 * Model class for personal MVC framework
 * Only class with knowledge of the database connections
 *
 * @author jds
 */

class model
{
    private $conn;
    private $tbl;
    private $type;
    private $cols;
    private $where;
    private $values;
    private $updateOnDup;
    private $rowCount;

    function __construct ($tbl=NULL, $type=NULL)
    {
        print "begin model";

        //if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $services = getenv("VCAP_SERVICES");
        $services_json = json_decode($services,true);
        $mysql_config = $services_json["mysql-5.1"][0]["credentials"];
        define('_DB_NAME_', $mysql_config["name"]);
        define('_DB_USER_', $mysql_config["user"]);
        define('_DB_PASSWORD_', $mysql_config["password"]);
        define('_DB_HOST_', $mysql_config["hostname"]);
        define('_DB_PORT_', $mysql_config["port"]);

        // set model.conn to reference to mysql connection

        $server = _DB_HOST_.':'._DB_PORT_;
        print $server;
        $this->conn = mysql_connect($server, _DB_USER_, _DB_PASSWORD_);
        print mysql_error();
        if($this->conn) print "success";
        else print "fail";

        //if (_DEBUG_) dbg::msg("model.conn opened", __METHOD__);

        // set model.tbl to current table if it is passed on object initialization
        if(!is_null($tbl))
        {
            $tbl = '`'._DB_NAME_.'`.`'.$tbl.'`';
            $this->from($tbl);
            if (_DEBUG_) dbg::msg("model.tbl set to $tbl", __METHOD__);
        }
        // set model.type to current type if it is passed on object initialization
        if(!is_null($type))
        {
            $this->type($type);
            if (_DEBUG_) dbg::msg("model.type set to $type", __METHOD__);
        }
    }

    public function unitTest()
    {
       
        try
        {
            $select = new model();
            print "EOF";
            /*
            $select = new model();
            $select->from('`464119_nxtlvl`.`config`');
            $select->type('SELECT');
            $select->columns('`value`');
            $select->where("`variable`='companyName'");
            $valid = "SELECT `value` FROM `464119_nxtlvl`.`config` WHERE `variable`='companyName'";
            if($select->assemble() != $valid)
                print "SELECT method failed unit test.";
            else
                print "SELECT method passed unit test.";

            $insert = new model();
            $insert->from('`464119_nxtlvl`.`config`');
            $insert->type('INSERT');
            $insert->columns('`variable`,`value`');
            $insert->values("'companyName','test'");
            $valid = "INSERT INTO `464119_nxtlvl`.`config` (`variable`,`value`) VALUES ('companyName','test')";
            if($insert->assemble() != $valid)
                print "INSERT method failed unit test.";
            else
                print "INSERT method passed unit test.";

            $update = new model();
            $update->from('`464119_nxtlvl`.`config`');
            $update->type('UPDATE');
            $update->columns('`variable`,`value`');
            $update->values("'companyName','test'");
            $update->where(true);
            $valid = "UPDATE `464119_nxtlvl`.`config` SET `variable`='companyName',`value`='test' WHERE 1";
            if($update->assemble() != $valid)
                print "UPDATE method failed unit test.";
            else
                print "UPDATE method passed unit test.";
         * 
         */
        }
        catch (Exception $e)
        {
            print $e;
        }
    }
}

print "BoF";
$model = new model();
print "EoF";

?>