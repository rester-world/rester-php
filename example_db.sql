-- --------------------------------------------------------
-- 호스트:                          127.0.0.1
-- 서버 버전:                        10.2.14-MariaDB-10.2.14+maria~jessie - mariadb.org binary distribution
-- 서버 OS:                        debian-linux-gnu
-- HeidiSQL 버전:                  9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- 테이블 example.example 구조 내보내기
CREATE TABLE IF NOT EXISTS `example` (
  `no` int(11) NOT NULL AUTO_INCREMENT COMMENT '테이블키',
  `key` varchar(50) NOT NULL DEFAULT '0',
  `value` varchar(50) NOT NULL DEFAULT '0',
  PRIMARY KEY (`no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='rester example table';

-- 테이블 데이터 example.example:~0 rows (대략적) 내보내기
/*!40000 ALTER TABLE `example` DISABLE KEYS */;
/*!40000 ALTER TABLE `example` ENABLE KEYS */;

-- 테이블 example.example_file 구조 내보내기
CREATE TABLE IF NOT EXISTS `example_file` (
  `file_no` int(11) NOT NULL AUTO_INCREMENT COMMENT '테이블키',
  `file_fkey` int(11) DEFAULT NULL COMMENT '연동테이블키',
  `file_owner` int(11) DEFAULT NULL COMMENT '업로드한사용자',
  `file_module` varchar(50) DEFAULT NULL COMMENT '모듈명',
  `file_name` varchar(128) CHARACTER SET utf8 DEFAULT NULL COMMENT '업로드시파일명',
  `file_local_name` varchar(128) CHARACTER SET utf8 DEFAULT NULL COMMENT '저장된파일명',
  `file_download` int(11) unsigned DEFAULT 0 COMMENT '다운로드횠수',
  `file_size` int(11) unsigned DEFAULT NULL COMMENT '파일크기',
  `file_type` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '파일mime-type',
  `file_desc` varchar(256) CHARACTER SET utf8 DEFAULT '' COMMENT '파일설명',
  `file_datetime` datetime DEFAULT current_timestamp() COMMENT '파일업로드시간',
  `file_tmp` tinyint(3) unsigned DEFAULT 1 COMMENT '임시파일여부',
  PRIMARY KEY (`file_no`),
  KEY `연동테이블레코드` (`file_fkey`),
  KEY `연동테이블레코드+순서` (`file_fkey`,`file_datetime`),
  KEY `업로드유저` (`file_owner`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='파일 예제 테이블';

-- 테이블 데이터 example.example_file:~0 rows (대략적) 내보내기
/*!40000 ALTER TABLE `example_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `example_file` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
