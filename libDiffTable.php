<?php
define("E_NotSameTable", 1);
#define(E_NotSameTable, 1);

$oTable = [
	"name"=>"",
	"columns"=>[
		"name"=>"",
		"type"=>"",
	],
	"options"=>[],
];


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
	$ret = preg_match("/CREATE TABLE ([`_a-zA-Z0-9]*) \((.*)\)(.*);/", $testSql, $matches);
	if(!$ret)
	{
		echo "=========\n";
		print_r($ret);
		echo $strSql;
		print_r($matches);
		echo "=========\n";
		return ;
	}
	foreach(explode(' ', $matches[3]) as $v)
	{
		$ret = preg_match("/[ \t]*([a-zA-Z0-9]*)(=([a-zA-Z0-9]*))?/",$v,$matchoption);
		if($ret){
			if(isset($matchoption[3]))
				$oTable['options'][$matchoption[1]] = $matchoption[3];
			else
				$oTable['options'][$matchoption[1]]='';
		}
	}

	$oTable['name'] = trim($matches[1],' \t`');

	$columns = explode(",", $matches[2]);
	foreach($columns as $v)
	{
		$ret = preg_match("/.*KEY.*/",$v,$matches);
		if($ret)
		{
			$oTable['indexes'][] = ['detail'=>$matches[0]];
		}
		else
		{
			$oTable['columns'][] = ['detail'=>$v];	
		}
	}

	return $oTable;
}

function diffOneTable($aT, $bT)
{
	if($aT['name']!=$bT['name'])
		return E_NotSameTable;

	$sql = "";

	return $sql;
}

$leftFile  = $argv[1];
$rightFile  = $argv[2];

$left = fopen($leftFile,"r");
$right = fopen($rightFile,"r");

$i = 0 ;

$ltable = pickupOneTable($left);
$aT = parseCreateSql($ltable);

$rtable = pickupOneTable($right);
$bT = parseCreateSql($ltable);

while( !feof($left) ){
	$ltable = pickupOneTable($left);
	$aT = parseCreateSql($ltable);
	print_r($aT);	
}

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