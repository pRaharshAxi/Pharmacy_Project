-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 17, 2026 at 07:02 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medcare`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `add_customer`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_customer` (IN `p_user_id` VARCHAR(10), IN `p_age` INT, IN `p_country` CHAR(50), IN `p_state` CHAR(50), IN `p_city` CHAR(50), IN `p_street` CHAR(50), IN `p_gender` ENUM('MALE','FEMALE'), IN `p_year` INT, IN `p_month` INT, IN `p_date` INT, IN `p_med_id` VARCHAR(10), IN `p_duration` CHAR(50))   BEGIN
    INSERT INTO customer
    VALUES (p_user_id, p_age, p_country, p_state, p_city,
            p_street, p_gender, p_year, p_month, p_date,
            p_med_id, p_duration);
END$$

DROP PROCEDURE IF EXISTS `add_medicine`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_medicine` (IN `p_id` VARCHAR(10), IN `p_name` CHAR(50), IN `p_category` ENUM('Pain and Fever','Antibiotics','Chronic Care','General Wellness'), IN `p_price` FLOAT, IN `p_stock` INT, IN `p_dosage` CHAR(50), IN `p_exp` CHAR(50))   BEGIN
    INSERT INTO medicine
    VALUES (p_id, p_name, p_category, p_price, p_stock, p_dosage, p_exp);
END$$

DROP PROCEDURE IF EXISTS `add_order_item`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_order_item` (IN `p_item_id` INT, IN `p_order_id` VARCHAR(10), IN `p_med_id` VARCHAR(10), IN `p_qty` INT, IN `p_price` FLOAT)   BEGIN
    INSERT INTO order_item
    VALUES (p_item_id, p_order_id, p_med_id, p_qty, p_price);

    UPDATE medicine
    SET STOCK_QUANTITY = STOCK_QUANTITY - p_qty
    WHERE MEDICINE_ID = p_med_id;
END$$

DROP PROCEDURE IF EXISTS `add_purchase_item`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_purchase_item` (IN `p_item_id` INT, IN `p_purchase_id` VARCHAR(10), IN `p_qty` INT, IN `p_med_id` VARCHAR(10), IN `p_cost` FLOAT)   BEGIN
    INSERT INTO purchase_item
    VALUES (p_item_id, p_purchase_id, p_qty, p_med_id, p_cost);

    UPDATE medicine
    SET STOCK_QUANTITY = STOCK_QUANTITY + p_qty
    WHERE MEDICINE_ID = p_med_id;
END$$

DROP PROCEDURE IF EXISTS `add_user`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_user` (IN `p_user_id` VARCHAR(10), IN `p_fname` CHAR(50), IN `p_lname` CHAR(50), IN `p_email` VARCHAR(100), IN `p_contact` VARCHAR(15), IN `p_role` ENUM('PHARMACIST','ADMIN','CUSTOMER'), IN `p_password` VARCHAR(10))   BEGIN
    INSERT INTO users
    VALUES (p_user_id, p_fname, p_lname, p_email, p_contact, p_role, p_password);
END$$

DROP PROCEDURE IF EXISTS `create_invoice`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `create_invoice` (IN `p_invoice_id` VARCHAR(10), IN `p_method` CHAR(50), IN `p_date` DATE, IN `p_order_id` VARCHAR(50), IN `p_total` FLOAT, IN `p_pharmacist` VARCHAR(10), IN `p_customer` VARCHAR(10))   BEGIN
    INSERT INTO invoice
    VALUES (p_invoice_id, p_method, p_date,
            p_order_id, p_total, p_pharmacist, p_customer);
END$$

DROP PROCEDURE IF EXISTS `create_order`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `create_order` (IN `p_order_id` VARCHAR(10), IN `p_customer_id` VARCHAR(10), IN `p_date` DATE, IN `p_total` FLOAT, IN `p_status` ENUM('PENDING','PROCESSING','COMPLETED','CANCEL'))   BEGIN
    INSERT INTO orders
    VALUES (p_order_id, p_customer_id, p_date, p_total, p_status);
