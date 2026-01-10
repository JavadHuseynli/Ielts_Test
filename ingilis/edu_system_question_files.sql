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
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_files`
--

LOCK TABLES `question_files` WRITE;
/*!40000 ALTER TABLE `question_files` DISABLE KEYS */;
INSERT INTO `question_files` VALUES (7,'reading','Variant A',3,'uploads/6956e1d6525a5_xdiak2exam1.txt','2025-05-24 15:16:09','2026-01-01 21:06:30'),(8,'listening','Variant A',3,'uploads/6956e1ca2478d_XDIAK2.mp3','2025-05-24 15:21:25','2026-01-01 21:06:18'),(30,'reading','Variant A',9,'uploads/6845e5191770b_A - reading.txt','2025-06-09 02:31:37','2025-12-19 08:23:40'),(31,'listening','Variant A',9,'uploads/6845e79dbed21_a - listening.mp3','2025-06-09 02:42:21','2025-12-19 08:23:40'),(32,'reading','Variant B',9,'uploads/6845e9b0ebb40_B - reading.txt','2025-06-09 02:51:12','2025-12-19 08:23:40'),(33,'listening','Variant B',9,'uploads/6845eca2d134d_b - listening.mp3','2025-06-09 03:03:46','2025-12-19 08:23:40'),(46,'reading','Variant A',13,'uploads/695971458d769_xdiak3R1.txt','2025-06-29 21:21:20','2026-01-03 19:43:01'),(47,'listening','Variant A',13,'uploads/6959715240e90_2KURS-MobilePhonesandTabletComputers_VoiceText_2024.mp3','2025-06-30 01:31:30','2026-01-03 19:43:14'),(51,'reading','Variant A',11,'uploads/6952c8b212b79_readingExam1.txt','2025-12-29 18:30:10','2026-01-01 11:17:32'),(52,'listening','Variant A',11,'uploads/6952cc3b4e0a4_exam1-the-library.mp3','2025-12-29 18:45:15','2026-01-01 11:17:32'),(53,'reading','Variant B',11,'uploads/6952ce21bbe2e_readingExam2.txt','2025-12-29 18:53:21','2026-01-01 11:17:32'),(54,'listening','Variant B',11,'uploads/6956df71251de_exam2-at-the-zoo1.mp3','2025-12-29 19:12:10','2026-01-01 20:56:17'),(55,'reading','Variant C',11,'uploads/6952d6cb11903_exam3readimg.txt','2025-12-29 19:23:57','2026-01-01 11:17:32'),(57,'listening','Variant C',11,'uploads/6952db60ee649_asdafasfvaqwr12411.mp3','2025-12-29 19:49:52','2026-01-01 11:17:32'),(58,'listening','Variant D',11,'uploads/6956e23a4f35f_exam4-jet-plane1.mp3','2025-12-29 20:03:12','2026-01-01 21:08:10'),(59,'reading','Variant D',11,'uploads/6952e16ba6720_exam4reading.txt','2025-12-29 20:03:29','2026-01-01 11:17:32'),(60,'reading','Variant A',15,'uploads/6956e584bc32d_oxuverdisleriv1copy.txt','2026-01-01 21:22:12','2026-01-01 21:22:12'),(61,'reading','Variant A',15,'uploads/6956e59c9637c_oxuverdisleriv2.txt','2026-01-01 21:22:36','2026-01-01 21:22:36'),(62,'reading','Variant B',15,'uploads/6957972fb190f_oxuverdisleriv2copy.txt','2026-01-02 10:00:15','2026-01-02 10:00:15'),(63,'reading','Variant B',15,'uploads/6957973ba2194_oxuverdisleriv2R2.txt','2026-01-02 10:00:27','2026-01-02 10:00:27'),(67,'reading','Variant A',16,'uploads/69579f5d57e3b_akademinoxuverdisiv1R1.txt','2026-01-02 10:32:40','2026-01-02 10:35:09'),(68,'reading','Variant A',16,'uploads/69579f7023b99_akademikoxuverdisiV1R2.txt','2026-01-02 10:33:18','2026-01-02 10:35:28'),(69,'reading','Variant B',16,'uploads/695cb151ee660_akademinoxuverdisiv2R1.txt','2026-01-02 12:55:41','2026-01-06 06:53:05'),(70,'reading','Variant B',16,'uploads/695cb13b42d4d_akademikoxuverdisiV2R2.txt','2026-01-02 12:56:03','2026-01-06 06:52:43'),(71,'reading','Variant B',13,'uploads/69597280651ee_xdiak3R1.txt','2026-01-03 19:48:16','2026-01-03 19:48:16'),(72,'listening','Variant B',13,'uploads/6959759b15105_2KURS-MobilePhonesandTabletComputers_VoiceText_2024.mp3','2026-01-03 20:01:31','2026-01-03 20:01:31'),(73,'listening','Varinat A',17,'uploads/695caaa962b38_Bilet3-2025-12-15-14-22-122.mp3','2026-01-06 06:24:41','2026-01-06 06:24:41'),(74,'reading','Varinat A',17,'uploads/695cac2e236de_akademikoxuverdisiV2R2.txt','2026-01-06 06:31:10','2026-01-06 06:31:10'),(75,'listening','Varinat B',17,'uploads/695cb995aa7e0_Bilet2-2025-12-15-14-22-1231.mp3','2026-01-06 07:28:21','2026-01-06 07:28:30'),(76,'reading','Varinat B',17,'uploads/695cbdfac5af2_akademinoxuverdisiv2R1.txt','2026-01-06 07:47:06','2026-01-06 07:47:06'),(77,'listening','Variant C',17,'uploads/695cd27465862_Bilet4-2025-12-15-14-22-121.mp3','2026-01-06 09:14:28','2026-01-06 09:14:28'),(78,'reading','Variant C',17,'uploads/695cd42d22a23_variantC.txt','2026-01-06 09:21:16','2026-01-06 09:21:49'),(79,'listening','Variant D',17,'uploads/695cfb01d33cb_Bilet1-practice-listening-test-02-part-21.mp3','2026-01-06 12:07:29','2026-01-06 12:07:29'),(80,'reading','Variant D',17,'uploads/695cfc93a787c_variantD.txt','2026-01-06 12:14:11','2026-01-06 12:14:11');
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

-- Dump completed on 2026-01-06 19:04:56
