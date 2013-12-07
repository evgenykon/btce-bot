CREATE DATABASE  IF NOT EXISTS `btc` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `btc`;
-- MySQL dump 10.13  Distrib 5.5.29, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: btc
-- ------------------------------------------------------
-- Server version	5.5.29-0ubuntu0.12.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `pair_prices`
--

DROP TABLE IF EXISTS `pair_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pair_prices` (
  `pair` varchar(7) NOT NULL,
  `updated` datetime DEFAULT NULL,
  `sell` float DEFAULT NULL,
  `buy` float DEFAULT NULL,
  `trend` int(11) NOT NULL DEFAULT '0',
  `weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pair`),
  UNIQUE KEY `pair_UNIQUE` (`pair`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pair_prices`
--

LOCK TABLES `pair_prices` WRITE;
/*!40000 ALTER TABLE `pair_prices` DISABLE KEYS */;
INSERT INTO `pair_prices` VALUES ('btc_eur','2013-12-08 00:36:21',560.303,570.1,1,2),('btc_rur','2013-12-08 00:36:21',25000,25199,1,1),('btc_usd','2013-12-08 00:36:21',712,712.421,3,2),('eur_usd','2013-12-08 00:36:21',1.25345,1.25983,0,0),('ftc_btc','2013-12-08 00:36:21',0.00048,0.00049,0,0),('ltc_btc','2013-12-08 00:36:21',0.03292,0.03298,3,1),('ltc_eur','2013-12-08 00:36:21',18.503,18.559,0,0),('ltc_rur','2013-12-08 00:36:21',810,832.5,0,0),('ltc_usd','2013-12-08 00:36:21',23.4002,23.44,0,0),('nmc_btc','2013-12-08 00:36:21',0.00742,0.00744,0,1),('nmc_usd','2013-12-08 00:36:21',5.262,5.288,0,0),('nvc_btc','2013-12-08 00:36:21',0.02134,0.02139,1,0),('nvc_usd','2013-12-08 00:36:21',14.809,15.19,0,0),('ppc_btc','2013-12-08 00:36:21',0.00471,0.00473,0,1),('ppc_usd','2013-12-08 00:36:21',3.359,3.36,0,0),('trc_btc','2013-12-08 00:36:21',0.0007,0.00072,-3,8),('usd_rur','2013-12-08 00:36:21',35.4,35.54,0,0),('xpm_btc','2013-12-08 00:36:21',0.00399,0.00402,2,1);
/*!40000 ALTER TABLE `pair_prices` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-12-08  0:36:28
