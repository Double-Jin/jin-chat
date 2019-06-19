# DataBase-Mysqli
EasySwoole has provide an coroutine-save orm which is base on https://github.com/ThingEngineer/PHP-MySQLi-Database-Class
。

EasySwoole/mysqli：https://github.com/easy-swoole/mysqli 

## Install
```
composer require easyswoole/mysqli
```
## Base Usage
```
use EasySwoole\Mysqli\Config;
use EasySwoole\Mysqli\Mysqli;
$conf = new Config([
    'host'=>'',
    'user'=>'',
    'password'=>'',
    'database'=>'',
    'port'=>''
]);

$db = new Mysqli($conf);
$data = $db->get('test');
```

## Mysqli Pool
declare MysqlPool
```
use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\EasySwoole\Config;

class MysqlPool extends AbstractPool
{
    protected function createObject()
    {
        // TODO: Implement createObject() method.
        $dbConf = new Config([
            //config array
        ]);
        return new MysqlDbObject($dbConf);
    }
}
```
declare MysqlDbObject
```
use EasySwoole\Component\Pool\PoolObjectInterface;
use EasySwoole\Mysqli\Mysqli;

class MysqlDbObject extends Mysqli implements PoolObjectInterface
{
    /*
        call when MysqlDbObject is bean recycle 
    */
    function gc()
    {
        /*
            call this is in order to prevent when exception occur but you did not 
            catch the eror an forget to rollback you operator
        */
        $this->rollback();
        $this->commit();
        $this->resetDbStatus();
        $this->getMysqlClient()->close();
    }
    
    /*
        call when MysqlDbObject is return to pool
    */
    function objectRestore()
    {
    
        /*
            call this is in order to prevent when exception occur but you did not 
            catch the eror an forget to rollback you operator
        */
        $this->rollback();
        $this->commit();
        $this->resetDbStatus();
    }
    /*
        call when MysqlDbObject is pop from pool
        return true mean this object is active
    */
    function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }
}
```

> pool is implement at easyswoole/component

## Usage List
### insert
```
$data = Array ("login" => "admin",
               "firstName" => "John",
               "lastName" => 'Doe'
);
$id = $db->insert ('users', $data);
if($id)
    echo 'user was created. Id=' . $id;
    
//Insert with functions use
$data = Array (
	'login' => 'admin',
    'active' => true,
	'firstName' => 'John',
	'lastName' => 'Doe',
	'password' => $db->func('SHA1(?)',Array ("secretpassword+salt")),
	// password = SHA1('secretpassword+salt')
	'createdAt' => $db->now(),
	// createdAt = NOW()
	'expires' => $db->now('+1Y')
	// expires = NOW() + interval 1 year
	// Supported intervals [s]econd, [m]inute, [h]hour, [d]day, [M]onth, [Y]ear
);

$id = $db->insert ('users', $data);
if ($id)
    echo 'user was created. Id=' . $id;
else
    echo 'insert failed: ' . $db->getLastError();

//Insert with on duplicate key update
$data = Array ("login" => "admin",
               "firstName" => "John",
               "lastName" => 'Doe',
               "createdAt" => $db->now(),
               "updatedAt" => $db->now(),
);
$updateColumns = Array ("updatedAt");
$lastInsertId = "id";
$db->onDuplicate($updateColumns, $lastInsertId);
$id = $db->insert ('users', $data);
    
```

### Update Query
```
$data = Array (
	'firstName' => 'Bobby',
	'lastName' => 'Tables',
	'editCount' => $db->inc(2),
	// editCount = editCount + 2;
	'active' => $db->not()
	// active = !active;
);
$db->where ('id', 1);
if ($db->update ('users', $data))
    echo $db->count . ' records were updated';
else
    echo 'update failed: ' . $db->getLastError();
```    
update() also support limit parameter:
```
$db->update ('users', $data, 10);
// Gives: UPDATE users SET ... LIMIT 10
```

### Select Query
```
$users = $db->get('users'); //contains an Array of all users 
$users = $db->get('users', 10); //contains an Array 10 users
```
or select with custom columns set. Functions also could be used

