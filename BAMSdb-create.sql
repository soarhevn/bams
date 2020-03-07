# ************************************************************
# Sequel Pro SQL dump
# Version 5446
#
# https://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: moose.sakuratechnology.com (MySQL 5.5.5-10.4.12-MariaDB-1:10.4.12+maria~bionic)
# Database: bams
# Generation Time: 2020-03-07 21:24:41 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
SET NAMES utf8mb4;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table accountGroups
# ------------------------------------------------------------

CREATE TABLE `accountGroups` (
  `accGrpID` smallint(6) NOT NULL AUTO_INCREMENT,
  `accGrpName` varchar(20) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `inactive` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`accGrpID`),
  UNIQUE KEY `accGrpName` (`accGrpName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table accountGroupXref
# ------------------------------------------------------------

CREATE TABLE `accountGroupXref` (
  `accGrp_id` smallint(6) NOT NULL,
  `accName_id` smallint(6) NOT NULL,
  PRIMARY KEY (`accGrp_id`,`accName_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table accountNames
# ------------------------------------------------------------

CREATE TABLE `accountNames` (
  `accountID` smallint(6) NOT NULL AUTO_INCREMENT,
  `acctCODE` varchar(20) DEFAULT NULL,
  `accountName` varchar(20) NOT NULL,
  `accountType` tinyint(3) DEFAULT NULL,
  `bankAccount` tinyint(1) DEFAULT 0,
  `description` varchar(100) DEFAULT NULL,
  `inactive` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `autoPay` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `xAccountID` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`accountID`),
  UNIQUE KEY `acctCODE` (`acctCODE`),
  KEY `xAccountID` (`xAccountID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table accountReport
# ------------------------------------------------------------

CREATE TABLE `accountReport` (
  `accountID` smallint(5) unsigned NOT NULL,
  `amount` decimal(19,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`accountID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table accounts
# ------------------------------------------------------------

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transDate` date NOT NULL,
  `accountID` smallint(6) unsigned NOT NULL,
  `debit` decimal(19,2) DEFAULT NULL,
  `credit` decimal(19,2) DEFAULT NULL,
  `amountIn` decimal(19,2) DEFAULT NULL,
  `amountOut` decimal(19,2) DEFAULT NULL,
  `notes` varchar(100) NOT NULL DEFAULT ' ',
  `wireXfer` tinyint(1) unsigned DEFAULT NULL,
  `idNumber` varchar(10) DEFAULT NULL,
  `changeDate` date DEFAULT NULL,
  `xAccountID` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accountID` (`accountID`),
  KEY `xAccountID` (`xAccountID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table accountType
# ------------------------------------------------------------

CREATE TABLE `accountType` (
  `acctTypeID` tinyint(3) NOT NULL,
  `atName` varchar(50) DEFAULT NULL,
  `atNameEn` varchar(50) DEFAULT NULL,
  `debit` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`acctTypeID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table billingMonths
# ------------------------------------------------------------

CREATE TABLE `billingMonths` (
  `monthNum` tinyint(12) unsigned NOT NULL,
  `monthlyBill` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `duesHalf` tinyint(2) unsigned NOT NULL,
  PRIMARY KEY (`monthNum`),
  KEY `duesHalf` (`duesHalf`),
  KEY `monthlyBill` (`monthlyBill`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table billType
# ------------------------------------------------------------

CREATE TABLE `billType` (
  `id` int(1) unsigned NOT NULL AUTO_INCREMENT,
  `note_en` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table budget
# ------------------------------------------------------------

CREATE TABLE `budget` (
  `budgetID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `accountID` smallint(6) unsigned NOT NULL,
  `budgetYear` smallint(4) unsigned NOT NULL,
  `budgetAmount` decimal(19,0) NOT NULL DEFAULT 0,
  `budgetNotes` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`budgetID`),
  KEY `accountID` (`accountID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table dependents
# ------------------------------------------------------------

CREATE TABLE `dependents` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `idNumber` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `idParent` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `name` varchar(50) CHARACTER SET big5 DEFAULT NULL,
  `cardNum` varchar(6) CHARACTER SET latin1 DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `handicap` tinyint(3) unsigned DEFAULT 1,
  `insureDate` date DEFAULT NULL,
  `inactive` date DEFAULT NULL,
  `memberDate` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idNumber` (`idNumber`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table duesHalfName
# ------------------------------------------------------------

CREATE TABLE `duesHalfName` (
  `duesHalf` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `duesHalfName` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`duesHalf`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table groups
# ------------------------------------------------------------

CREATE TABLE `groups` (
  `Group` int(11) DEFAULT NULL,
  `Sub` int(11) DEFAULT NULL,
  `Card` varchar(255) DEFAULT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Birthdate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table handicapAdjustment
# ------------------------------------------------------------

CREATE TABLE `handicapAdjustment` (
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `percentage` tinyint(4) DEFAULT NULL,
  `discount` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table handicapArray
# ------------------------------------------------------------

CREATE TABLE `handicapArray` (
  `handicap` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `handiName` varchar(20) NOT NULL,
  PRIMARY KEY (`handicap`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table handicapDiscount
# ------------------------------------------------------------

CREATE TABLE `handicapDiscount` (
  `level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `discount` mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table income
# ------------------------------------------------------------

CREATE TABLE `income` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `idNumber` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `monthNum` tinyint(12) unsigned DEFAULT NULL,
  `duesYear` year(4) NOT NULL DEFAULT 2007,
  `duesHalf` tinyint(3) unsigned DEFAULT NULL,
  `unionDues` int(11) DEFAULT 0,
  `laborIns` int(11) DEFAULT 0,
  `medIns` int(11) DEFAULT 0,
  `newMemDues` int(11) DEFAULT 0,
  `newMemDues2` int(11) DEFAULT 0,
  `paidDate` date DEFAULT NULL,
  `wire` smallint(6) unsigned DEFAULT NULL,
  `changeDate` date DEFAULT NULL,
  `unionDuesID` int(11) DEFAULT NULL,
  `laborInsID` int(11) DEFAULT NULL,
  `medInsID` int(11) DEFAULT NULL,
  `newMemDuesID` int(11) DEFAULT NULL,
  `newMemDues2ID` int(11) DEFAULT NULL,
  `billType` int(1) unsigned DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `idNumber` (`idNumber`),
  KEY `paidDate` (`paidDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table incomeAdd2NextPeriodBill
# ------------------------------------------------------------

CREATE TABLE `incomeAdd2NextPeriodBill` (
  `idNumber` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `monthNum` tinyint(12) unsigned DEFAULT 0,
  `duesYear` year(4) NOT NULL DEFAULT 2018,
  `duesHalf` tinyint(3) unsigned DEFAULT NULL,
  `unionDues` int(11) DEFAULT 0,
  `laborIns` int(11) DEFAULT 0,
  `medIns` int(11) DEFAULT 0,
  `newMemDues` int(11) DEFAULT 0,
  `newMemDues2` int(11) DEFAULT 0,
  `changeDate` date DEFAULT NULL,
  `billType` int(1) unsigned DEFAULT NULL,
  KEY `idNumber` (`idNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



# Dump of table members
# ------------------------------------------------------------

CREATE TABLE `members` (
  `idNumber` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL,
  `cardNum` varchar(6) DEFAULT '',
  `inactive` date DEFAULT NULL,
  `numDeps` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `salary` decimal(19,2) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `handicap` tinyint(3) unsigned DEFAULT NULL,
  `memberDate` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `homePhone` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  `workPhone` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  `mblPhone` varchar(50) CHARACTER SET latin1 DEFAULT NULL,
  `email` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `changeDate` date DEFAULT NULL,
  `changeDateSal` date DEFAULT NULL,
  `insureHealth` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `insureLabor` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `insureDateHealth` date DEFAULT NULL,
  `insureDateLabor` date DEFAULT NULL,
  `monthlyBill` tinyint(1) unsigned DEFAULT 0,
  `referrer` varchar(10) DEFAULT NULL,
  `salaryIncrease` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `representative` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `boardMember` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`idNumber`),
  KEY `cardNumIndx` (`cardNum`),
  KEY `monthlyBill` (`monthlyBill`),
  KEY `salary` (`salary`),
  KEY `handicap` (`handicap`),
  KEY `referrer` (`referrer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table membersActive
# ------------------------------------------------------------

CREATE TABLE `membersActive` (
   `idNumber` VARCHAR(10) NOT NULL DEFAULT '',
   `name` VARCHAR(50) NOT NULL,
   `cardNum` VARCHAR(6) NULL DEFAULT '',
   `address` VARCHAR(255) NULL DEFAULT NULL
) ENGINE=MyISAM;



# Dump of table reporting
# ------------------------------------------------------------

CREATE TABLE `reporting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `groupID` int(11) DEFAULT NULL,
  `yearR` int(4) DEFAULT NULL,
  `monthR` int(2) DEFAULT NULL,
  `typeR` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table sex
# ------------------------------------------------------------

CREATE TABLE `sex` (
  `ID` tinyint(4) NOT NULL DEFAULT 0,
  `sex` char(5) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table stats
# ------------------------------------------------------------

CREATE TABLE `stats` (
   `ord` INT(1) NOT NULL DEFAULT '0',
   `in_out` VARCHAR(3) NOT NULL DEFAULT '',
   `in_outZH` VARCHAR(7) NOT NULL DEFAULT '',
   `year_quarter` VARCHAR(8) NULL DEFAULT NULL,
   `male` BIGINT(22) NOT NULL DEFAULT '0',
   `female` BIGINT(22) NOT NULL DEFAULT '0',
   `total` BIGINT(22) NOT NULL DEFAULT '0'
) ENGINE=MyISAM;



# Dump of table subsidies
# ------------------------------------------------------------

CREATE TABLE `subsidies` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `subsidyType` tinyint(1) unsigned NOT NULL,
  `idNumber` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `applicationDate` date DEFAULT NULL,
  `grantDate` date DEFAULT NULL,
  `grantID` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `subsidyAmount` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idNumber` (`idNumber`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table subsidyType
# ------------------------------------------------------------

CREATE TABLE `subsidyType` (
  `id` tinyint(1) unsigned NOT NULL,
  `subsidyTypeName` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



# Dump of table textBlocks
# ------------------------------------------------------------

CREATE TABLE `textBlocks` (
  `id` smallint(6) unsigned NOT NULL,
  `textFlag` tinyint(4) unsigned DEFAULT NULL,
  `descript_zh` varchar(50) DEFAULT NULL,
  `descript_en` varchar(50) DEFAULT NULL,
  `textBlock` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table transactions
# ------------------------------------------------------------

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idMaster` int(11) unsigned NOT NULL,
  `accountID` smallint(6) unsigned NOT NULL,
  `debit` decimal(19,2) DEFAULT NULL,
  `credit` decimal(19,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accountID` (`accountID`),
  KEY `idMaster` (`idMaster`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table transactionsMaster
# ------------------------------------------------------------

CREATE TABLE `transactionsMaster` (
  `idMaster` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `transDate` date NOT NULL,
  `changeDate` date DEFAULT NULL,
  `memIdNum` varchar(10) DEFAULT NULL,
  `notes` varchar(100) NOT NULL,
  `marker` int(11) DEFAULT NULL,
  `code` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`idMaster`),
  KEY `transDate` (`transDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table transactionView
# ------------------------------------------------------------

CREATE TABLE `transactionView` (
   `idMaster` INT(11) UNSIGNED NOT NULL DEFAULT '0',
   `transDate` DATE NOT NULL,
   `accountID` SMALLINT(6) UNSIGNED NULL DEFAULT NULL,
   `accountName` VARCHAR(73) NULL DEFAULT NULL,
   `debit` VARCHAR(23) NULL DEFAULT NULL,
   `credit` VARCHAR(23) NULL DEFAULT NULL,
   `memIdNum` VARCHAR(10) NULL DEFAULT NULL,
   `notes` VARCHAR(100) NOT NULL
) ENGINE=MyISAM;



# Dump of table unionRates
# ------------------------------------------------------------

CREATE TABLE `unionRates` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `salary` decimal(19,2) NOT NULL DEFAULT 0.00,
  `unionDues` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unionDues_dateEffective` date NOT NULL,
  `laborIns` decimal(10,2) NOT NULL DEFAULT 0.00,
  `laborIns_dateEffective` date NOT NULL,
  `medIns` decimal(10,2) NOT NULL DEFAULT 0.00,
  `medIns_dateEffective` date NOT NULL,
  `salDisplay` varchar(50) DEFAULT NULL,
  `salEndDate` date DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table unionRatesCurrent
# ------------------------------------------------------------

CREATE TABLE `unionRatesCurrent` (
   `ID` INT(10) UNSIGNED NOT NULL DEFAULT '0',
   `salDisplay` VARCHAR(50) NULL DEFAULT NULL,
   `salary` DECIMAL(19,2) NOT NULL DEFAULT '0.00',
   `unionDues` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
   `laborIns` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
   `laborIns_dateEffective` DATE NOT NULL,
   `medIns` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
   `medIns_dateEffective` DATE NOT NULL,
   `salEndDate` DATE NULL DEFAULT NULL
) ENGINE=MyISAM;



# Dump of table unionSalaryCurrent
# ------------------------------------------------------------

CREATE TABLE `unionSalaryCurrent` (
   `ID` INT(10) UNSIGNED NULL DEFAULT NULL,
   `salary` DECIMAL(19,2) NOT NULL DEFAULT '0.00'
) ENGINE=MyISAM;



# Dump of table userAuth
# ------------------------------------------------------------

CREATE TABLE `userAuth` (
  `username` char(50) NOT NULL,
  `passwd` char(50) NOT NULL,
  `access` char(25) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;





# Replace placeholder table for unionRatesCurrent with correct view syntax
# ------------------------------------------------------------

DROP TABLE `unionRatesCurrent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`soarhevn`@`localhost` SQL SECURITY DEFINER VIEW `unionRatesCurrent`
AS SELECT
   `uR`.`ID` AS `ID`,
   `uR`.`salDisplay` AS `salDisplay`,
   `uR`.`salary` AS `salary`,
   `uR`.`unionDues` AS `unionDues`,
   `uR`.`laborIns` AS `laborIns`,
   `uR`.`laborIns_dateEffective` AS `laborIns_dateEffective`,
   `uR`.`medIns` AS `medIns`,
   `uR`.`medIns_dateEffective` AS `medIns_dateEffective`,
   `uR`.`salEndDate` AS `salEndDate`
FROM (`unionRates` `uR` join `unionSalaryCurrent` `uSC` on(`uR`.`ID` = `uSC`.`ID`)) where `uR`.`salEndDate` > curdate() or `uR`.`salEndDate` is null order by `uR`.`salary`;


# Replace placeholder table for stats with correct view syntax
# ------------------------------------------------------------

DROP TABLE `stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`soarhevn`@`localhost` SQL SECURITY DEFINER VIEW `stats` AS (select 0 AS `ord`,'in' AS `in_out`,'新加會員' AS `in_outZH`,concat_ws(', ',year(`members`.`memberDate`),concat('Q',quarter(`members`.`memberDate`))) AS `year_quarter`,count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,count(0) AS `total` from `members` where `members`.`memberDate` >= makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 4 quarter group by concat_ws(', ',year(`members`.`memberDate`),concat('Q',quarter(`members`.`memberDate`)))) union (select 0 AS `ord`,'out' AS `in_out`,'退會會員' AS `in_outZH`,concat_ws(', ',year(`members`.`inactive`),concat('Q',quarter(`members`.`inactive`))) AS `year_quarter`,-count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,-count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,-count(0) AS `total` from `members` where `members`.`inactive` >= makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 4 quarter group by concat_ws(', ',year(`members`.`inactive`),concat('Q',quarter(`members`.`inactive`)))) union (select 1 AS `ord`,'NOW' AS `in_out`,'目前會員' AS `in_outZH`,concat_ws(', ',year(curdate()),concat('Q',quarter(curdate()))) AS `year_quarter`,count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,count(0) AS `total` from `members` where `members`.`inactive` is null) union (select 1 AS `ord`,'END' AS `in_out`,'當季結束總人數' AS `in_outZH`,concat_ws(', ',year(curdate() - interval 1 quarter),concat('Q',quarter(curdate() - interval 1 quarter))) AS `year_quarter`,count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,count(0) AS `total` from `members` where (`members`.`inactive` is null or `members`.`inactive` >= makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 1 quarter) and `members`.`memberDate` < makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 1 quarter) union (select 1 AS `ord`,'END' AS `in_out`,'當季結束總人數' AS `in_outZH`,concat_ws(', ',year(curdate() - interval 2 quarter),concat('Q',quarter(curdate() - interval 2 quarter))) AS `year_quarter`,count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,count(0) AS `total` from `members` where (`members`.`inactive` is null or `members`.`inactive` >= makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 2 quarter) and `members`.`memberDate` < makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 2 quarter) union (select 1 AS `ord`,'END' AS `in_out`,'當季結束總人數' AS `in_outZH`,concat_ws(', ',year(curdate() - interval 3 quarter),concat('Q',quarter(curdate() - interval 3 quarter))) AS `year_quarter`,count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,count(0) AS `total` from `members` where (`members`.`inactive` is null or `members`.`inactive` >= makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 3 quarter) and `members`.`memberDate` < makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 3 quarter) union (select 1 AS `ord`,'END' AS `in_out`,'當季結束總人數' AS `in_outZH`,concat_ws(', ',year(curdate() - interval 4 quarter),concat('Q',quarter(curdate() - interval 4 quarter))) AS `year_quarter`,count(if(substr(`members`.`idNumber`,2,1) = 1,1,NULL)) AS `male`,count(if(substr(`members`.`idNumber`,2,1) = 2,1,NULL)) AS `female`,count(0) AS `total` from `members` where (`members`.`inactive` is null or `members`.`inactive` >= makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 4 quarter) and `members`.`memberDate` < makedate(year(curdate()),1) + interval quarter(curdate()) quarter - interval 4 quarter) order by `year_quarter`,`ord`,`in_out`;


# Replace placeholder table for transactionView with correct view syntax
# ------------------------------------------------------------

DROP TABLE `transactionView`;

CREATE ALGORITHM=UNDEFINED DEFINER=`soarhevn`@`localhost` SQL SECURITY DEFINER VIEW `transactionView`
AS SELECT
   distinct `tm`.`idMaster` AS `idMaster`,
   `tm`.`transDate` AS `transDate`,
   `trans`.`accountID` AS `accountID`,concat(`aN`.`accountName`,_utf8' - ',`acT`.`atName`) AS `accountName`,if(`trans`.`debit` > 0,format(`trans`.`debit`,0),NULL) AS `debit`,if(`trans`.`credit` > 0,format(`trans`.`credit`,0),NULL) AS `credit`,
   `tm`.`memIdNum` AS `memIdNum`,
   `tm`.`notes` AS `notes`
FROM (((`transactionsMaster` `tm` left join `transactions` `trans` on(`tm`.`idMaster` = `trans`.`idMaster`)) left join `accountNames` `aN` on(`trans`.`accountID` = `aN`.`accountID`)) left join `accountType` `acT` on(`aN`.`accountType` = `acT`.`acctTypeID`)) order by `tm`.`transDate`,`tm`.`idMaster`;


# Replace placeholder table for unionSalaryCurrent with correct view syntax
# ------------------------------------------------------------

DROP TABLE `unionSalaryCurrent`;

CREATE ALGORITHM=UNDEFINED DEFINER=`soarhevn`@`localhost` SQL SECURITY DEFINER VIEW `unionSalaryCurrent`
AS SELECT
   max(`unionRates`.`ID`) AS `ID`,
   `unionRates`.`salary` AS `salary`
FROM `unionRates` where `unionRates`.`laborIns_dateEffective` <= curdate() and `unionRates`.`medIns_dateEffective` <= curdate() group by `unionRates`.`salary`;


# Replace placeholder table for membersActive with correct view syntax
# ------------------------------------------------------------

DROP TABLE `membersActive`;

CREATE ALGORITHM=UNDEFINED DEFINER=`soarhevn`@`localhost` SQL SECURITY DEFINER VIEW `membersActive`
AS SELECT
   `members`.`idNumber` AS `idNumber`,
   `members`.`name` AS `name`,
   `members`.`cardNum` AS `cardNum`,
   `members`.`address` AS `address`
FROM `members` where `members`.`inactive` is null order by `members`.`cardNum`;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
