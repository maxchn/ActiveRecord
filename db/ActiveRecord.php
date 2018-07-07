<?php namespace db;

use db\Config;

abstract class ActiveRecord
{
    private const MAX_ELEMENTS_IN_FIND = 1; // Max number of records in the sample
    protected static $db = null;            // Connection to the database

    protected $primaryKey = null;           // Primary key
    protected $properties = [];             // Array of properties related to the table
    protected $modified = false;            // Variable that stores whether the object was modified or not

    public function __set($name, $value)
    {
        // If the specified value is the primary key, we store it in a separate variable
        if ($name == self::getPrimaryKey()) {
            $this->primaryKey = $value;
        } else {
            $this->properties[$name] = $value;
        }

        $this->modified = true;
    }

    public function __get($name)
    {
        // If the requested property is the primary key, then return it
        if ($name == self::getPrimaryKey()) {
            return $this->primaryKey;
        }

        // Otherwise the requested property is not a primary key and we check its presence in the property array and, 
		// if it is not, return null, otherwise we return its value        
        if (!isset($this->properties[$name])) {            
            return null;
        }

        return $this->properties[$name];
    }

    // Inserting a new record into the table
    private function insert()
    {
        $propertiesStr = '';
        $valuesStr = '';
        $params = array();

        foreach ($this->properties as $propName => $propValue) {
            $propertiesStr .= '`' . $propName . '`,';
            $valuesStr .= '?,';
            $params[] = $propValue;
        }

        $propertiesStr = rtrim($propertiesStr, ',');
        $valuesStr = rtrim($valuesStr, ',');

        $query = 'INSERT INTO `' . self::getTableName() . '` (' . $propertiesStr . ') VALUES(' . $valuesStr . ');';
        $res = self::execute($query, $params, 'id');

        if (!is_null($res)) {
            if (!is_array($res)) {
                $this->primaryKey = $res;
            }
        }

        return $res;
    }

    // Update table entry
    private function update()
    {
        $modifiedPropertiesStr = '';
        $params = array();

        foreach ($this->properties as $propName => $propValue) {
            $modifiedPropertiesStr .= ('`' . $propName . '`=?,');
            $params[] = $propValue;
        }

        $modifiedPropertiesStr = rtrim($modifiedPropertiesStr, ',');

        $query = 'UPDATE `' . self::getTableName() . '` SET ' . $modifiedPropertiesStr . ' WHERE ' . self::getPrimaryKey() . '=\'' . $this->primaryKey . '\';';
        $res = self::execute($query, $params, 'count');

        return $res === TRUE;
    }

    // Deleting an entry from a table
    public function delete()
    {
        // Check the presence of the primary key in the case of its absence, throw an exception
        if (is_null($this->primaryKey)) {
            throw new \Exception('ID not found! ID is required for this action!');
        }

        $query = 'DELETE FROM `' . self::getTableName() . '` WHERE ' . self::getPrimaryKey() . "='" . $this->primaryKey . "';";
        $res = self::execute($query, array(), 'count');

        $this->primaryKey = null;
        return $res === TRUE;
    }

    // Saving changes
    public function save()
    {
        // Save changes only if the current object has been modified
        if ($this->modified) {
            // If the primary key is null it means it's a new record and it needs to be inserted into the table 
			// otherwise it's not a new record and it needs to be updated            
            if (is_null($this->primaryKey)) {
                $result = $this->insert();
            } else {
                $result = $this->update();
            }
        }

        $this->modified = false;
        return isset($result) ? $result : false;
    }

    // Search for the first line that satisfies the condition
    public static function find(string $condition, array $params)
    {
        if (empty($condition) || count($params) == 0) {
            throw new \InvalidArgumentException("Arguments must not be empty!");
        }

        $query = 'SELECT * FROM `' . self::getTableName() . '` WHERE ' . $condition . " LIMIT " . self::MAX_ELEMENTS_IN_FIND;
        $res = self::execute($query, $params, 'single');

        if (is_array($res)) {


            return self::buildObject($res);
        } else {
            return null;
        }
    }

    // Search for a string with the specified value of the primary key
    public static function findByPk($id, string $condition = '', array $params = [])
    {
        $query = 'SELECT * FROM `' . self::getTableName() . '` WHERE ' . self::getPrimaryKey() . '=' . $id;

        if (!empty($condition) && count($params) > 0) {
            $query .= (' AND ' . $condition);
        }

        $res = self::execute($query, $params, 'single');

        if (is_array($res)) {
            return self::buildObject($res);
        } else {
            return null;
        }
    }