END$$

DROP PROCEDURE IF EXISTS `create_purchase`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `create_purchase` (IN `p_id` VARCHAR(10), IN `p_date` DATE, IN `p_supplier` VARCHAR(10), IN `p_pharmacist` VARCHAR(10), IN `p_total` FLOAT)   BEGIN
    INSERT INTO purchase
    VALUES (p_id, p_date, p_supplier, p_pharmacist, p_total);
END$$

DROP PROCEDURE IF EXISTS `get_all_medicines`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_all_medicines` ()   BEGIN
    SELECT * FROM medicine;
END$$

DROP PROCEDURE IF EXISTS `get_customer_details`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_customer_details` (IN `p_customer_id` VARCHAR(10))   BEGIN
    SELECT u.F_NAME, u.L_NAME, c.*
    FROM customer c
    JOIN users u ON u.USER_ID = c.USER_ID
    WHERE c.USER_ID = p_customer_id;
END$$

DROP PROCEDURE IF EXISTS `get_supplier_contacts`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_supplier_contacts` ()   BEGIN
    SELECT s.COMPANY_NAME, c.MAIN_NUM, c.OPTIONAL_NUM
    FROM supplier s
    JOIN supplier_contactnum c
    ON s.SUPPLIER_ID = c.SUPPLIER_ID;
END$$

DROP PROCEDURE IF EXISTS `login_user`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `login_user` (IN `p_email` VARCHAR(100), IN `p_password` VARCHAR(10))   BEGIN
    SELECT USER_ID, ROLE
    FROM users
    WHERE EMAIL = p_email AND PASSWORD = p_password;
END$$

DROP PROCEDURE IF EXISTS `update_medicine_stock`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_medicine_stock` (IN `p_med_id` VARCHAR(10), IN `p_qty` INT)   BEGIN
    UPDATE medicine
    SET STOCK_QUANTITY = STOCK_QUANTITY + p_qty
    WHERE MEDICINE_ID = p_med_id;
END$$

DROP PROCEDURE IF EXISTS `update_order_status`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `update_order_status` (IN `p_order_id` VARCHAR(10), IN `p_status` ENUM('PENDING','PROCESSING','COMPLETED','CANCEL'))   BEGIN
    UPDATE orders
    SET STATUS = p_status
    WHERE ORDER_ID = p_order_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `USER_ID` varchar(10) NOT NULL,
  `ADMIN_LEVEL` int NOT NULL,
  KEY `fk_ADMIN_USERS` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`USER_ID`, `ADMIN_LEVEL`) VALUES
('u002', 1),
('u004', 2);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
CREATE TABLE IF NOT EXISTS `customer` (
  `USER_ID` varchar(10) NOT NULL,
  `AGE` int NOT NULL,
  `COUNTRY` char(50) NOT NULL,
  `STATE` char(50) NOT NULL,
  `CITY` char(50) NOT NULL,
  `STREET` char(50) NOT NULL,
  `GENDER` enum('MALE','FEMALE') DEFAULT NULL,
  `B_YEAR` int NOT NULL,
  `B_MONTH` int NOT NULL,
  `B_DATE` int NOT NULL,
  KEY `fk_CUSTOMER_USERS` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`USER_ID`, `AGE`, `COUNTRY`, `STATE`, `CITY`, `STREET`, `GENDER`, `B_YEAR`, `B_MONTH`, `B_DATE`) VALUES
('u003', 22, 'Sri Lanka', 'Colombo', 'Awissawella', 'Main Road', 'MALE', 2003, 9, 25),
('U8531', 35, 'Sri Lanka', 'Western', 'Colombo', 'Maradana Road', 'MALE', 1990, 8, 12),
('U2875', 24, 'Sri Lanka', 'Western', 'Colombo', '20/A, Colombo', 'FEMALE', 2001, 11, 14),
('U7097', 22, 'Sri Lanka', 'Western', 'Narahenpita', 'Chithra lane', 'MALE', 2003, 12, 10);

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

