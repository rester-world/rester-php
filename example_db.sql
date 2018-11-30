-- --------------------------------------------------------
-- 호스트:                          192.168.99.100
-- 서버 버전:                        10.3.11-MariaDB-1:10.3.11+maria~bionic - mariadb.org binary distribution
-- 서버 OS:                        debian-linux-gnu
-- HeidiSQL 버전:                  9.5.0.5339
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='rester example table';

-- 테이블 데이터 example.example:~3 rows (대략적) 내보내기
/*!40000 ALTER TABLE `example` DISABLE KEYS */;
INSERT INTO `example` (`no`, `key`, `value`) VALUES
	(1, '96', '37'),
	(2, '37', '254'),
	(3, '73', '77');
/*!40000 ALTER TABLE `example` ENABLE KEYS */;

-- 테이블 example.example_file 구조 내보내기
CREATE TABLE IF NOT EXISTS `example_file` (
  `file_no` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '테이블키',
  `file_fkey` int(11) unsigned DEFAULT 0 COMMENT '연동테이블키',
  `file_owner` int(11) unsigned DEFAULT 0 COMMENT '업로드한사용자',
  `file_module` varchar(50) DEFAULT '' COMMENT '모듈명',
  `file_name` varchar(128) DEFAULT NULL COMMENT '업로드시파일명',
  `file_local_name` varchar(128) DEFAULT NULL COMMENT '저장된파일명',
  `file_download` int(11) unsigned DEFAULT 0 COMMENT '다운로드횠수',
  `file_size` int(11) unsigned DEFAULT NULL COMMENT '파일크기',
  `file_type` varchar(128) DEFAULT NULL COMMENT '파일mime-type',
  `file_desc` varchar(256) DEFAULT '' COMMENT '파일설명',
  `file_datetime` datetime DEFAULT current_timestamp() COMMENT '파일업로드시간',
  `file_tmp` tinyint(3) unsigned DEFAULT 1 COMMENT '임시파일여부',
  PRIMARY KEY (`file_no`),
  KEY `연동테이블레코드` (`file_fkey`),
  KEY `연동테이블레코드+순서` (`file_fkey`,`file_datetime`),
  KEY `업로드유저` (`file_owner`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8 COMMENT='파일 예제 테이블';

-- 테이블 데이터 example.example_file:~79 rows (대략적) 내보내기
/*!40000 ALTER TABLE `example_file` DISABLE KEYS */;
INSERT INTO `example_file` (`file_no`, `file_fkey`, `file_owner`, `file_module`, `file_name`, `file_local_name`, `file_download`, `file_size`, `file_type`, `file_desc`, `file_datetime`, `file_tmp`) VALUES
	(1, 0, 0, 'hello_rester', 'ì œëª© ì—†ìŒ.png', '4c044ade4f3194c9b758_ì œëª©_ì—†ìŒ.png', 0, 185780, 'image/png', '', '2018-11-27 06:11:25', 1),
	(2, 0, 0, 'hello_rester', 'ì œëª© ì—†ìŒ.png', 'c78f4f6722fb3f23c0d3_ì œëª©_ì—†ìŒ.png', 0, 185780, 'image/png', '', '2018-11-27 06:12:45', 1),
	(3, 0, 0, 'hello_rester', '제목 없음.png', '457181ff6920cc77cdd4_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 06:16:00', 1),
	(4, 0, 0, 'hello_rester', '제목 없음.png', '7e4c11dc371613802eb5_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 06:26:25', 1),
	(5, 0, 0, 'hello_rester', '제목 없음.png', 'c0d0c9bf66b9d6213e9c_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 06:26:26', 1),
	(6, 0, 0, 'hello_rester', '제목 없음.png', 'bda6653b279ef6189440_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 07:11:55', 1),
	(7, 0, 0, 'hello_rester', '00001_150803_094521_4a1e.jpg', 'aaaa11cbcac391742c43_00001_150803_094521_4a1e.jpg', 0, 133105, 'image/jpeg', '', '2018-11-27 07:11:55', 1),
	(8, 0, 0, 'hello_rester', 'cb042000917.jpg', 'd10a915acd95528de40a_cb042000917.jpg', 0, 3387322, 'image/jpeg', '', '2018-11-27 07:11:55', 1),
	(9, 0, 0, 'hello_rester', 'Day 1 - 4 - Andre - Lear - CAMP MD Workshop.pdf', '818a1677dded11524db4_Day_1_-_4_-_Andre_-_Lear_-_CAMP_MD_Workshop.pdf', 0, 578979, 'application/pdf', '', '2018-11-27 07:11:55', 1),
	(10, 0, 0, 'hello_rester', 'Day 2 - 1 - Presentation Marion Vasseur.pdf', '3d7c1d5224437635a440_Day_2_-_1_-_Presentation_Marion_Vasseur.pdf', 0, 753483, 'application/pdf', '', '2018-11-27 07:11:55', 1),
	(11, 0, 0, 'hello_rester', 'Day 2 - 2 - V2X-workshop.pdf', 'ee9ac4bee24329c38da7_Day_2_-_2_-_V2X-workshop.pdf', 0, 643683, 'application/pdf', '', '2018-11-27 07:11:55', 1),
	(12, 0, 0, 'hello_rester', 'png (1).png', '3fae5af48ac27755ba85_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 07:11:55', 1),
	(13, 0, 0, 'hello_rester', 'png.png', '236d587288568bf8692d_png.png', 0, 388086, 'image/png', '', '2018-11-27 07:11:55', 1),
	(14, 0, 0, 'hello_rester', 'ThingPlug 연동 메시지_V02.doc', '4344245cab515efc77df_ThingPlug_연동_메시지_V02.doc', 0, 190976, 'application/msword', '', '2018-11-27 07:11:55', 1),
	(15, 0, 0, 'hello_rester', '물건이미지.2018.09.jpg', '4eb03d1fa8d3f845e33a_물건이미지.2018.09.jpg', 0, 156383, 'image/jpeg', '', '2018-11-27 07:11:55', 1),
	(16, 0, 0, 'hello_rester', '제목 없음.png', '119f0fad332f075e8d1d_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 07:12:44', 1),
	(17, 0, 0, 'hello_rester', '00001_150803_094521_4a1e.jpg', '9d252485395227a197b6_00001_150803_094521_4a1e.jpg', 0, 133105, 'image/jpeg', '', '2018-11-27 07:12:44', 1),
	(18, 0, 0, 'hello_rester', 'cb042000917.jpg', '98b81b2fd07bb60e2194_cb042000917.jpg', 0, 3387322, 'image/jpeg', '', '2018-11-27 07:12:44', 1),
	(19, 0, 0, 'hello_rester', 'Day 1 - 4 - Andre - Lear - CAMP MD Workshop.pdf', '94830dc839210ab24e2a_Day_1_-_4_-_Andre_-_Lear_-_CAMP_MD_Workshop.pdf', 0, 578979, 'application/pdf', '', '2018-11-27 07:12:45', 1),
	(20, 0, 0, 'hello_rester', 'Day 2 - 1 - Presentation Marion Vasseur.pdf', '60316d100daaad7d7145_Day_2_-_1_-_Presentation_Marion_Vasseur.pdf', 0, 753483, 'application/pdf', '', '2018-11-27 07:12:45', 1),
	(21, 0, 0, 'hello_rester', 'Day 2 - 2 - V2X-workshop.pdf', '5b789a7f1fe94cc1c119_Day_2_-_2_-_V2X-workshop.pdf', 0, 643683, 'application/pdf', '', '2018-11-27 07:12:45', 1),
	(22, 0, 0, 'hello_rester', 'png (1).png', 'dbeb1c5fac79efb4ba54_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 07:12:45', 1),
	(23, 0, 0, 'hello_rester', 'png.png', '2688ca49463cc125e346_png.png', 0, 388086, 'image/png', '', '2018-11-27 07:12:45', 1),
	(24, 0, 0, 'hello_rester', 'ThingPlug 연동 메시지_V02.doc', 'a6bc6ba48c9571928e48_ThingPlug_연동_메시지_V02.doc', 0, 190976, 'application/msword', '', '2018-11-27 07:12:45', 1),
	(25, 0, 0, 'hello_rester', '물건이미지.2018.09.jpg', 'ce6b7e1e95e7f86a8165_물건이미지.2018.09.jpg', 0, 156383, 'image/jpeg', '', '2018-11-27 07:12:45', 1),
	(26, 0, 0, 'hello_rester', '제목 없음.png', '3179212aeae21fae6241_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 07:13:11', 1),
	(27, 0, 0, 'hello_rester', '00001_150803_094521_4a1e.jpg', '84cfec79ddf388683be4_00001_150803_094521_4a1e.jpg', 0, 133105, 'image/jpeg', '', '2018-11-27 07:13:11', 1),
	(28, 0, 0, 'hello_rester', 'cb042000917.jpg', '1e8601a7ab1580e7c5f9_cb042000917.jpg', 0, 3387322, 'image/jpeg', '', '2018-11-27 07:13:11', 1),
	(29, 0, 0, 'hello_rester', 'Day 1 - 4 - Andre - Lear - CAMP MD Workshop.pdf', 'cc7d3d828682cba9ad7c_Day_1_-_4_-_Andre_-_Lear_-_CAMP_MD_Workshop.pdf', 0, 578979, 'application/pdf', '', '2018-11-27 07:13:11', 1),
	(30, 0, 0, 'hello_rester', 'Day 2 - 1 - Presentation Marion Vasseur.pdf', 'a95dba78e8f8dd48ee00_Day_2_-_1_-_Presentation_Marion_Vasseur.pdf', 0, 753483, 'application/pdf', '', '2018-11-27 07:13:11', 1),
	(31, 0, 0, 'hello_rester', 'Day 2 - 2 - V2X-workshop.pdf', '595c47f575739624b755_Day_2_-_2_-_V2X-workshop.pdf', 0, 643683, 'application/pdf', '', '2018-11-27 07:13:11', 1),
	(32, 0, 0, 'hello_rester', 'png (1).png', 'c451e02e41f196d69bdd_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 07:13:11', 1),
	(33, 0, 0, 'hello_rester', 'png.png', 'f360d4ceb79a72736d5a_png.png', 0, 388086, 'image/png', '', '2018-11-27 07:13:11', 1),
	(34, 0, 0, 'hello_rester', 'ThingPlug 연동 메시지_V02.doc', '0efac25c28fdae87274b_ThingPlug_연동_메시지_V02.doc', 0, 190976, 'application/msword', '', '2018-11-27 07:13:11', 1),
	(35, 0, 0, 'hello_rester', '물건이미지.2018.09.jpg', 'c03c3e0019cca54e0faa_물건이미지.2018.09.jpg', 0, 156383, 'image/jpeg', '', '2018-11-27 07:13:11', 1),
	(36, 0, 0, 'hello_rester', '제목 없음.png', 'a0be892af9b94a9d881e_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 07:13:40', 1),
	(37, 0, 0, 'hello_rester', '00001_150803_094521_4a1e.jpg', 'b31c769838f14f3625c2_00001_150803_094521_4a1e.jpg', 0, 133105, 'image/jpeg', '', '2018-11-27 07:13:40', 1),
	(38, 0, 0, 'hello_rester', 'cb042000917.jpg', '7fd9c99d7d2c3ce505c7_cb042000917.jpg', 0, 3387322, 'image/jpeg', '', '2018-11-27 07:13:40', 1),
	(39, 0, 0, 'hello_rester', 'Day 1 - 4 - Andre - Lear - CAMP MD Workshop.pdf', '7876803f5bb3392baa85_Day_1_-_4_-_Andre_-_Lear_-_CAMP_MD_Workshop.pdf', 0, 578979, 'application/pdf', '', '2018-11-27 07:13:40', 1),
	(40, 0, 0, 'hello_rester', 'Day 2 - 1 - Presentation Marion Vasseur.pdf', '0c70e97ce4a847099c34_Day_2_-_1_-_Presentation_Marion_Vasseur.pdf', 0, 753483, 'application/pdf', '', '2018-11-27 07:13:40', 1),
	(41, 0, 0, 'hello_rester', 'Day 2 - 2 - V2X-workshop.pdf', '0ac48b390e4ffcd2396b_Day_2_-_2_-_V2X-workshop.pdf', 0, 643683, 'application/pdf', '', '2018-11-27 07:13:40', 1),
	(42, 0, 0, 'hello_rester', 'png (1).png', '091b43848207e062e1d0_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 07:13:40', 1),
	(43, 0, 0, 'hello_rester', 'png.png', '4be3864970c76c2d8112_png.png', 0, 388086, 'image/png', '', '2018-11-27 07:13:40', 1),
	(44, 0, 0, 'hello_rester', 'ThingPlug 연동 메시지_V02.doc', '7502a88051155bb4319b_ThingPlug_연동_메시지_V02.doc', 0, 190976, 'application/msword', '', '2018-11-27 07:13:40', 1),
	(45, 0, 0, 'hello_rester', '물건이미지.2018.09.jpg', 'a114c3343084ecfc572f_물건이미지.2018.09.jpg', 0, 156383, 'image/jpeg', '', '2018-11-27 07:13:40', 1),
	(46, 0, 0, 'hello_rester', '제목 없음.png', 'fe4b376036fbe27cd443_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 07:14:11', 1),
	(47, 0, 0, 'hello_rester', '00001_150803_094521_4a1e.jpg', '57195dc53dd0b5e25da3_00001_150803_094521_4a1e.jpg', 0, 133105, 'image/jpeg', '', '2018-11-27 07:14:11', 1),
	(48, 0, 0, 'hello_rester', 'cb042000917.jpg', '41b93c3fc9fda35e86c0_cb042000917.jpg', 0, 3387322, 'image/jpeg', '', '2018-11-27 07:14:11', 1),
	(49, 0, 0, 'hello_rester', 'Day 1 - 0 - CAMP MBD Workshop.pptx', 'afe7a28a7a5db6d0d34d_Day_1_-_0_-_CAMP_MBD_Workshop.pptx', 0, 6772130, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 07:14:11', 1),
	(50, 0, 0, 'hello_rester', 'Day 1 - 3 - MDB-Workshop-Security-Innovation.pptx', '8917addbc4cc3b7ccfbe_Day_1_-_3_-_MDB-Workshop-Security-Innovation.pptx', 0, 1527930, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 07:14:11', 1),
	(51, 0, 0, 'hello_rester', 'Day 1 - 4 - Andre - Lear - CAMP MD Workshop.pdf', '5e523be2e505b836af5f_Day_1_-_4_-_Andre_-_Lear_-_CAMP_MD_Workshop.pdf', 0, 578979, 'application/pdf', '', '2018-11-27 07:14:11', 1),
	(52, 0, 0, 'hello_rester', 'Day 1 - 4 - Andre - Lear - CAMP MD Workshop.pptx', 'd0865d5a2adb3534a2cb_Day_1_-_4_-_Andre_-_Lear_-_CAMP_MD_Workshop.pptx', 0, 801194, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 07:14:11', 1),
	(53, 0, 0, 'hello_rester', 'Day 2 - 1 - Presentation Marion Vasseur.pdf', 'e2803c7bf0f0cd316837_Day_2_-_1_-_Presentation_Marion_Vasseur.pdf', 0, 753483, 'application/pdf', '', '2018-11-27 07:14:11', 1),
	(54, 0, 0, 'hello_rester', 'Day 2 - 2 - V2X-workshop.pdf', 'cf4b3a98c90675056a06_Day_2_-_2_-_V2X-workshop.pdf', 0, 643683, 'application/pdf', '', '2018-11-27 07:14:11', 1),
	(55, 0, 0, 'hello_rester', 'Day 2 - 3 - V2X Talk Fagiolini.pptx', '507c4c501958d474ba58_Day_2_-_3_-_V2X_Talk_Fagiolini.pptx', 0, 11241391, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 07:14:11', 1),
	(56, 0, 0, 'hello_rester', 'Day 2 - 4 - 2016-11_CAMP_workshop.pptx', '29488ec8aed14a04abf7_Day_2_-_4_-_2016-11_CAMP_workshop.pptx', 0, 2571864, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 07:14:11', 1),
	(57, 0, 0, 'hello_rester', 'png (1).png', '38c105f5610895441cce_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 07:14:12', 1),
	(58, 0, 0, 'hello_rester', 'png.png', '459c8464efcd2152d1a3_png.png', 0, 388086, 'image/png', '', '2018-11-27 07:14:12', 1),
	(59, 0, 0, 'hello_rester', 'ThingPlug 연동 메시지_V02.doc', '4dc5c10c1bee4b2a6f80_ThingPlug_연동_메시지_V02.doc', 0, 190976, 'application/msword', '', '2018-11-27 07:14:12', 1),
	(60, 0, 0, 'hello_rester', '물건이미지.2018.09.jpg', 'f0e983c542fe99c8aec2_물건이미지.2018.09.jpg', 0, 156383, 'image/jpeg', '', '2018-11-27 07:14:12', 1),
	(61, 0, 0, 'hello_rester', '제목 없음.png', '6b95fb6e82fa945824a8_제목_없음.png', 0, 185780, 'image/png', '', '2018-11-27 07:36:18', 1),
	(62, 0, 0, 'hello_rester', 'png (1).png', 'e9cfc0ef1e72353ba270_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 07:36:18', 1),
	(63, 0, 0, 'hello_rester', 'png.png', '8b97b15454579897362a_png.png', 0, 388086, 'image/png', '', '2018-11-27 07:36:18', 1),
	(64, 0, 0, 'hello_rester', 'png.png', '3f49abbe8887ffe00cb2_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:31:05', 1),
	(65, 0, 0, 'hello_rester', 'png.png', '05ea38cfc4b7c4f144e3_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:32:10', 1),
	(66, 0, 0, 'hello_rester', 'png.png', '23695a7ca3f20cb0ffe3_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:32:23', 1),
	(67, 1, 2, 'hello_rester', 'png.png', 'd818a4b6117c8db5d579_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:33:23', 1),
	(68, 1, 2, 'hello_rester', 'png.png', 'a282278eb9400aa72218_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:34:25', 1),
	(69, 1, 2, 'hello_rester', 'png.png', '8571251e0cdb5258abfa_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:37:34', 1),
	(70, 1, 2, 'hello_rester', 'png.png', '8189be4dcdfe12424a57_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:38:37', 1),
	(71, 1, 2, 'hello_rester', 'png.png', '5c530e7cb03989e620b3_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:38:57', 1),
	(72, 1, 2, 'hello_rester', 'png (1).png', 'c4cbc6c56fec70ec3125_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 12:38:57', 1),
	(73, 1, 2, 'hello_rester', 'png.png', 'a1da06233cb8569dab9b_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:38:57', 1),
	(74, 1, 2, 'hello_rester', 'png.png', '8ce8b29e44953c4a9b45_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:40:10', 1),
	(75, 1, 2, 'hello_rester', 'Day 2 - 3 - V2X Talk Fagiolini.pptx', '769fe104e980f76ade9b_Day_2_-_3_-_V2X_Talk_Fagiolini.pptx', 0, 11241391, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 12:40:11', 1),
	(76, 1, 2, 'hello_rester', 'Day 2 - 4 - 2016-11_CAMP_workshop.pptx', '33834007e8e32e30bba1_Day_2_-_4_-_2016-11_CAMP_workshop.pptx', 0, 2571864, 'application/vnd.openxmlformats-officedocument.presentationml.presentation', '', '2018-11-27 12:40:11', 1),
	(77, 1, 2, 'hello_rester', 'png (1).png', 'd9e5fbeb72b03b0f0762_png_(1).png', 0, 388086, 'image/png', '', '2018-11-27 12:40:11', 1),
	(78, 1, 2, 'hello_rester', 'png.png', 'de33177f617f69b39c46_png.png', 0, 388086, 'image/png', '', '2018-11-27 12:40:11', 1),
	(79, 1, 2, 'hello_rester', 'top_logo.gif', 'ad774c1af1c0a24ba0f5_top_logo.gif', 0, 4757, 'image/gif', '', '2018-11-27 12:48:10', 1);
/*!40000 ALTER TABLE `example_file` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
