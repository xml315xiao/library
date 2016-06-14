<?php
/**
 * The Class of Database Supper PDO
 * @create-time 2016-06-13 16:23:00
 */
class DB
{
    protected $master_link;       // The PDO instance which connected to the master database.

    protected $slave_link;        // The PDO instance which connected to the slave database.

    protected $last_sql;          // The last sql exec.

    protected $last_params;       // The params of the last sql binded.

    protected $config = [         // The configs of connect to the database.
        'dsn'      => '',
        'hostname' => '',
        'database' => '',
        'username' => 'root',
        'password' => 'root',
        'charset'   => 'utf8',
        'prefix'   => '',
    ];

    protected $slave_config;       // The config of connect to the slave database.

    protected $transaction = 0;    // Use only master database when start transaction.


    /**
     * DB constructor.
     * Set the config.
     *
     * @param $config  array the master database config.
     * @param $slave_config array slave config.
     */
    public function __construct($config, $slave_config = array())
    {
        $this->config = array_replace_recursive($this->config, $config);
        $this->slave_config = $slave_config;

        $this->initialize($this->config);
    }

    /**
     * Init the master_link
     * @param $config  array
     */
    public function initialize($config)
    {
        if (! $this->master_link instanceof PDO) {
            $this->master_link = $this->db_connect($config);
        }
    }

    /**
     * Db connect
     * @param $config  array
     *
     * @return PDO
     */
    protected function db_connect($config)
    {
        try {
            $dsn = strlen($config['dsn']) > 0 ? $config['dsn'] : 'mysql:host='. $config['hostname']. ';dbname='. $config['database'];
            $dbh = new PDO($dsn, $config['username'], $config['password']);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->exec('SET NAMES ' . $dbh->quote($config['charset']));

        } catch (PDOException $exception) {
            exit ($exception->getMessage());
        }

        return $dbh;
    }

    /**
     * Get the PDO for query.
     *
     * @return PDO Instance
     */
    protected function get_query_link()
    {
        if ( $this->transaction > 0) {
            return $this->master_link;
        }

        if ( $this->slave_link instanceof PDO ) {
            return $this->slave_link;
        }

        if ( ! is_array($this->slave_config) OR count($this->slave_config) === 0 ) {
            return $this->master_link;
        }

        $slaveDbConfig = $this->slave_config;
        shuffle($slaveDbConfig);         // randomizes the order of the elements

        do {
            $config = array_shift($slaveDbConfig);
            $config = array_replace_recursive($this->config, $config);

            $this->slave_link = $this->db_connect($config);
            if ($this->slave_link instanceof PDO)
                return $this->slave_link;

        } while ( count($slaveDbConfig) > 0 );

        return $this->slave_link = $this->master_link;
    }

    /**
     * Get the prefix of table.
     */
    protected function get_prefix()
    {
        return $this->config['prefix'];
    }

    /**
     * Parse the SQL
     * eg: {{%user}} => cms_user
     * @param   $sql     String
     * @return  string
     */
    protected function quote_sql($sql)
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return '`' . $matches[3] . '`'; // Parse ColumnName
                } else {
                    return str_replace('%', $this->get_prefix(), '`' . $matches[2] . '`');// Parse TableName
                }
            },
            $sql
        );
    }

    /**
     * Execute SQL Insert Delete and Update.
     *
     * @param  $sql string
     * @param  $params array
     * @return int the rows affected.
     */
    public function execute($sql, $params = array())
    {
        $sql = $this->quote_sql($sql);
        $this->last_sql = $sql;
        $this->last_params = $params;

        try {
            $statement = $this->master_link->prepare($sql);
            $statement->execute($params);

            return $statement->rowCount();

        } catch (PDOException $exception) {

            return FALSE;
        }
    }

    /**
     * Query SQL
     *
     * @param   $sql        string
     * @param   $params     array
     * @return  mixed   success array else false.
     */
    public function query($sql, $params = array())
    {
        $sql = $this->quote_sql($sql);
        $this->last_sql = $sql;
        $this->last_params = $params;

        try {
            $statement = $this->get_query_link()->prepare($sql);
            $statement->execute($params);

            return $statement->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $exception) {

            return FALSE;
        }
    }

    /**
     * Execute SQL like COUNT、AVG、MAX、MIN
     * @param $sql
     * @param array $params
     * @return mixed    success array else false.
     */
    public function query_scalar($sql, $params = array())
    {
        $sql = $this->quote_sql($sql);
        $this->lastSql = $sql;
        $this->lastParams = $params;

        try {
            $statement = $this->get_query_link()->prepare($sql);
            $statement->execute($params);
            $data = $statement->fetch(PDO::FETCH_NUM);

            if (is_array($data) && isset($data[0])) {
                return $data[0];
            }

        } catch (PDOException $exception) {

            return FALSE;
        }
    }

    /**
     * Get the last inserted ID
     * PDO::lastInsertId
     * @param null $sequence
     * @return int|string
     */
    public function get_last_insert_id($sequence = null)
    {
        return $this->master_link->lastInsertId($sequence);
    }

    /**
     * Start transaction
     * @return void
     */
    public function begin_transaction()
    {
        ++$this->transaction;
        if ($this->transaction == 1)
            $this->master_link->beginTransaction();
    }

    /**
     * Commit transaction.
     * @return void
     */
    public function commit()
    {
        if ($this->transaction == 1)
            $this->master_link->commit();
        --$this->transaction;
    }

    /**
     * Roll Back transaction.
     * @return void
     */
    public function roll_back()
    {
        if ($this->transaction == 1) {
            $this->transaction = 0;
            $this->master_link->rollBack();
        } else {
            --$this->transaction;
        }
    }

    /**
     * Disconnect the database.
     */
    public function disconnect()
    {
        $this->master_link = null;
        $this->slave_link  = null;
    }

    /**
     * Get the Last SQL executed.
     * @return string
     */
    public function get_last_sql()
    {
        return $this->parse_sql([$this->last_sql, $this->last_params]);
    }

    /**
     * Parse sql with params bound
     * @param  $condition   array
     * eg parse_sql(array('select * from tableName where id=? or id=?', array(1, 3))
     * eg parse_sql(array('select * from tableName where name like :name', array(':name'=>'%jack'))
     * @return string
     */
    public function parse_sql($condition)
    {
        if (count($condition) == 1) {
            return $condition[0];
        }

        $sql = array_shift($condition);
        $count = substr_count($sql, '?');

        if (is_array($condition[0])) {
            $condition = $condition[0];
        }

        if ($count > count($condition)) {
            return $sql . "\n" . print_r($condition, TRUE);
        }

        for ($i = 0; $i < $count; $i++) {
            $sql = preg_replace('/\?/', $this->master_link->quote($condition[$i]), $sql, 1);
        }

        // replace :id
        $sql = preg_replace_callback('/:(\w+)/', function ($matches) use ($condition) {
            if (isset($condition[$matches[1]])) {
                return $this->master_link->quote($condition[$matches[1]]);
            } else if (isset($condition[':' . $matches[1]])) {
                return $this->master_link->quote($condition[':' . $matches[1]]);
            }
            return $matches[0];
        }, $sql);

        return $sql;
    }

}