```
$cols = Array ("id", "name", "email");
$users = $db->get ("users", null, $cols);
if ($db->count > 0)
    foreach ($users as $user) { 
        print_r ($user);
    }
```
or select just one row
```
$db->where ("id", 1);
$user = $db->getOne ("users");
echo $user['id'];

$stats = $db->getOne ("users", "sum(id), count(*) as cnt");
echo "total ".$stats['cnt']. "users found";
```
or select one column value or function result
```
$count = $db->getValue ("users", "count(*)");
echo "{$count} users found";
```
select one column value or function result from multiple rows:
```
$logins = $db->getValue ("users", "login", null);
// select login from users
$logins = $db->getValue ("users", "login", 5);
// select login from users limit 5
foreach ($logins as $login)
    echo $login;
```
### Running raw SQL queries
```
$users = $db->rawQuery('SELECT * from users where id >= 100');
foreach ($users as $user) {
    print_r ($user);
}
```

### Where / Having Methods
`where()`, `orWhere()`, `having()` and `orHaving()` methods allows you to specify where and having conditions of the query. All conditions supported by where() are supported by having() as well.

WARNING: In order to use column to column comparisons only raw where conditions should be used as column name or functions cant be passed as a bind variable.

Regular == operator with variables:
```php
$db->where ('id', 1);
$db->where ('login', 'admin');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE id=1 AND login='admin';
```

```php
$db->where ('id', 1);
$db->having ('login', 'admin');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE id=1 HAVING login='admin';
```


Regular == operator with column to column comparison:
```php
// WRONG
$db->where ('lastLogin', 'createdAt');
// CORRECT
$db->where ('lastLogin = createdAt');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE lastLogin = createdAt;
```

```php
$db->where ('id', 50, ">=");
// or $db->where ('id', Array ('>=' => 50));
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE id >= 50;
```

BETWEEN / NOT BETWEEN:
```php
$db->where('id', Array (4, 20), 'BETWEEN');
// or $db->where ('id', Array ('BETWEEN' => Array(4, 20)));

$results = $db->get('users');
// Gives: SELECT * FROM users WHERE id BETWEEN 4 AND 20
```

IN / NOT IN:
```php
$db->where('id', Array(1, 5, 27, -1, 'd'), 'IN');
// or $db->where('id', Array( 'IN' => Array(1, 5, 27, -1, 'd') ) );

$results = $db->get('users');
// Gives: SELECT * FROM users WHERE id IN (1, 5, 27, -1, 'd');
```

OR CASE:
```php
$db->where ('firstName', 'John');
$db->orWhere ('firstName', 'Peter');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE firstName='John' OR firstName='peter'
```

NULL comparison:
```php
$db->where ("lastName", NULL, 'IS NOT');
$results = $db->get("users");
// Gives: SELECT * FROM users where lastName IS NOT NULL
```

LIKE comparison:
```php
$db->where ("fullName", 'John%', 'like');
$results = $db->get("users");
// Gives: SELECT * FROM users where fullName like 'John%'
```

Also you can use raw where conditions:
```php
$db->where ("id != companyId");
$db->where ("DATE(createdAt) = DATE(lastLogin)");
$results = $db->get("users");
```

Or raw condition with variables:
```php
$db->where ("(id = ? or id = ?)", Array(6,2));
$db->where ("login","mike")
$res = $db->get ("users");
// Gives: SELECT * FROM users WHERE (id = 6 or id = 2) and login='mike';
```


Find the total number of rows matched. Simple pagination example:
```php
$offset = 10;
$count = 15;
$users = $db->withTotalCount()->get('users', Array ($offset, $count));
echo "Showing {$count} from {$db->totalCount}";
```

### Query Keywords
To add LOW PRIORITY | DELAYED | HIGH PRIORITY | IGNORE and the rest of the mysql keywords to INSERT (), REPLACE (), GET (), UPDATE (), DELETE() method or FOR UPDATE | LOCK IN SHARE MODE into SELECT ():
```php
$db->setQueryOption ('LOW_PRIORITY')->insert ($table, $param);
// GIVES: INSERT LOW_PRIORITY INTO table ...
```
```php
$db->setQueryOption ('FOR UPDATE')->get ('users');
// GIVES: SELECT * FROM USERS FOR UPDATE;
```

