# Active Record

> An example of the minimum code to use:
```php
class Products extends ActiveRecord
{ }
```

> Defining a table name.

If the class name does not match the table name then this behavior can be changed by defining the static tableName method in the derived class. Example:
```php
class Products extends ActiveRecord {
    protected static function tableName() : string {
        return 'shop_product';
    }
}
```

> Determining the name of the primary key.

If the primary key name does not match the default name ("id") then this behavior can be changed by defining the static primaryKey method in the derived class. Example:
```php
class Products extends ActiveRecord {
    protected static function primaryKey() : string {
        return 'product_id';
    }
}
```

> Establishing a Database Connection.

To specify your connection to the database, you need to define the dbConnection static method in the derived class. Example:
```php
class Products extends ActiveRecord {
    protected static function dbConnection() {
        if (is_null(self::$db)) {
            try {
                self::$db = new \PDO('mysql:host=localhost;port=3306;dbname=test_shop_db', 'root', '');
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $ex) {
                echo 'Error: ' . $ex->getMessage() . '\n';
                self::$db = null;
            }
        }
    }
}
```
