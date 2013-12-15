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
-- Dumping routines for database 'btc'
--
/*!50003 DROP PROCEDURE IF EXISTS `upsert_funds` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50020 DEFINER=`root`@`localhost`*/ /*!50003 PROCEDURE `upsert_funds`(
    code varchar(3),
    amount float,
    lastupdate datetime,
    setlock int
)
BEGIN
declare isset int;
declare lastHistId int;
declare lastAmount float;

select count(*) into isset
from funds
where funds.code = code;
IF isset>0 THEN
    UPDATE funds
    SET 
        funds.amount = amount,
        funds.lastupdate = lastupdate,
        funds.`lock` = setlock
    WHERE funds.code = code;
ELSE
    INSERT INTO funds
    SET 
        funds.amount = amount,
        funds.lastupdate = lastupdate,
        funds.`lock` = setlock,
        funds.code = code;
END IF;

select max(id) into lastHistId 
from funds_history 
where funds_history.code = code;

select amount into lastAmount
from funds_history
where id = lastHistId;


INSERT INTO funds_history 
SET
    code = code,
    amount = amount,
    dt = lastupdate;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `upsert_pair_price` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50020 DEFINER=`root`@`localhost`*/ /*!50003 PROCEDURE `upsert_pair_price`(
    pairCode varchar(7),
    updated DATETIME,
    sell float,
    buy float
)
BEGIN
declare isset int;
declare vector varchar(8);
declare lastPriceId int;
declare lastSell float;
declare lastBuy float;
declare midPrice float;
declare lastMidPrice float;
declare weight float;

select count(*) into isset
from pair_prices pp
where pp.pair = pairCode;
IF isset>0 THEN
    UPDATE pair_prices as pp
    SET pp.updated = updated,
        pp.sell = sell,
        pp.buy = buy
    WHERE pp.pair = pairCode;
ELSE
    INSERT INTO pair_prices
    SET pair_prices.updated = updated,
        pair_prices.sell = sell,
        pair_prices.buy = buy,
        pair_prices.pair = pairCode;
END IF;

select max(ph.id) into lastPriceId 
from price_history ph
where ph.pair = pairCode;

select ph.sell,ph.buy into lastSell,lastBuy
from price_history ph
where ph.id = lastPriceId;

set midPrice = (sell+buy)/2;
set lastMidPrice = (lastSell+lastBuy)/2;
set weight = abs(100-(100*midPrice/lastMidPrice));

IF lastMidPrice > midPrice then
    set vector = 'DOWN';
ELSE
    IF lastMidPrice < midPrice then
        set vector = 'UP';
    else 
        set vector = 'NOCHANGE';
    end if;
end if;

INSERT INTO price_history
SET
    price_history.pair = pairCode,
    price_history.sell = sell,
    price_history.buy = buy,
    price_history.dt = updated,
    price_history.vector = vector,
    price_history.weight = weight;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-12-15 22:57:58