    // Search for all rows that satisfy the condition
    public static function findAll(string $condition = '', array $params = [])
    {
        $query = 'SELECT * FROM `' . self::getTableName() . '`';

        if (!empty($condition) && count($params) > 0) {
            $query .= ' WHERE ' . $condition;
        }

        $res = self::execute($query, $params, 'list');

        if (is_array($res)) {
            foreach ($res as $value) {
                if (is_array($value)) {
                    $result[] = self::buildObject($value);
                }
            }

            return isset($result) ? $result : null;
        } else {
            return null;
        }
    }

    // Determining the number of rows satisfying the condition
    public static function count(string $condition = '', array $params = [])
    {
        $query = 'SELECT count(*) as \'count\' FROM `' . self::getTableName() . '`';

        if (!empty($condition) && count($params) > 0) {
            $query .= ' WHERE ' . $condition;
        }

        $res = self::execute($query, $params, 'single');

        return is_null($res) ? null : $res;
    }

    public static function rawQuery(string $query, array $params)
    {
        self::setDatabase();

        if (self::checkDatabase() === TRUE) {
            try {
                $stmt = self::$db->prepare($query);

                try {
                    $res = $stmt->execute($params);
                    $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } catch (\PDOException $ex) {
                    echo 'Error: ' . $ex->getMessage() . '\n';
                }
            } catch (\PDOException $ex) {
                echo 'Error: ' . $ex->getMessage() . '\n';
            } finally {
                self::$db = null;
            }
        }

        return isset($res) ? $res : null;
    }

    // Query execution
    private static function execute($query, $params = [], $returningData = false)
    {
        self::setDatabase();

        if (self::checkDatabase() === TRUE) {
            try {
                $stmt = self::$db->prepare($query);

                try {
                    self::$db->beginTransaction();
                    $count = $stmt->execute($params);

                    if ($returningData == 'id') {
                        $res = self::$db->lastInsertId();
                    }

                    self::$db->commit();

                    if ($returningData == 'single') {
                        $res = $stmt->fetch(\PDO::FETCH_ASSOC);
                    } else if ($returningData == 'list') {
                        $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    } else if ($returningData == 'count') {
                        $res = $count;
                    }
                } catch (\PDOException $ex) {
                    self::$db->rollback();
                    echo 'Error: ' . $ex->getMessage() . '\n';
                }

                if ($returningData !== false) {
                    if (isset($res)) {
                        return $res;
                    }
                }
            } catch (\PDOException $ex) {
                echo 'Error: ' . $ex->getMessage() . '\n';
            } finally {
                self::$db = null;
            }
        }
    }

    // Initializing an object with initial values
    protected function initialize($data)
    {
        if (is_array($data)) {
            foreach ($data as $propName => $propValue) {
                if ($propName == $this->getPrimaryKey()) {
                    $this->primaryKey = $propValue;
                } else {
                    $this->properties[$propName] = $propValue;
                }
            }

            $this->modified = false;
        }
    }

    // Creation and filling of object data
    private static function buildObject($data)
    {
        $className = get_called_class();
        $newObject = new $className;
        $newObject->initialize($data);

        return $newObject;
    }

    // Getting the name of the table
    private static function getTableName(): string
    {
        $methods = get_class_methods(get_called_class());
        if (array_search("tableName", $methods) !== FALSE) {
            return static::tableName();
        } else {
            $className = strtolower(get_called_class());
            $idx = strripos($className, '\\');

            if ($idx !== FALSE) {
                $className = substr($className, $idx + 1);
            }

            return $className;
        }
    }

    // Obtaining the name of the primary key
    private static function getPrimaryKey(): string
    {
        $methods = get_class_methods(get_called_class());
        if (array_search("primaryKey", $methods) !== FALSE) {
            return static::primaryKey();
        } else {
            return "id";
        }
    }

    // Checking connection to the database
    private static function checkDatabase()
    {
        return is_null(self::$db) ? false : true;
    }

    // Connection to the database
    protected static function setDatabase()
    {
        $methods = get_class_methods(get_called_class());
        if (array_search("dbConnection", $methods) !== FALSE) {
            static::dbConnection();
        } else {
            if (is_null(self::$db)) {
                try {
                    self::$db = new \PDO(Config::getConnectionString(Config::DEFAULT_DB_NAME), Config::DEFAULT_USER, Config::DEFAULT_PASS);
                    self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                } catch (\PDOException $ex) {
                    echo 'Error: ' . $ex->getMessage() . '\n';
                    self::$db = null;
                }
            }
        }
    }
}