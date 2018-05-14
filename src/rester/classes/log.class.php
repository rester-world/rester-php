<?php
class log
{
  private $tbn = 'log';
  
  // 필드명
  const log_no = 'log_no';
  const mb_no = 'mb_no';
  const log_kind = 'log_kind';
  const log_msg = 'log_msg';
  const log_datetime = 'log_datetime';
  
  private $kind = array(
    0x0001 => '시스템',
    0x0002 => '크롤링',
    0x0004 => '신규물건업데이트',
    0x0008 => '특수물건업데이트',
    0x0010 => '다수조회물건업데이트',
    0x0020 => '다수관심물건업데이트',
    0x0040 => '물건패치',
    0x0080 => '서버 IP 블록',
    0x0100 => '매각결과업데이트',
    0x0200 => '물건서버이상',
  );

	public function __construct()
  {
    $this->tbn = D::$prefix.$this->tbn;
	}
  
  /**
   *  @brief 로그남기기
   *  
   *  @param [in] $msg   로그메시지
   *  @param [in] $kind  로그종류
   *  @param [in] $mb_no 회원번호
   *  
   *  @details 데이터베이스에 로그를 남긴다.
   *  메시지가 배열로 왔을 경우는 개행문자로 합쳐서 넣어줌
   */
  public function add($msg, $kind, $mb_no=1)
  {
    if(is_array($msg)) $msg = implode("\n",$msg);
    $clear = array();
    $clear[self::mb_no] = $mb_no;
    $clear[self::log_kind] = $kind;
    $clear[self::log_msg] = $msg;
    $clear[self::log_datetime] = 'NOW()';
    D::InsertEx($this->tbn, $clear, array('log_datetime'));
  }
}

/**
 *  시스템 로그를 남긴다.
 */
function 시스템로그($msg, $mb_no=1)
{
  $log = new clog();
  $log->add($msg, 0x0001);
}

/**
 *  크롤링로그를 남긴다.
 *  남기는 회원은 100% 관리자
 */
function 크롤링로그($msg, $kind=0)
{
  if(sizeof($msg)>0)
  {
    $log = new clog();
    $log->add($msg, 0x0002|$kind,1);
  }
}
