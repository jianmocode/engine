Yao\Xlsx
===============

Xlsx 数据表类

see https://github.com/PHPOffice/PhpSpreadsheet


* Class name: Xlsx
* Namespace: Yao





Properties
----------


### $spreadSheet

    public \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet = null

Xlsx 工作表



* Visibility: **public**
* This property is **static**.


### $activeSheet

    public \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $activeSheet = null

当前工作表



* Visibility: **public**
* This property is **static**.


### $writer

    public \PhpOffice\PhpSpreadsheet\Writer\Xlsx $writer = null

Xlsx 对象, 用于写入



* Visibility: **public**
* This property is **static**.


Methods
-------


### __construct

    mixed Yao\Xlsx::__construct()

Xlsx 数据表对象



* Visibility: **public**




### readLine

    void Yao\Xlsx::readLine(callable $callback, string $sheet, string $xlsx_file)

读取数据表每一行



* Visibility: **public**
* This method is **static**.


#### Arguments
* $callback **callable**
* $sheet **string** - &lt;p&gt;数据表名称&lt;/p&gt;
* $xlsx_file **string** - &lt;p&gt;Excel文件&lt;/p&gt;



### writer

    \PhpOffice\PhpSpreadsheet\Writer\Xlsx Yao\Xlsx::writer(string $xlsx_file)

读取 Xlsx 对象, 用于写入



* Visibility: **public**
* This method is **static**.


#### Arguments
* $xlsx_file **string** - &lt;p&gt;Excel文件&lt;/p&gt;



### save

    void Yao\Xlsx::save(string $output_file)

保存数据表到文件



* Visibility: **public**
* This method is **static**.


#### Arguments
* $output_file **string** - &lt;p&gt;文件保存路径&lt;/p&gt;



### activeSheet

    \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet Yao\Xlsx::activeSheet(string $sheet, string $xlsx_file)

读取当前工作表



* Visibility: **public**
* This method is **static**.


#### Arguments
* $sheet **string** - &lt;p&gt;工作表名称&lt;/p&gt;
* $xlsx_file **string** - &lt;p&gt;Excel文件&lt;/p&gt;



### spreadSheet

    \PhpOffice\PhpSpreadsheet\Spreadsheet Yao\Xlsx::spreadSheet(string $xlsx_file)

创建 Spreadsheet 对象



* Visibility: **public**
* This method is **static**.


#### Arguments
* $xlsx_file **string** - &lt;p&gt;Excel文件&lt;/p&gt;



### __callStatic

    mixed Yao\Xlsx::__callStatic(string $method, array $parameters)

Pass methods onto activeSheet.



* Visibility: **public**
* This method is **static**.


#### Arguments
* $method **string**
* $parameters **array**