DROP TABLE IF EXISTS `invoice`;
CREATE TABLE IF NOT EXISTS `invoice` (
  `INVOICE_ID` varchar(10) NOT NULL,
  `PAYMENT_METHOD` char(50) NOT NULL,
  `INVOICE_DATE` date NOT NULL,
  `ORDER_ID` varchar(50) NOT NULL,
  `TOTAL_AMOUNT` float NOT NULL,
  `PHARMACIST_ID` varchar(10) NOT NULL,
  `CUSTOMER_ID` varchar(10) NOT NULL,
  PRIMARY KEY (`INVOICE_ID`),
  KEY `fk_ORDER_ID_ORDERS` (`ORDER_ID`),
  KEY `fk_PHARMACIST_ID_PHARMACIST` (`PHARMACIST_ID`),
  KEY `fk_CUSTOMER_ID_CUSTOMER` (`CUSTOMER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `invoice`
--

INSERT INTO `invoice` (`INVOICE_ID`, `PAYMENT_METHOD`, `INVOICE_DATE`, `ORDER_ID`, `TOTAL_AMOUNT`, `PHARMACIST_ID`, `CUSTOMER_ID`) VALUES
('1', 'CASH ON DELIVERY', '2026-01-02', '1', 100, 'u001', 'u003'),
('INV69B8F13', 'CASH ON DELIVERY', '2026-03-17', '0', 82, 'u001', 'U2875');

-- --------------------------------------------------------

--
-- Table structure for table `medicine`
--

DROP TABLE IF EXISTS `medicine`;
CREATE TABLE IF NOT EXISTS `medicine` (
  `MEDICINE_ID` varchar(10) NOT NULL,
  `NAME` char(50) NOT NULL,
  `CATEGORY` enum('Pain and Fever','Antibiotics','Chronic Care','General Wellness') NOT NULL,
  `Price` float NOT NULL,
  `STOCK_QUANTITY` int NOT NULL,
  `DOSAGE` char(50) NOT NULL,
  `EXP_DURATION` char(50) NOT NULL,
  PRIMARY KEY (`MEDICINE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medicine`
--

INSERT INTO `medicine` (`MEDICINE_ID`, `NAME`, `CATEGORY`, `Price`, `STOCK_QUANTITY`, `DOSAGE`, `EXP_DURATION`) VALUES
('M001', 'Paracetamol', 'Pain and Fever', 3, 34, '4 per day', '2-4 years'),
('M003', 'Ibuprofen', 'Pain and Fever', 15, 29, '3 per day', '2-3 years'),
('M006', 'Aspirin', 'Pain and Fever', 6, 125, '1-2 per day', '2-4 years'),
('M002', 'Amoxicillin', 'Antibiotics', 12, 38, '3 per day', '2-3 years'),
('M004', 'Cetirizine', 'Antibiotics', 10, 48, '1 per day', '2-3 years'),
('M008', 'Azithromycin', 'Antibiotics', 35, 46, '1 per day', '2-3 years'),
('M005', 'Metformin', 'Chronic Care', 8, 0, '2 per day', '2-3 years'),
('M007', 'Omeprazole', 'Chronic Care', 18, 0, '1 per day', '2-3 years'),
('M009', 'Vitamin C', 'General Wellness', 5, 0, '1 per day', '2-3 years'),
('M010', 'Cough Syrup', 'General Wellness', 120, 0, '3 times per day', '1-2 years'),
('M011', 'Vitamin D', 'General Wellness', 8.5, 103, '1 per day', '2-3 years'),
('M018', 'Check01', 'Antibiotics', 0.03, 31, '2', '2-3 years');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `ORDER_ID` varchar(10) NOT NULL,
  `CUSTOMER_ID` varchar(10) NOT NULL,
  `ORDER_DATE` date NOT NULL,
  `TOTAL_AMOUNT` float NOT NULL,
  `STATUS` enum('PENDING','PROCESSING','COMPLETED','CANCEL') NOT NULL,
  `PRESCRIPTION_PATH` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ORDER_ID`),
  KEY `fk_CUSTOMER_ID_CUSTOMER` (`CUSTOMER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`ORDER_ID`, `CUSTOMER_ID`, `ORDER_DATE`, `TOTAL_AMOUNT`, `STATUS`, `PRESCRIPTION_PATH`) VALUES
('1', 'u003', '2026-01-02', 100, 'COMPLETED', NULL),
('2', 'u003', '2026-01-11', 50, 'PROCESSING', NULL),
('3', 'u006', '2026-01-31', 12, 'PENDING', 'uploads/prescriptions/1769839318_12d348b9519c.png'),
('ORD0004', 'u003', '2026-02-01', 120, 'PENDING', NULL),
('ORD0005', 'u003', '2026-02-01', 35, 'PENDING', NULL),
('ORD0006', 'u003', '2026-02-01', 10, 'PENDING', NULL),
('ORD0007', 'u003', '2026-02-01', 45, 'PENDING', NULL),
('ORD0008', 'u003', '2026-02-01', 300, 'PENDING', NULL),
('ORD0009', 'u006', '2026-02-01', 35, 'PENDING', '../uploads/prescriptions/u006_1769979279.png'),
('ORD0010', 'u006', '2026-03-17', 60, 'PENDING', '../uploads/prescriptions/u006_1773725276.jpg'),
('ORD0011', 'U2875', '2026-03-17', 82, 'PROCESSING', '../uploads/prescriptions/U2875_1773728004.jpg'),
('ORD0012', 'U7097', '2026-03-17', 40, 'PENDING', '../uploads/prescriptions/U7097_1773729296.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

DROP TABLE IF EXISTS `order_item`;
CREATE TABLE IF NOT EXISTS `order_item` (
  `ORDER_ITEM_ID` int NOT NULL,
  `ORDER_ID` varchar(10) NOT NULL,
  `MEDICINE_ID` varchar(10) NOT NULL,
  `QUANTITY` int NOT NULL,
  `PRICE` float NOT NULL,
  PRIMARY KEY (`ORDER_ITEM_ID`),
  KEY `fk_ORDER_ID_ORDERS` (`ORDER_ID`),
  KEY `fk_MEDICINE_ID_MEDICINE` (`MEDICINE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`ORDER_ITEM_ID`, `ORDER_ID`, `MEDICINE_ID`, `QUANTITY`, `PRICE`) VALUES
(1, '1', 'm004', 10, 100),
(3, '2', 'm001', 1, 3),
(4, '3', 'M002', 1, 12),
(5, 'ORD0004', 'M002', 10, 12),
(6, 'ORD0005', 'M008', 1, 35),
(7, 'ORD0006', 'M004', 1, 10),
(8, 'ORD0007', 'M001', 15, 3),
(9, 'ORD0008', 'M003', 20, 15),
(10, 'ORD0009', 'M008', 1, 35),
(11, 'ORD0010', 'M002', 5, 12),
(12, 'ORD0011', 'M006', 2, 6),
(13, 'ORD0011', 'M008', 2, 35),
(14, 'ORD0012', 'M002', 1, 12),
(15, 'ORD0012', 'M004', 1, 10),
(16, 'ORD0012', 'M001', 1, 3),
(17, 'ORD0012', 'M003', 1, 15);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist`
--

DROP TABLE IF EXISTS `pharmacist`;
CREATE TABLE IF NOT EXISTS `pharmacist` (
  `USER_ID` varchar(10) NOT NULL,
  `YEARS_OF_EXP` int NOT NULL,
  `LICENSE_NO` varchar(10) NOT NULL,
  KEY `fk_PHARMACIST_USERS` (`USER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pharmacist`
--

INSERT INTO `pharmacist` (`USER_ID`, `YEARS_OF_EXP`, `LICENSE_NO`) VALUES
('u001', 2, 'ABC123'),
('U6741', 3, 'PH12345'),
('U9064', 5, 'PH123458');

-- --------------------------------------------------------

--
-- Table structure for table `pharmacist_purchaseitem`
--

DROP TABLE IF EXISTS `pharmacist_purchaseitem`;
CREATE TABLE IF NOT EXISTS `pharmacist_purchaseitem` (
  `PHARMACIST_ID` varchar(10) NOT NULL,
  `PURCHASE_ITEM_ID` int NOT NULL,
  PRIMARY KEY (`PHARMACIST_ID`,`PURCHASE_ITEM_ID`),
  KEY `fk_PURCHASE_ITEM_ID_PURCHASE_ITEM` (`PURCHASE_ITEM_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pharmacist_purchaseitem`
--

INSERT INTO `pharmacist_purchaseitem` (`PHARMACIST_ID`, `PURCHASE_ITEM_ID`) VALUES
('U001', 1),
('u001', 7),
('u001', 8),
('u001', 9),
('u001', 14),
('U9064', 10),
('U9064', 11),
('U9064', 12),
('U9064', 13);

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

DROP TABLE IF EXISTS `purchase`;
CREATE TABLE IF NOT EXISTS `purchase` (
  `PURCHASE_ID` varchar(10) NOT NULL,
  `PURCHASE_DATE` date NOT NULL,
  `SUPPLIER_ID` varchar(10) NOT NULL,
  `PHARMACIST_ID` varchar(10) NOT NULL,
  `TOTAL_AMOUNT` float NOT NULL,
  PRIMARY KEY (`PURCHASE_ID`),
  KEY `fk_SUPPLIER_ID_SUPPLIER` (`SUPPLIER_ID`),
  KEY `fk_PHARMACIST_ID_PHARMACIST` (`PHARMACIST_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`PURCHASE_ID`, `PURCHASE_DATE`, `SUPPLIER_ID`, `PHARMACIST_ID`, `TOTAL_AMOUNT`) VALUES
