<?php
define('_DEBUG_', isset($_REQUEST['dbg']));
if(_DEBUG_) require_once("class.dbg.php");

/**
 * Model class for personal MVC framework
 * Only class with knowledge of the database connections
 *
 * @author jds
 */

class model
{
    private $conn;
    private $dbg;
    private $tbl;
    private $type;
    private $cols;
    private $where;
    private $values;
    private $updateOnDup;
    private $rowCount;

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

        if (_DEBUG_) dbg::msg("model.conn opened", __METHOD__);

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
    function __destruct ()
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        mysql_close($this->conn);
        if (_DEBUG_) dbg::msg("model.conn closed", __METHOD__);
    }

    public function from ($tbl)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $this->tbl = $this->validateTbl($tbl);
    }
    public function columns ($cols='*')
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $this->validateCols($cols);
    }
    public function where ($condition, $conjunction='AND')
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        try {
            $this->validateWhere($condition);
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function type ($type)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        if($type=='INSERTUPDATE')
        {
            $type='INSERT';
            $this->updateOnDup=true;
        }

        $this->type = $type;
    }
    public function query($retType='assoc')
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $validRetTypes = array(
            'array',
            'assoc',
            'field',
            'lengths',
            'object',
            'row'
            );

        if(in_array($retType, $validRetTypes))
        {
            $sql = $this->assemble();
            $result = mysql_query($sql);
            $this->rowCount = mysql_num_rows($result);
            print mysql_error();
            if (_DEBUG_) dbg::msg("Return query result.", __METHOD__);



            switch($retType)
            {
                case 'array':
                    $ret = mysql_fetch_array($result);
                    break;
                case 'assoc':
                    if (_DEBUG_) dbg::msg("Return type is assoc", __METHOD__);
                    // Begin Development Section

                    if($this->rowCount > 1) {
                        $ret =array();
                        $count = 0;

                        while($row=mysql_fetch_assoc($result))
                        {
                            $key = $row['variable'];
                            $ret[$key] = $row['value'];
                        }
                    }
                    else
                        $ret = mysql_fetch_assoc($result);

                    // End Development Section
                    if (_DEBUG_) dbg::msg("Result fetched.", __METHOD__);
                    break;
                case 'field':
                    $ret = mysql_fetch_field($result);
                    break;
                case 'lengths':
                    $ret = mysql_fetch_lengths($result);
                    break;
                case 'object':
                    $ret = mysql_fetch_object($result);
                    break;
                case 'row':
                    $ret = mysql_fetch_row($result);
                    break;
            };
            $this->reset();
            return $ret;
        }
        else
        {
            throw Exception("Return type must be ".implode(', ', $validRetTypes).".");
        }
    }
    public function values($vals)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $this->validateVals($vals);
    }
    public function assemble ()
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        switch($this->type)
        {
            case 'SELECT':
            case 'select':
                return "SELECT $this->cols FROM $this->tbl WHERE $this->where";
                break;
            case 'INSERT':
            case 'insert':
                if(!isset($this->tbl)) throw Exception("Table not set.");
                if(!isset($this->cols)) throw Exception("Cols not set.");
                if(!isset($this->values)) throw Exception("Values not set.");
                if($this->updateOnDup && !isset($this->where)) throw Exception("Where not set.");

                $sql = "INSERT INTO $this->tbl ($this->cols) VALUES ($this->values)";
                $this->updateOnDup ? $sql .= " ON DUPLICATE KEY UPDATE $this->where" : $sql .= '';
                return $sql;
                break;
            case 'UPDATE':
            case 'update':
                $colsArr = explode(',', $this->cols);
                $valsArr = explode(',', $this->values);

                if(count($colsArr)!=count($valsArr))
                    throw Exception("number of columns != number of values");
                else
                {
                    for($i=0; $i<count($colsArr); $i++)
                    {
                        if(!isset($sql))
                            $sql = "$colsArr[$i]=$valsArr[$i]";
                        else
                            $sql .= ",$colsArr[$i]=$valsArr[$i]";
                    }

                    return "UPDATE $this->tbl SET $sql WHERE $this->where";
                }
                break;
        }
    }
    public function numRows ()
    {
        return $this->rowCount;
    }
    
    private function validateTbl($tbl)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $pattern = "/`([^`]*)`\.`([^`]*)`/";

        if(preg_match_all($pattern, $tbl, $tokens)) {
            $dbTok = $tokens[1][0];
            $tblTok = $tokens[2][0];

            return '`'.$this->mysqlSanitize($dbTok).'`.`'
                    .$this->mysqlSanitize($tblTok).'`';
        }
        else {
            throw Exception("$tbl is not a valid `db`.'tableName'");
        }
    }
    private function validateCols($cols)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $pattern = "/`([^`]*)`,?/";

        if(preg_match_all($pattern, $cols, $tokens))
        {
            foreach($tokens[1] AS $col)
            {
                if(!isset($this->cols))
                    $this->cols = '`'.$this->mysqlSanitize($col).'`';
                else
                    $this->cols .= ",`".$this->mysqlSanitize($col)."`";
            }
        }
        else
            throw Exception("$cols is not a valid `col1`,`col2`");
    }
    private function validateWhere($where, $conjunction)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        if($where!==true) {
            $pattern = "/`([^`]*)`(\=| LIKE )'([^`]*)'/";

            if(preg_match_all($pattern, $where, $tokens))
            {
                $validConjuntion = array('and','AND','or','OR');

                if(strpos($where, ' LIKE '))
                    $operator = ' LIKE ';
                else
                    $operator = '=';

                $column = $tokens[1][0];
                $value = $tokens[3][0];

                if(!isset($this->where))
                    $this->where = "`".$this->mysqlSanitize($column)."`".$operator."'".$this->mysqlSanitize($value)."'";
                else
                {
                    if(in_array($validConjuction, $conjunction))
                        $this->where .= " $conjunction ".$condition;
                    else throw Exception("Invalid conjunction: $conjunction");
                }
            }
            else
                throw Exception("$where is not a valid `column`='value'");
      }
      else
        $this->where=true;
    }
    private function validateVals($vals)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $pattern = "/'([^']*)',?/";

        if(preg_match_all($pattern, $vals, $tokens))
        {
            foreach($tokens[1] AS $val)
            {
                if(!isset($this->values))
                    $this->values = "'".$this->mysqlSanitize($val)."'";
                else
                    $this->values .= ",'".$this->mysqlSanitize($val)."'";
            }
        }
        else
            throw Exception("$vals is not a valid `val1`,`val2`");
    }
    private function mysqlSanitize($dirty)
    {
        if (_DEBUG_) dbg::msg("Initialized", __METHOD__);
        /*****************************************/

        $clean = mysql_real_escape_string($dirty, $this->conn);
        if($clean)
        {
            if (_DEBUG_) dbg::msg("Return $clean", __METHOD__);
            return $clean;
        }
        else throw Exception("Attempt to sanitize $dirty failed.");
    }
    private function reset()
    {
        //UNSET($this->tbl);
        UNSET($this->type);
        UNSET($this->cols);
        UNSET($this->where);
        UNSET($this->values);
        UNSET($this->updateOnDup);
    }
    
    public function UNIT()
    {
        dbg::msg("<div>BoF ".rand()."</div>", __METHOD__);

        dbg::msg("<div>EoF</div>", __METHOD__);
    }
}

if(_DEBUG_)
{
    $model = new model();
    $model->UNIT();
}
?>