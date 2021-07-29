<?php
define("E_AllSame", 0);
define("E_NotSameTable", 1);
#define(E_NotSameTable, 1);

$debug = false;

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

	public function search()
	{
		echo 'in search';
	}

	public function hasColumn($name)
	{
		
	}

	public function isEnd()
	{
		return $this->_max<=$this->_offset;
	}

	public function popColumn()
	{
		$this->_columnOption = [];

		$column = ['name'=>'','dataType'=>'','options'=>'','columnType'=>'column'];
		$this->skipBlank();
		$column['name'] = $this->popName();

		if(in_array($column['name'],$this->_KEY))
		{
			$this->skipBlank();
			$column['options'] = $this->popIndex($column['name']);
			$column['columnType']='index';
			$column['name']=$this->_tmpname;
			return $column;
		}
		else
		{
			$this->skipBlank();
			$column['dataType'] = $this->popDataType();
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
			$ret = preg_match("/\'(\\\'|[^'])*\'/", $this->_columns, $matches, 0, $this->_offset);
		else if($s=="\"")
			$ret = preg_match("/\"(\\\"|[^\"])*\"/", $this->_columns, $matches, 0, $this->_offset);
		else
			return '';

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

	// 解析两端带括号的简单选项
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

	// 解析括号嵌套的形式
	public function parseKeyPart()
	{
		$ret = preg_match("/[a-zA-Z0-9 `]*(\([0-9,]*\))?/", $this->_columns, $matches, 0, $this->_offset);
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
		if(strcasecmp($firstWord,'PRIMARY')==0)
			$this->_tmpname = 'PRIMARY';

		$indexLine = $firstWord;
		$this->skipBlank();
		$s = substr($this->_columns, $this->_offset,1);
		$e = true;
		$inKeypart = 0;
		$keypart = [];

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
					$this->_tmpname = $name;
					$indexLine .= '`'.$name.'`';
					break;
				case '(':
					#$options = $this->popIndexOption();
					$this->_offset+=1;
					$inKeypart = 1;
					$indexLine .= '(';
					break;
				case ')':
					#$options = $this->popIndexOption();
					$this->_offset+=1;
					$inKeypart =0;
					$indexLine .= ')';
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

			if($inKeypart){
				while($inKeypart){
					$kp = $this->parseKeyPart();
					if($kp){
						$keypart[] = $kp;
						$this->skipBlank();
						$this->skipCommas();
					}
					else
					{
						$indexLine .= implode(',', $keypart);
						$keypart=null;
						$keypart=[];
						$inKeypart=0;
						break;
					}
				}
			}

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
			if($matchoption[1]=='COMMENT')
			{
				$len = strlen($matchoption[0]);
				$sub = substr($v,$len);
				$s   = substr($v,$len,1);

				if($s=="'")
					$ret = preg_match("/\'(\\\'|[^'])*\'/", $v, $matches, 0, $len);
				else if($s=="\"")
					$ret = preg_match("/\"(\\\"|[^\"])*\"/", $v, $matches, 0, $len);
				else
					$ret = false ;

				$oOption[$matchoption[1]] = $matches[0];
			}
			else if(isset($matchoption[3]))
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

	$i=0;
	while(!$cp->isEnd())
	{
		$oC = $cp->popColumn();

		if($oC['columnType']=='column')
			$columns[] = $oC;
		else
			$indexes[] = $oC;

		$cp->skipCommas();		
		$i++;
	}

	return [$columns,$indexes];
}

function diffOneTable($aT, $bT)
{
	if($aT['name']!=$bT['name'])
		return E_NotSameTable;

	// 1. check columns
	$alter = [];
	$precol = '';
	foreach($bT['columns'] as $k=>$v)
	{
		$bT['columns'][$k]['columnType'] = 'checking';
		foreach($aT['columns'] as $kk=>$vv)
		{
			if($vv['name']==$v['name'])
			{
				$bT['columns'][$k]['columnType'] = 'found';
				$aT['columns'][$kk]['columnType'] = 'found';
				if($vv['dataType']==$v['dataType'] && $vv['options']==$v['options'])
				{
					// do nothing
				}
				else
				{
					//ALTER TABLE `person` CHANGE `birthday` `birthday1` DATE NOT NULL;
					$alter[] = "MODIFY `". $v['name']."` ".$v['dataType'].' '.$v['options'];
				}
			}
		}
		if($bT['columns'][$k]['columnType'] == 'checking')
		{
			$alter[] = 'ADD `'.$v['name'].'` '.$v['dataType']. $v['options']. ' after `'. $precol."`";
		}

		$precol = $v['name'];
	}

	foreach($aT['columns'] as $k=>$v)
	{
		if($v['columnType']!='found')
			$alter[] = 'DROP `'.$v['name'].'`';
	}
	
	if(count($alter)>0)
	{
		echo 'Alter Table `',$bT['name'],"`\n";
		foreach($alter as $k=>$v){
			echo "\t",$v;
			if(isset($alter[$k+1]))
				echo ",\n";
			else
				echo ";\n";
		}
		echo "\n";
	}

	// 2. check indexes

	// 3. check options

	return E_AllSame;
}

function diffTables($left, $right)
{
	$ltable = pickupOneTable($left);
	if(!$ltable){
		print_r($ltable);
		return ;
	}

	$aT = parseCreateSql($ltable);

	$rtable = pickupOneTable($right);
	if(!$rtable){
		print_r($rtable);
		return ;
	}
	$bT = parseCreateSql($rtable);

	while(!feof($left))
	{
		#echo $aT['name'],":",$bT['name'],"\n";
		if($aT['name']>$bT['name'])
		{
			$rtable = null;
			$rtable = pickupOneTable($right);
			if(!$rtable){
				print_r($rtable);
				return ;
			}
			$bT=null;
			$bT = parseCreateSql($rtable);
		}
		else if($aT['name']<$bT['name'])
		{
			echo 'DROP table `'.$aT['name'],"`;\n";
			$ltable=null;
			$ltable = pickupOneTable($left);
			if(!$ltable){
				print_r($ltable);
				return ;
			}
			$aT = null;
			$aT = parseCreateSql($ltable);
		} 
		else
		{
			$ret = diffOneTable($aT,$bT);
		
			$ltable=null;
			$ltable = pickupOneTable($left);
			if(!$ltable){
				print_r($ltable);
				return ;
			}
			$aT=null;
			$aT = parseCreateSql($ltable);

			$rtable=null;
			$rtable = pickupOneTable($right);
			if(!$rtable){
				print_r($rtable);
				return ;
			}
			$bT=null;
			$bT = parseCreateSql($rtable);
		}
	}

}

$leftFile  = $argv[1];
$rightFile  = $argv[2];

$left = fopen($leftFile,"r");
$right = fopen($rightFile,"r");

$i = 0 ;

if(true)
{
	diffTables($left,$right);	
}
else
{
	#$str = "UNIQUE KEY `username` (`username`(190),`enterpriseId`) USING BTREE";
	#echo $str,"\n";
	#$cp = new ColumnParser($str);
	#$c = $cp->popColumn();
	#print_r($c);
	#die();

	$ltable = pickupOneTable($left);
	if(!$ltable){
		print_r($ltable);
		return ;
	}
	$aT = parseCreateSql($ltable);

	$rtable = pickupOneTable($right);
	if(!$rtable){
		print_r($rtable);
		return ;
	}
	$bT = parseCreateSql($rtable);

	diffOneTable($aT,$bT);	
}

fclose($left);
fclose($right);