Also you can use an array of keywords:
```php
$db->setQueryOption (Array('LOW_PRIORITY', 'IGNORE'))->insert ($table,$param);
// GIVES: INSERT LOW_PRIORITY IGNORE INTO table ...
```

Same way keywords could be used in SELECT queries as well:
```php
$db->setQueryOption ('SQL_NO_CACHE');
$db->get("users");
// GIVES: SELECT SQL_NO_CACHE * FROM USERS;
```

Optionally you can use method chaining to call where multiple times without referencing your object over and over:

```php
$results = $db
	->where('id', 1)
	->where('login', 'admin')
	->get('users');
```

### Delete Query
```php
$db->where('id', 1);
if($db->delete('users')) echo 'successfully deleted';
```


### Ordering method
```php
$db->orderBy("id","asc");
$db->orderBy("login","Desc");
$db->orderBy("RAND ()");
$results = $db->get('users');
// Gives: SELECT * FROM users ORDER BY id ASC,login DESC, RAND ();
```

Order by values example:
```php
$db->orderBy('userGroup', 'ASC', array('superuser', 'admin', 'users'));
$db->get('users');
// Gives: SELECT * FROM users ORDER BY FIELD (userGroup, 'superuser', 'admin', 'users') ASC;
```

If you are using setPrefix () functionality and need to use table names in orderBy() method make sure that table names are escaped with ``.

```php
$db->setPrefix ("t_");
$db->orderBy ("users.id","asc");
$results = $db->get ('users');
// WRONG: That will give: SELECT * FROM t_users ORDER BY users.id ASC;

$db->setPrefix ("t_");
$db->orderBy ("`users`.id", "asc");
$results = $db->get ('users');
// CORRECT: That will give: SELECT * FROM t_users ORDER BY t_users.id ASC;
```

### Grouping method
```php
$db->groupBy ("name");
$results = $db->get ('users');
// Gives: SELECT * FROM users GROUP BY name;
```

Join table products with table users with LEFT JOIN by tenantID
### JOIN method
```php
$db->join("users u", "p.tenantID=u.tenantID", "LEFT");
$db->where("u.id", 6);
$products = $db->get ("products p", null, "u.name, p.productName");
print_r ($products);
```

### Join Conditions
Add AND condition to join statement
```php
$db->join("users u", "p.tenantID=u.tenantID", "LEFT");
$db->joinWhere("users u", "u.tenantID", 5);
$products = $db->get ("products p", null, "u.name, p.productName");
print_r ($products);
// Gives: SELECT  u.login, p.productName FROM products p LEFT JOIN users u ON (p.tenantID=u.tenantID AND u.tenantID = 5)
```
Add OR condition to join statement
```php
$db->join("users u", "p.tenantID=u.tenantID", "LEFT");
$db->joinOrWhere("users u", "u.tenantID", 5);
$products = $db->get ("products p", null, "u.name, p.productName");
print_r ($products);
// Gives: SELECT  u.login, p.productName FROM products p LEFT JOIN users u ON (p.tenantID=u.tenantID OR u.tenantID = 5)
```

### Properties sharing
It is also possible to copy properties

```php
$db->where ("agentId", 10);
$db->where ("active", true);

$customers = $db->copy ();
$res = $customers->get ("customers", Array (10, 10));
// SELECT * FROM customers where agentId = 10 and active = 1 limit 10, 10

$cnt = $db->getValue ("customers", "count(id)");
echo "total records found: " . $cnt;
// SELECT count(id) FROM users where agentId = 10 and active = 1
```

### Subqueries
Subquery init

Subquery init without an alias to use in inserts/updates/where Eg. (select * from users)
```php
$sq = $db->subQuery();
$sq->get ("users");
```
 
A subquery with an alias specified to use in JOINs . Eg. (select * from users) sq
```php
$sq = $db->subQuery("sq");
$sq->get ("users");
```

Subquery in selects:
```php
$ids = $db->subQuery ();
$ids->where ("qty", 2, ">");
$ids->get ("products", null, "userId");

$db->where ("id", $ids, 'in');
$res = $db->get ("users");
// Gives SELECT * FROM users WHERE id IN (SELECT userId FROM products WHERE qty > 2)
```

