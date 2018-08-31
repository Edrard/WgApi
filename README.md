# ATM
MultiDemensinal Array to Mysql converter
Using Pixie Query Builder as SQL Driver

Simple Example
```
$data[1][2][4][6] = 1;
$data[3][2][4][4] = 2;
$data[1][2][4][3] = 3;
$data[3][2][4][3] = 4;
$data[1][1][4][3] = 5;
$data[3][5][4][3] = 'asf3';
$data[2][5][9][3] = 'fasgasgas';
$data[2][2][4][6] = 1;
$data[2][2][4][4] = 2;
$data[5][2][4][3] = 3;
$data[5][2][4][3] = 4;
$data[5][1][4][3] = 5;
$data[6][5][4][3] = 'asf3';
$data[6][5][9][3] = 'fasgasgas';
$data[6][2][4][6] = 1;
$data[7][2][4][4] = 2;
$data[7][2][4][3] = 3;
$data[7][2][4][3] = 4;
$data[8][1][4][3] = 5;
$data[8][5][4][3] = 'asf3';
$data[8][5][9][3] = 'fasgasgas';


require '../vendor/autoload.php';

use edrard\Atm\Atm;
use edrard\Atm\Database\DbManipulate;
use edrard\Atm\Database\DataManipulate;
use edrard\Atm\Database\DbCreate;


$config = array(
    'driver'    => 'mysql', // Db driver
    'host'      => 'localhost',
    'database'  => 'test',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8', // Optional
    'collation' => 'utf8_unicode_ci', // Optional
    'prefix'    => '', // Table prefix, optional
);
$db = new DbCreate($config);
$atm = new Atm(new DbManipulate($db->getDbConnect(),'table'),new DataManipulate($db->getDbConnect(),'table'));
$atm->fullLog();
$atm->dropTable();
foreach($data as $key => $val){
    $additional_data = array(
        'id' => array('value' => $key, 'index' => TRUE), 
        'time' => array('value' => 1343535*$key, 'index' => TRUE),
        'type' => array('value' => 'ru', 'index' => TRUE)
    );
    $atm->constructData($val,$additional_data)
    ->checkMysql()
    ->insertDataBatch(3);
}
$atm->insertFinish();
$atm->getData();
```