('1', '2026-01-01', 's001', 'u001', 2000),
('2', '2026-01-31', 'SUP001', 'u001', 0.08),
('3', '2026-01-31', 'SUP001', 'u001', 43.05),
('PUR0001', '2026-02-02', 'SUP001', 'u001', 0.3),
('PUR0002', '2026-02-02', 'SUP001', 'u001', 0.3),
('PUR0003', '2026-02-02', 'SUP001', 'u001', 0.3),
('PUR0004', '2026-03-17', 'SUP001', 'U9064', 150),
('PUR0005', '2026-03-17', 'SUP001', 'U9064', 150),
('PUR0006', '2026-03-17', 'SUP001', 'U9064', 150),
('PUR0007', '2026-03-17', 'SUP001', 'U9064', 6),
('PUR0008', '2026-03-17', 'SUP003', 'u001', 12);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_item`
--

DROP TABLE IF EXISTS `purchase_item`;
CREATE TABLE IF NOT EXISTS `purchase_item` (
  `PURCHASE_ITEM_ID` int NOT NULL,
  `PURCHASE_ID` varchar(10) NOT NULL,
  `QUANTITY` int NOT NULL,
  `MEDICINE_ID` varchar(10) NOT NULL,
  `UNIT_COST` float NOT NULL,
  PRIMARY KEY (`PURCHASE_ITEM_ID`),
  KEY `fk_PURCHASE_ID_PURCHASE` (`PURCHASE_ID`),
  KEY `fk_MEDICINE_ID_MEDICINE` (`MEDICINE_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_item`
--

INSERT INTO `purchase_item` (`PURCHASE_ITEM_ID`, `PURCHASE_ID`, `QUANTITY`, `MEDICINE_ID`, `UNIT_COST`) VALUES
(1, '1', 20, 'm008', 35),
(2, '1', 10, 'm010', 120),
(3, '2', 1, 'M002', 0.05),
(4, '2', 1, 'M006', 0.03),
(5, '3', 3, 'M002', 8.4),
(6, '3', 3, 'M011', 5.95),
(7, 'PUR0001', 10, 'M018', 0.03),
(8, 'PUR0002', 10, 'M018', 0.03),
(9, 'PUR0003', 10, 'M018', 0.03),
(10, 'PUR0004', 25, 'M006', 6),
(11, 'PUR0005', 25, 'M006', 6),
(12, 'PUR0006', 25, 'M006', 6),
(13, 'PUR0007', 1, 'M006', 6),
(14, 'PUR0008', 1, 'M002', 12);

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

DROP TABLE IF EXISTS `supplier`;
CREATE TABLE IF NOT EXISTS `supplier` (
  `SUPPLIER_ID` varchar(10) NOT NULL,
  `COMPANY_NAME` char(50) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `ZIP_CODE` varchar(50) NOT NULL,
  `CITY` char(50) NOT NULL,
  `STREET` char(50) NOT NULL,
  `STATE` char(50) NOT NULL,
  `COUNTRY` char(50) NOT NULL,
  PRIMARY KEY (`SUPPLIER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`SUPPLIER_ID`, `COMPANY_NAME`, `EMAIL`, `ZIP_CODE`, `CITY`, `STREET`, `STATE`, `COUNTRY`) VALUES
