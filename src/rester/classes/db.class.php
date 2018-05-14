<?php
/**
 *  @class    DB
 *  @author   박경종(kevinpark@webace.co.kr)
 *  @brief    모듈
 *  @date     2011.10.26 - 생성
 *  @update   2016.10.21 static class
 */
class DB
{
  public static $inst = array();
  public static $prefix='';

  // 패스워드 인코딩후 반환
  public static function password($v)
  {
    return array_pop(self::sql_fetch("SELECT PASSWORD('{$v}')"));
  }

	// 쿼리 결과를 한줄 받환 받음
	public static function sql_fetch($sql)
  {
		$result = self::sql_query($sql);
		$num_results = $result->num_rows;
		$row = '';
		if($num_results!=0) {
			$row = $result->fetch_assoc();
			$result->free();
		}
		return $row;
	}

	// 단순 쿼리 결과를 연관배열로 반환해줌
	public static function sql_query_list($sql)
  {
		$result = self::sql_query($sql);
		$num_results = $result->num_rows;
		$row='';
		for($i=0; $i<$num_results; $i++) 
		{
			$row = $result->fetch_assoc();
			$list[$i] = $row;
		}
		return $list;
	}

	// 쿼리 결과를 단순배열로 반환해줌
	public static function sql_query_list_row($sql)
  {
		$result = self::sql_query($sql);
		$num_results = $result->num_rows;
		$list=array();
		for($i=0; $i<$num_results; $i++) 
		{
			$list[$i] = $result->fetch_row();
		}
		mysqli_free_result($result);
		return $list;
	}

	// Simple Select 확장
	public static function sql_selectEx($table, $field, $where='')
  {
		$sql = self::createSelect($table, $field, $where);
		return self::sql_query_list($sql);
	}

	// Simple Select Row 확장
	public static function sql_selectRowEx($table, $field, $where='')
  {
		$sql = self::createSelect($table, $field, $where);
		return self::sql_query_list_row($sql);
	}

	// 테이블, 필드명을 선택하면 자동으로 쿼리문장을 생성함
	protected static function createSelect($table, $field, $where='')
  {
		// 데이터 검증
		if(!isset($table) && $table!='') return false;
		if($field!='*' && !is_array($field)) return false;

		$length = sizeof($field);

		// select ,,, from table where ...
		$sql='SELECT ';
		if(!is_array($field) && $field=='*') $sql.=' * ';
		else
		{
			$count=0;
			foreach($field as $k => $v)
			{
				$sql.=$v;
				if(++$count!=$length) $sql.=',';
			}
		}
		$sql.=' FROM `'.$table.'` ';

		if($where!='') $sql.= ' WHERE '.$where;
		return $sql;
	}


    // insert
    // 일반적인 입력
    public static function Insert($sql)
    {
        if (self::sql_query($sql)) {
            return mysqli_insert_id(self::$inst);
        } else {
            return false;
        }
    }
	// 확장 입력
	// 테이블 명과 필드명, 데이터들을 입력하면
	// 자동으로 쿼리문장을 생성해서 입력함
	public static function InsertEx($table, $data, $functions=array())
  {
		// 데이터 검증
		if(!isset($table) && $table!='') return false;
		if(!is_array($data)) return false;
		$length = sizeof($data);

		$sql='INSERT INTO '.$table.' (';

		$count=0;
		foreach($data as $k => $v)
		{
			$sql.=$k;
			if(++$count!=$length) $sql.=',';
		}
		$sql.=') VALUES (';
		$count=0;
		foreach($data as $k => $v) {
			if(!in_array($k, $functions) && ($k=='wr_content' || strpos($v, 'PASSWORD')===false) )
				$sql.="'".$v."'";
			else
				$sql.=$v;
			if(++$count!=$length) $sql.=',';
		}
		$sql.=')';
		return self::insert($sql);
	}

	// update
	public static function update($table, $data, $where, $functions=array())
  {
		// 데이터 검증
		if(!isset($table) && $table!='') return false;
		if(!is_array($data)) return false;
		$length = sizeof($data);

		$sql='UPDATE '.$table.' SET ';

		$count=0;
		foreach($data as $k => $v)
		{
			$sql.= " $k=";
			if(!in_array($k, $functions) && ($k=='wr_content' || strpos($v, 'PASSWORD')===false) )
				$sql.="'".$v."'";
			else
				$sql.=$v;

			if(++$count!=$length) $sql.=',';
		}
		$sql.=' '.$where.' LIMIT 1';
		return self::insert($sql);
	}

	// 단순쿼리	
	// debug 모드일때 접속수를 카운트함
	public static function sql_query($sql)
  {
		return self::check_error(mysqli_query(self::$inst,$sql), $sql);
	}

  // 쿼리결과 체크
	protected static function check_error($result, $sql)
  {
		if(defined('__DEBUG__'))
    {
			if(!$result)
      {
        echo $sql.'<br/>';
				var_dump("errno:<b>".mysqli_errno(D::$inst)."</b>\n".mysqli_error(D::$inst));
				exit;
			}
		}
		return $result;
	}
}

include_once(dirname(__FILE__) . '/../config/dbconfig.php');

// prefix 설정
D::$prefix = $prefix;

D::$inst = new mysqli($mysql_host, $mysql_user, $mysql_password, $mysql_db);
if(mysqli_connect_errno()) 
{
  echo '데이터베이스에 접속 할수 없습니다.';
  exit;
}

// 글자 깨짐 방지
mysqli_query(D::$inst,"set sql_mode = '';");
mysqli_query(D::$inst,"set character_set_client = utf8;");
mysqli_query(D::$inst,"set character_set_connection = utf8;");
mysqli_query(D::$inst,"set character_set_results = utf8;");

