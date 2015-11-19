/*
SQLyog Ultimate v11.11 (64 bit)
MySQL - 5.6.26 : Database - webuser
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`webuser` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `webuser`;

/*Table structure for table `acl` */

DROP TABLE IF EXISTS `acl`;

CREATE TABLE `acl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app` varchar(20) NOT NULL,
  `role` varchar(20) NOT NULL,
  `acl` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;

/*Data for the table `acl` */

insert  into `acl`(`id`,`app`,`role`,`acl`) values (34,'theme','manager','view'),(35,'theme','manager','base'),(36,'theme','manager','wallpaper'),(37,'theme','manager','edit'),(38,'theme','manager','push'),(39,'theme','manager','pay'),(40,'theme','manager','management'),(41,'theme','wallpaper','base'),(42,'theme','wallpaper','wallpaper'),(43,'theme','wallpaper','edit');

/*Table structure for table `role` */

DROP TABLE IF EXISTS `role`;

CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `app` varchar(20) NOT NULL,
  `role` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;

/*Data for the table `role` */

insert  into `role`(`id`,`user_id`,`app`,`role`) values (5,99999,'browser','admin'),(4,2,'browser','admin'),(3,2,'recommend','manager'),(17,9,'theme','manager'),(1,1,'recommend','manager'),(6,4,'recommend','manager'),(7,4,'browser','admin'),(8,3,'activate','admin'),(9,5,'activate','admin'),(10,6,'statistic','manager'),(11,7,'recommend','manager'),(12,7,'browser','admin'),(13,7,'activate','admin'),(14,7,'statistic','manager'),(15,8,'theme','manager'),(16,1,'statistic','manager'),(18,10,'statistic','manager'),(19,11,'theme','manager'),(20,12,'managecollect','manager'),(21,13,'report','manager'),(22,17,'recommend','member'),(23,17,'widgetimg','admin'),(24,18,'recommend','manager'),(25,8,'report','manager'),(26,19,'widgetimg','admin'),(27,20,'theme','wallpaper'),(28,13,'audit','manager'),(29,8,'launcher','manager'),(30,21,'theme','visitor'),(31,21,'report','manager'),(32,21,'launcher','manager');

/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` char(32) NOT NULL,
  `email` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `state` tinyint(1) NOT NULL,
  `addtime` datetime NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;

/*Data for the table `user` */

insert  into `user`(`userid`,`username`,`password`,`email`,`display_name`,`state`,`addtime`) values (4,'lijie','e10adc3949ba59abbe56e057f20f883e','lijie1@yulong.com','李杰',0,'2013-05-20 10:01:50'),(3,'yangdan','e10adc3949ba59abbe56e057f20f883e','yangdan@yulong.com','杨丹',0,'2013-05-14 21:10:30'),(2,'admin','97db1846570837fce6ff62a408f1c26a','admin@126.com','管理员',0,'2013-05-14 21:09:43'),(1,'dongyufeng','97db1846570837fce6ff62a408f1c26a','dongyufeng@yulong.com','董裕丰',0,'2013-05-02 07:44:18'),(5,'liangweiwei','7378e8963942a357e34df20a54a21a29','liangweiwei@yulong.com','梁维伟',1,'2013-05-29 20:01:38'),(6,'jingxueying','97db1846570837fce6ff62a408f1c26a','jingxueying@yulong.com','井雪莹',1,'2013-06-20 15:42:43'),(7,'manager','7378e8963942a357e34df20a54a21a29','lijie1@yulong.com','李杰',1,'2013-06-24 17:00:38'),(8,'liuyanjiao','f17bf460327e86cf527950b5c32283bd','liuyanjiao@yulong.com','刘艳娇',1,'2013-06-27 10:04:24'),(9,'huanglidi','97db1846570837fce6ff62a408f1c26a','huanglidi@yulong.com','黄郦迪',1,'2013-07-02 10:34:27'),(10,'hulixiang','075390e6615e010771359c9cd6fabd21','hulixiang@yulong.com','胡立祥',1,'2013-07-02 11:38:49'),(11,'wangjinjiang','97db1846570837fce6ff62a408f1c26a','wangjinjiang@yulong.com','王金江',1,'2013-07-05 09:23:46'),(12,'zhaozhun','97db1846570837fce6ff62a408f1c26a','zhaozhun@yulong.com','赵准',1,'2013-07-22 13:14:39'),(13,'chengjie','97db1846570837fce6ff62a408f1c26a','chengjie@yulong.com','程杰',1,'2013-10-30 09:48:05'),(17,'bifen','97db1846570837fce6ff62a408f1c26a','bifen@yulong.com','毕芬',1,'2014-01-28 16:23:55'),(18,'zhangsheng','97db1846570837fce6ff62a408f1c26a','zhangsheng1@yulong.com','张晟',1,'2014-05-15 00:00:00'),(19,'jinlinli','97db1846570837fce6ff62a408f1c26a','jinlinli@yulong.com','金林黎',1,'2014-05-24 14:59:45'),(20,'chenweiming','97db1846570837fce6ff62a408f1c26a','chenweiming@yulong.com','陈维明',1,'2014-07-11 15:07:47'),(21,'liyi','97db1846570837fce6ff62a408f1c26a','liyi3@yulong.com','李益',1,'2015-01-19 16:46:32');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