('SUP001', 'ABC Medical Suppliers', 'contact@abcmedical.com', '10250', 'Colombo', 'Main Street', 'Western', 'Sri Lanka'),
('SUP002', 'HealthPlus Distributors', 'info@healthplus.lk', '20000', 'Kandy', 'Peradeniya Rd', 'Central', 'Sri Lanka'),
('SUP003', 'LifeCare Pharma', 'sales@lifecare.lk', '40000', 'Jaffna', 'Hospital Rd', 'Northern', 'Sri Lanka'),
('SUP321', 'check 01', 'check01@gmail.com', '562', 'Matara', 'street', 'Southern Province', 'Sri Lanka'),
('SUP329', 'check 01', 'check01@gmail.com', '562', 'Matara', 'street', 'Southern Province', 'Sri Lanka');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_contactnum`
--

DROP TABLE IF EXISTS `supplier_contactnum`;
CREATE TABLE IF NOT EXISTS `supplier_contactnum` (
  `SUPPLIER_ID` varchar(10) NOT NULL,
  `MAIN_NUM` varchar(50) NOT NULL,
  `OPTIONAL_NUM` varchar(50) NOT NULL,
  PRIMARY KEY (`SUPPLIER_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplier_contactnum`
--

INSERT INTO `supplier_contactnum` (`SUPPLIER_ID`, `MAIN_NUM`, `OPTIONAL_NUM`) VALUES
('SUP001', '0771234567', '0719876543'),
('SUP002', '0765558899', ''),
('SUP003', '0701122334', '0729988776'),
('SUP321', '0715678910', '0123547869'),
('SUP329', '0715678910', '0123547869');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_pharmacist`
--

