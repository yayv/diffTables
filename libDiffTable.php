<?php
define("E_NotSameTable", 1);
#define(E_NotSameTable, 1);

$oTable = [
	"name"=>"",
	"columns"=>[],
	"indexes"=>[],
	"options"=>[],
];

class ColumnParser
{
	private $_columns;
	private $_offset ;
	private $_max ;
	private $_debug;

	private $_columnOption;

	public function __construct($line)
	{
		$this->_KEY = ['KEY',"INDEX","FULLTEXT","CONSTRAINT",'PRIMARY','UNIQUE',"FOREIGN"];

		$this->_offset = 0;
		$this->_columns = $line ;
		$this->_max = strlen($line);
		$this->_debug = false;
	}

	public function isEnd()
	{
		return $this->_max<=$this->_offset;
	}

	public function popColumn()
	{
		$debug = false;
		$this->_columnOption = [];

		$column = ['name'=>'','dataType'=>'','options'=>'','columnType'=>'column'];
		$this->skipBlank();
		$column['name'] = $this->popName();

if($column['name']=='areaId')
	$this->_debug = true;

		if(in_array($column['name'],$this->_KEY))
		{
			if($debug) die('a1');
			$this->skipBlank();
			$column['options'] = $this->popIndex($column['name']);
			$column['columnType']='index';
			$column['name']='';
			return $column;
		}
		else
		{
			$this->skipBlank();

			$column['dataType'] = $this->popDataType();
#if($this->_debug) echo "before:",$column['dataType'],"\n";			
			$this->skipBlank();
			$word = $this->popColumnOptions();

			while($word)
			{
				$column['options'] .= ' '.$word;
				$this->skipBlank();	
				if(strcasecmp($word,'COMMENT')==0)
				{
					$comment = $this->popString();
					$column['options'] .= ' '.$comment;
					$this->skipBlank();	
				}
				if(strcasecmp($word,'DEFAULT')==0)
				{
					$comment = $this->popString();
					$column['options'] .= ' '.$comment;
					$this->skipBlank();	
				}
			
				$word = $this->popColumnOptions();
			}
#if($this->_debug) echo "after:",$word,"\n";

#if($this->_debug) die('for test');
			return $column;
		}
	}

	public function skipBlank()
	{
		$ret = preg_match("/[ \t\n]*/",$this->_columns,$matches,0,$this->_offset);
		if($ret)
		{
			$this->_offset += strlen($matches[0]);
		}
		
		return $ret;
	}

	public function skipCommas()
	{
		$ret = preg_match("/[ \t\n]*,[ \t\n]*/",$this->_columns,$matches,0,$this->_offset);
		if($ret)
		{
			$this->_offset += strlen($matches[0]);
		}
		
		return $ret;
	}

	public function popName()
	{
		$ret = preg_match("/[a-zA-Z0-9_`]*/", $this->_columns, $matches, 0, $this->_offset);
		if($ret)
		{
			$this->_offset+=strlen($matches[0]);
			return trim($matches[0],"`");
		}	
		else
			return false;
	}

	public function popDataType()
	{
		$type = '';
		$len  = '';

		$ret = preg_match("/([a-zA-Z]*)/", $this->_columns, $matches, 0, $this->_offset);
		if($ret)
		{
			$type = $matches[0];
			$this->_offset+=strlen($matches[0]);
		}

		$ret = preg_match("/[ \t\n]*(\([0-9,]*\))?/",$this->_columns, $matches, 0, $this->_offset);
		if($ret)
		{
			$len = $matches[0];
			$this->_offset+=strlen($matches[0]);
		}

		return $type.$len;
	}

	public function popOption()
	{

	}

	public function popString()
	{
		$matches = [];
		
		$s = substr($this->_columns,$this->_offset,1);

		if($s=="'")
			$ret = preg_match("/'[^\']*'/", $this->_columns, $matches, 0, $this->_offset);
		else if($s=="\"")
			$ret = preg_match("/\".*\"/", $this->_columns, $matches, 0, $this->_offset);
		else
			return '';

		#echo substr($this->_columns,$this->_offset,10);
		#print_r($matches);
		$this->_offset += strlen($matches[0]);

		return $matches[0];
	}

	public function popColumnOptions()
	{
		$ret = preg_match("/([a-zA-Z0-9_])*/", $this->_columns,$matches, 0, $this->_offset);
		if($ret)
		{
			$this->_offset += strlen($matches[0]);
			return $matches[0];
		}

		return false;
	}

	public function popIndexOption()
	{	
		$ret = preg_match("/\([a-zA-Z0-9 `,]*\)/", $this->_columns, $matches, 0, $this->_offset);
		if($ret)
		{
			$this->_offset += strlen($matches[0]);
			return $matches[0];
		}
		else
			return '';
	}

