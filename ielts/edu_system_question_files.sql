-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: edu_system
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `question_files`
--

DROP TABLE IF EXISTS `question_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_files` (
  `id_read_quest_file` int NOT NULL AUTO_INCREMENT,
  `file_type` enum('reading','listening') NOT NULL,
  `file_title` varchar(255) DEFAULT NULL,
  `subject_id` int NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_read_quest_file`),
  KEY `idx_file_type` (`file_type`),
  KEY `idx_subject` (`subject_id`),
  CONSTRAINT `question_files_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id_subject`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_files`
--

LOCK TABLES `question_files` WRITE;
/*!40000 ALTER TABLE `question_files` DISABLE KEYS */;
INSERT INTO `question_files` VALUES (7,'reading','Variant A',3,'uploads/6956e1d6525a5_xdiak2exam1.txt','2025-05-24 15:16:09','2026-01-01 21:06:30'),(8,'listening','Variant A',3,'uploads/6956e1ca2478d_XDIAK2.mp3','2025-05-24 15:21:25','2026-01-01 21:06:18'),(46,'reading','Variant A',13,'uploads/695971458d769_xdiak3R1.txt','2025-06-29 21:21:20','2026-01-03 19:43:01'),(47,'listening','Variant A',13,'uploads/6959715240e90_2KURS-MobilePhonesandTabletComputers_VoiceText_2024.mp3','2025-06-30 01:31:30','2026-01-03 19:43:14'),(51,'reading','Variant A',11,'uploads/6952c8b212b79_readingExam1.txt','2025-12-29 18:30:10','2026-01-01 11:17:32'),(52,'listening','Variant A',11,'uploads/6952cc3b4e0a4_exam1-the-library.mp3','2025-12-29 18:45:15','2026-01-01 11:17:32'),(53,'reading','Variant B',11,'uploads/6952ce21bbe2e_readingExam2.txt','2025-12-29 18:53:21','2026-01-01 11:17:32'),(54,'listening','Variant B',11,'uploads/6956df71251de_exam2-at-the-zoo1.mp3','2025-12-29 19:12:10','2026-01-01 20:56:17'),(55,'reading','Variant C',11,'uploads/6952d6cb11903_exam3readimg.txt','2025-12-29 19:23:57','2026-01-01 11:17:32'),(57,'listening','Variant C',11,'uploads/6952db60ee649_asdafasfvaqwr12411.mp3','2025-12-29 19:49:52','2026-01-01 11:17:32'),(58,'listening','Variant D',11,'uploads/6956e23a4f35f_exam4-jet-plane1.mp3','2025-12-29 20:03:12','2026-01-01 21:08:10'),(59,'reading','Variant D',11,'uploads/6952e16ba6720_exam4reading.txt','2025-12-29 20:03:29','2026-01-01 11:17:32'),(60,'reading','Variant A',15,'uploads/6956e584bc32d_oxuverdisleriv1copy.txt','2026-01-01 21:22:12','2026-01-01 21:22:12'),(61,'reading','Variant A',15,'uploads/6956e59c9637c_oxuverdisleriv2.txt','2026-01-01 21:22:36','2026-01-01 21:22:36'),(62,'reading','Variant B',15,'uploads/6957972fb190f_oxuverdisleriv2copy.txt','2026-01-02 10:00:15','2026-01-02 10:00:15'),(63,'reading','Variant B',15,'uploads/6957973ba2194_oxuverdisleriv2R2.txt','2026-01-02 10:00:27','2026-01-02 10:00:27'),(67,'reading','Variant A',16,'uploads/69579f5d57e3b_akademinoxuverdisiv1R1.txt','2026-01-02 10:32:40','2026-01-02 10:35:09'),(68,'reading','Variant A',16,'uploads/69579f7023b99_akademikoxuverdisiV1R2.txt','2026-01-02 10:33:18','2026-01-02 10:35:28'),(69,'reading','Variant B',16,'uploads/695cb151ee660_akademinoxuverdisiv2R1.txt','2026-01-02 12:55:41','2026-01-06 06:53:05'),(70,'reading','Variant B',16,'uploads/695cb13b42d4d_akademikoxuverdisiV2R2.txt','2026-01-02 12:56:03','2026-01-06 06:52:43'),(71,'reading','Variant B',13,'uploads/69597280651ee_xdiak3R1.txt','2026-01-03 19:48:16','2026-01-03 19:48:16'),(72,'listening','Variant B',13,'uploads/6959759b15105_2KURS-MobilePhonesandTabletComputers_VoiceText_2024.mp3','2026-01-03 20:01:31','2026-01-03 20:01:31'),(73,'listening','Varinat A',17,'uploads/695caaa962b38_Bilet3-2025-12-15-14-22-122.mp3','2026-01-06 06:24:41','2026-01-06 06:24:41'),(74,'reading','Varinat A',17,'uploads/695cac2e236de_akademikoxuverdisiV2R2.txt','2026-01-06 06:31:10','2026-01-06 06:31:10'),(75,'listening','Varinat B',17,'uploads/695cb995aa7e0_Bilet2-2025-12-15-14-22-1231.mp3','2026-01-06 07:28:21','2026-01-06 07:28:30'),(76,'reading','Varinat B',17,'uploads/695cbdfac5af2_akademinoxuverdisiv2R1.txt','2026-01-06 07:47:06','2026-01-06 07:47:06'),(77,'listening','Variant C',17,'uploads/695cd27465862_Bilet4-2025-12-15-14-22-121.mp3','2026-01-06 09:14:28','2026-01-06 09:14:28'),(78,'reading','Variant C',17,'uploads/695cd42d22a23_variantC.txt','2026-01-06 09:21:16','2026-01-06 09:21:49'),(79,'listening','Variant D',17,'uploads/695cfb01d33cb_Bilet1-practice-listening-test-02-part-21.mp3','2026-01-06 12:07:29','2026-01-06 12:07:29'),(80,'reading','Variant D',17,'uploads/695cfc93a787c_variantD.txt','2026-01-06 12:14:11','2026-01-06 12:14:11'),(81,'listening','Variant A',18,'uploads/695e206f343e4_Bilet1task1.mp3','2026-01-07 08:59:27','2026-01-07 08:59:27'),(82,'listening','Variant A',18,'uploads/695e207b92c32_Bilet1task2.mp3','2026-01-07 08:59:39','2026-01-07 08:59:39'),(83,'listening','Variant B',18,'uploads/695e22fb1b310_Bilet2task1.mp3','2026-01-07 09:09:45','2026-01-07 09:10:19'),(84,'listening','Variant B',18,'uploads/695e2306b1c1f_Bilet2task2.mp3','2026-01-07 09:09:59','2026-01-07 09:10:30'),(85,'listening','Variant C',18,'uploads/695e25b3dae34_Bilet3task11.mp3','2026-01-07 09:21:55','2026-01-07 09:21:55'),(86,'listening','Variant C',18,'uploads/695e25bed36a3_Bilet3task2.mp3','2026-01-07 09:22:06','2026-01-07 09:22:06'),(87,'listening','Variant D',18,'uploads/695e2adce16d1_Bilet4task1.mp3','2026-01-07 09:43:56','2026-01-07 09:43:56'),(88,'listening','Variant D',18,'uploads/695e2af225927_Bilet4task2.mp3','2026-01-07 09:44:18','2026-01-07 09:44:18'),(89,'listening','Variant E',18,'uploads/695e4488341a6_Bilet5task1.mp3','2026-01-07 11:33:28','2026-01-07 11:33:28'),(90,'listening','Variant E',18,'uploads/695e4493af80c_Bilet5task2.mp3','2026-01-07 11:33:39','2026-01-07 11:33:39'),(91,'listening','Variant A',19,'uploads/695f5b7e1e478_Card1Test1.mp3','2026-01-08 07:23:42','2026-01-08 07:23:42'),(92,'listening','Variant A',19,'uploads/695f5d9a0ac88_Card1Test2.mp3','2026-01-08 07:32:42','2026-01-08 07:32:42'),(93,'listening','Variant B',19,'uploads/695f5eba77875_Card2Test1.mp3','2026-01-08 07:37:30','2026-01-08 07:37:30'),(94,'listening','Variant B',19,'uploads/695f6054217a6_Card2Test2.mp3','2026-01-08 07:44:20','2026-01-08 07:44:20'),(95,'listening','Variant C',19,'uploads/695f6322656a9_Card3test1.mp3','2026-01-08 07:56:18','2026-01-08 07:56:18'),(96,'listening','Variant C',19,'uploads/695f646c683fe_Card3test2.mp3','2026-01-08 08:01:48','2026-01-08 08:01:48'),(97,'listening','Variant D',19,'uploads/695f6581ebf61_Card4Test1.mp3','2026-01-08 08:06:25','2026-01-08 08:06:25'),(98,'listening','Variant D',19,'uploads/695f68cd26674_Card4Test2.mp3','2026-01-08 08:20:29','2026-01-08 08:20:29'),(99,'listening','Variant E',19,'uploads/695f6c15420c7_Card5Test1.mp3','2026-01-08 08:34:29','2026-01-08 08:34:29'),(100,'listening','Variant E',19,'uploads/695f79f177a87_Card5test2.mp3','2026-01-08 09:33:37','2026-01-08 09:33:37');
/*!40000 ALTER TABLE `question_files` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-10 16:08:16