DROP TABLE IF EXISTS `supplier_pharmacist`;
CREATE TABLE IF NOT EXISTS `supplier_pharmacist` (
  `SUPPLIER_ID` varchar(10) NOT NULL,
  `PHARMACIST_ID` varchar(10) NOT NULL,
  PRIMARY KEY (`SUPPLIER_ID`,`PHARMACIST_ID`),
  KEY `fk_PHARMACIST_ID_PHARMACIST` (`PHARMACIST_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `supplier_pharmacist`
--

INSERT INTO `supplier_pharmacist` (`SUPPLIER_ID`, `PHARMACIST_ID`) VALUES
('SUP001', 'PH001'),
('SUP001', 'u001'),
('SUP002', 'PH001'),
('SUP003', 'PH002');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `USER_ID` varchar(10) NOT NULL,
  `F_NAME` char(50) NOT NULL,
  `L_NAME` char(50) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `CONTACT_NUM` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ROLE` enum('PHARMACIST','ADMIN','CUSTOMER') DEFAULT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  PRIMARY KEY (`USER_ID`),
  UNIQUE KEY `EMAIL` (`EMAIL`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`USER_ID`, `F_NAME`, `L_NAME`, `EMAIL`, `CONTACT_NUM`, `ROLE`, `PASSWORD`) VALUES
('u001', 'Charutha', 'Palihawadana', 'charutha@gmail.com', '0725746789', 'PHARMACIST', '1234'),
('u002', 'Kamal', 'Gunasena', 'kamal@gmail.com', '0725746789', 'ADMIN', 'abcd'),
('u003', 'Gamitha', 'Thakshana', 'gamitha@gmail.com', '0771234467', 'CUSTOMER', '4784'),
('u004', 'Dimasha', 'Jayathilaka', 'dimasha@gmail.com', '0774234467', 'ADMIN', '7896'),
('u005', 'Chamod', 'Devranga', 'chamod@gmail.com', '0774234487', 'PHARMACIST', '1237'),
('u006', 'Gagan', 'Kavishka', 'gagn@gmail.com', '0771234469', 'CUSTOMER', '4884'),
('U1595', 'Malith', 'Thenuka', 'malith@gmail.com', '078899663', 'ADMIN', '$2y$10$7WC'),
('U2529', 'Poorni', 'Aishwarya', 'poorniaishwar@gmail.com', '0779289388', 'PHARMACIST', '$2y$10$4pF'),
('U8239', 'Aruth', 'Silva', 'aruth@gmail.com', '0708524569', 'PHARMACIST', '$2y$10$vBI'),
('U0443', 'ab', 'cd', 'abcd@gmail.com', '0708524568', 'CUSTOMER', '$2y$10$1ncouW7/.QtIYEZfpFjl6eQHoU1a2OfEr1BBAmp31Vum52DQScQgC'),
('U1239', 'hasini', 'susa', 'hasi@gmail.com', '0708524569', 'PHARMACIST', '$2y$10$9VB0dPR4vQ2/gpF.I4IJI.zSFmxoEV4T2l9uWnKyDIveriRvcos3W'),
('u007', 'John', 'Doe', 'john@gmail.com', '0771234567', 'CUSTOMER', 'pass123'),
('U2875', 'Shav', 'Edirisinghe', 'edirisingheshavini532@gmail.com', '0760912477', 'CUSTOMER', '$2y$10$E8AcyQkU7T6sGu4z6/nJmOoTnBTw/Yc44bwyBUnAvcV7wbkTPq2Y6'),
('U8531', 'Dananjaya', 'De Silva', 'dana@gmail.com', '0114257288', 'CUSTOMER', '$2y$10$qYMDA96e77weH6ldcMJnJ.Fxvb/uhYjc8cmZaNNIkPpjeE2.2Qonm'),
('U6741', 'Kusal', 'Mendis', 'kusal@gmail.com', '0114857899', 'PHARMACIST', '$2y$10$S5igif/rxxs0BQCk9jQ9Uurnnqe2IIuvZ9M4afZl3vGeJEBARve/O'),
('U9064', 'Waruni', 'Hewage', 'edirisinghewaruni590@gmail.com', '011784555', 'PHARMACIST', '$2y$10$OFpHpVJu65jk0jCK4KDNAetsqnimet98PLOVOqPcDrR.MafIzu8jS'),
('U7097', 'Jason', 'Fernando', 'jasonfdo1012@gmail.com', '0761023988', 'CUSTOMER', '$2y$10$iWrLQUUgDX5dVrdh9cWsAea/gcnJ5Ygf9g5WybXq.Agm9k3kqbK9G');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