	public function popIndex($firstWord)
	{
		$indexLine = $firstWord;
		$this->skipBlank();
		$s = substr($this->_columns, $this->_offset,1);
		$e = true;
		while($e)
		{
			switch($s)
			{
				case ' ':
				case "\t":
				case "\n":
					$this->skipBlank();
					break;
				case '`':
					$name = $this->popName();
					$indexLine .= '`'.$name.'`';
					break;
				case '(':
					$options = $this->popIndexOption();
					$indexLine .= $options;
					break;
				case ',':
					$e=false;
					break;
				default:
					$word = $this->popColumnOptions();
					$indexLine .= ' '.$word;
					break;
			}

			$this->skipBlank();
			if($this->_offset>=$this->_max)
				break;
			$s = substr($this->_columns, $this->_offset,1);
		}	

		return $indexLine;
	}

	public function popDefinition()
	{

	}
}

class TableParser
{
	private $_name;
	private $_string;
	public function __contruct($name, $allString)
	{
		$this->_name = $name;
	}

}

// 检查是否有不支持的SQL语句
function checkSQLFile($fSQL)
{
	$i=0;
	while( !feof($fSQL) ){
		$strTable = pickupOneTable($fSQL);
		echo $strTable,"\n\n\n";
		if($strTable){
			$oT = parseCreateSql($strTable);
			#echo $oT['name'],"\n";
		}
		else
		{
			#echo $strTable,"\n";
			break;
		}
		$i++;
		if($i>10)
			break;
	}
}


function pickupOneTable($file)
{
	$inCreate = false;

	$str = "";
	$line = '';

	$line = fgets($file);

	while( !$inCreate && !feof($file) )
	{
		$ret = preg_match("/CREATE[ \t]*TABLE.*/", $line, $matches1);
		if($ret){
			$inCreate = true;
			break;
		}
		$line = fgets($file);
		if(feof($file))
			return false;
	}

	while($inCreate)
	{
		$str .= $line;

		// ;
		$ret = preg_match("/.*;.*/", $line, $matches2);
		if($ret)
		{
			$inCreate = false;
			return $str;
		}

		$line = fgets($file);
	}

	return $str;
}

function parseCreateSql($str)
{
	$oTable = [
		"name"=>"",
		"columns"=>[
		//	["name"=>"","type"=>"",]
		],
		"indexes"=>[
		//	["name"=>'',"type"=>'',"detail"=>'',]
		],
		"options"=>[],
	];

	$strSql = str_replace("\n","",$str);
	$testSql = $strSql;
	$ret = preg_match("/[ \t\n]*CREATE TABLE ([`_a-zA-Z0-9]*) \((.*)\)(.*);/", $testSql, $matches);
	if(!$ret)
	{
		echo "=== Parse Create SQL failure ======\n";
		print_r($ret);
		echo $testSql;
		print_r($matches);
		echo "=========\n";

		return false;
	}

	$oTable['name'] = trim($matches[1]," \t`");

	$oTable['options'] = parseOptions($matches[3]);

	// parse Keys and columns
	list($oTable['columns'],$oTable['indexes']) = parseColumnsAndIndexes($matches[2]);

	return $oTable;
}

function parseOptions($line)
{
	$oOption = [];

	// parse options
	foreach(explode(' ', trim($line)) as $v)
	{
		$ret = preg_match("/[ \t]*([a-zA-Z0-9]*)(=([a-zA-Z0-9]*))?/",$v,$matchoption);
		if($ret){
			if(isset($matchoption[3]))
				$oOption[$matchoption[1]] = $matchoption[3];
			else
				$oOption[$matchoption[1]] = '';
		}
	}

	return $oOption;
}

function parseColumnsAndIndexes($line)
{
	$columns = [];
	$indexes = [];

	$cp = new ColumnParser($line);

	while(!$cp->isEnd())
	{
		$oC = $cp->popColumn();
		print_r($oC);
		if($oC['columnType']=='column')
			$columns[] = $oC;
		else
			$indexes[] = $oC;

		$cp->skipCommas();		
	}
	
	//print_r([$columns,$indexes]);
	return [$columns,$indexes];
}

function diffOneTable($aT, $bT)
{
	if($aT['name']!=$bT['name'])
		return E_NotSameTable;

	// 1. check columns

	// 2. check indexes

	// 3. check options


	return $sql;
}

$leftFile  = $argv[1];
$rightFile  = $argv[2];

$left = fopen($leftFile,"r");
$right = fopen($rightFile,"r");

$i = 0 ;

$ltable = pickupOneTable($left);
if(!$ltable){
	print_r($ltable);
	return ;
}
$aT = parseCreateSql($ltable);

/*
$rtable = pickupOneTable($right);
if(!$rtable){
	print_r($rtable);
	return ;
}
$bT = parseCreateSql($rtable);
*/
#checkSQLFile($left);
#checkSQLFile($right);

/*
while( !feof($left) || !feof($right) )
{
	$ret = diffOneTable($aT, $bT);

	if($ret==E_NotSameTable)
	{		
		if($aT['name']<$bT['name'])
		{
			$ltable = pickupOneTable($left);
			$aT = parseCreateSql($ltable);
		}
		else
		{
			$rtable = pickupOneTable($right);
			$bT = parseCreateSql($rtable);
		}
	}
	else
	{
		$ltable = pickupOneTable($left);
		$aT = parseCreateSql($ltable);

		$rtable = pickupOneTable($right);
		$bT = parseCreateSql($ltable);
	}
	#$ret = parseCreateSql($ltable);
	
	//break;
}
*/