Subquery in inserts:
```php
$userIdQ = $db->subQuery ();
$userIdQ->where ("id", 6);
$userIdQ->getOne ("users", "name"),

$data = Array (
    "productName" => "test product",
    "userId" => $userIdQ,
    "lastUpdated" => $db->now()
);
$id = $db->insert ("products", $data);
// Gives INSERT INTO PRODUCTS (productName, userId, lastUpdated) values ("test product", (SELECT name FROM users WHERE id = 6), NOW());
```

Subquery in joins:
```php
$usersQ = $db->subQuery ("u");
$usersQ->where ("active", 1);
$usersQ->get ("users");

$db->join($usersQ, "p.userId=u.id", "LEFT");
$products = $db->get ("products p", null, "u.login, p.productName");
print_r ($products);
// SELECT u.login, p.productName FROM products p LEFT JOIN (SELECT * FROM t_users WHERE active = 1) u on p.userId=u.id;
```

### EXISTS / NOT EXISTS condition
```php
$sub = $db->subQuery();
    $sub->where("company", 'testCompany');
    $sub->get ("users", null, 'userId');
$db->where (null, $sub, 'exists');
$products = $db->get ("products");
// Gives SELECT * FROM products WHERE EXISTS (select userId from users where company='testCompany')
```

### Has method
A convenient function that returns TRUE if exists at least an element that satisfy the where condition specified calling the "where" method before this one.
```php
$db->where("user", $user);
$db->where("password", md5($password));
if($db->has("users")) {
    return "You are logged";
} else {
    return "Wrong user/password";
}
``` 
### Helper methods
Disconnect from the database:
```php
    $db->disconnect();
```

Reconnect in case mysql connection died:
```php
if (!$db->ping())
    $db->connect()
```

Get last executed SQL query:
Please note that function returns SQL query only for debugging purposes as its execution most likely will fail due missing quotes around char variables.
```php
    $db->get('users');
    echo "Last executed query was ". $db->getLastQuery();
```

Check if table exists:
```php
    if ($db->tableExists ('users'))
        echo "hooray";
```

mysqli_real_escape_string() wrapper:
```php
    $escaped = $db->escape ("' and 1=1");
```

### Transaction helpers
Please keep in mind that transactions are working on innoDB tables.
Rollback transaction if insert fails:
```php
$db->startTransaction();
...
if (!$db->insert ('myTable', $insertData)) {
    //Error while saving, cancel new record
    $db->rollback();
} else {
    //OK
    $db->commit();
}
```


### Error helpers
After you executed a query you have options to check if there was an error. You can get the MySQL error string or the error code for the last executed query. 
```php
$db->where('login', 'admin')->update('users', ['firstName' => 'Jack']);

if ($db->getLastErrno() === 0)
    echo 'Update succesfull';
else
    echo 'Update failed. Error: '. $db->getLastError();
```

### Query execution time benchmarking
To track query execution time setTrace() function should be called.
```php
$db->setTrace (true);
// As a second parameter it is possible to define prefix of the path which should be striped from filename
// $db->setTrace (true, $_SERVER['SERVER_ROOT']);
$db->get("users");
$db->get("test");
print_r ($db->trace);
```

```
    [0] => Array
        (
            [0] => SELECT  * FROM t_users ORDER BY `id` ASC
            [1] => 0.0010669231414795
            [2] => MysqliDb->get() >>  file "/avb/work/PHP-MySQLi-Database-Class/tests.php" line #151
        )

    [1] => Array
        (
            [0] => SELECT  * FROM t_test
            [1] => 0.00069189071655273
            [2] => MysqliDb->get() >>  file "/avb/work/PHP-MySQLi-Database-Class/tests.php" line #152
        )

```

### Table Locking
To lock tables, you can use the **lock** method together with **setLockMethod**. 
The following example will lock the table **users** for **write** access.
```php
$db->setLockMethod("WRITE")->lock("users");
```

Calling another **->lock()** will remove the first lock.
You can also use
```php
$db->unlock();
```
to unlock the previous locked tables.
To lock multiple tables, you can use an array.
Example:
```php
$db->setLockMethod("READ")->lock(array("users", "log"));
```
This will lock the tables **users** and **log** for **READ** access only.
Make sure you use **unlock()* afterwards or your tables will remain locked!
