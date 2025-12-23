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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_files`
--

LOCK TABLES `question_files` WRITE;
/*!40000 ALTER TABLE `question_files` DISABLE KEYS */;
INSERT INTO `question_files` VALUES (7,'reading','Variant A',3,'../uploads/6831e2b927dd8_A Variant.txt','2025-05-24 15:16:09','2025-05-24 15:16:09'),(8,'listening','Variant A',3,'../uploads/6831e3f598b8e_exam 1.mp3','2025-05-24 15:21:25','2025-05-24 15:21:25'),(9,'reading','Variant B',3,'../uploads/68332b5b9cda4_B Variant.txt','2025-05-25 14:38:19','2025-05-25 14:38:19'),(10,'listening','Variant B',3,'../uploads/68332e6eaba03_exam 2.mp3','2025-05-25 14:51:26','2025-05-25 14:51:26'),(11,'reading','Variant C',3,'../uploads/683343e247094_C Variant.txt','2025-05-25 16:22:58','2025-05-25 16:22:58'),(12,'listening','Variant C',3,'../uploads/683347118c27c_exam 3.mp3','2025-05-25 16:36:33','2025-05-25 16:36:33'),(13,'reading','Variant D',3,'../uploads/6833ff35109b1_D Variant.txt','2025-05-26 05:42:13','2025-05-26 05:42:13'),(14,'listening','Variant D',3,'../uploads/6834019aa73ce_exam 4.mp3','2025-05-26 05:52:26','2025-05-26 05:52:26'),(19,'reading','Variant A',5,'../uploads/6835638eaf46c_OR Variant 1.txt','2025-05-27 07:02:38','2025-06-01 18:07:45'),(20,'reading','Variant A',5,'../uploads/683563a0c1174_OR Variant 1 metn 2 .txt','2025-05-27 07:02:56','2025-06-01 18:07:49'),(21,'reading','Variant B',5,'../uploads/683569c09f7d1_OR Variant 2.txt','2025-05-27 07:29:04','2025-06-01 18:07:56'),(22,'reading','Variant B',5,'../uploads/68356ea42e408_OR Variant 2 metn 2.txt','2025-05-27 07:49:56','2025-06-01 18:08:08'),(23,'reading','Variant A',6,'../uploads/683de4e9c31ac_variant A.txt','2025-06-02 17:52:41','2025-06-02 17:52:41'),(24,'listening','Variant A',6,'../uploads/683de75b28c24_WhatsApp Audio 2025-05-29 at 07.55.51.mp3','2025-06-02 18:03:07','2025-06-02 18:03:07'),(25,'reading','Variant A',7,'../uploads/6840388369b97_only dinleme.txt','2025-06-04 19:13:55','2025-06-04 19:13:55'),(26,'listening','Variant A',7,'../uploads/684039230b06d_dinleme-T24, T24A, T24B.mp3','2025-06-04 19:16:35','2025-06-04 19:16:35'),(27,'reading','Variant A',8,'../uploads/6840924e67086_variant A.txt','2025-06-05 01:37:02','2025-06-05 01:37:02'),(28,'listening','Variant A',8,'../uploads/6840928b0db71_dinleme-T24C, T24C, T24E (2).mp3','2025-06-05 01:38:03','2025-06-05 01:38:03'),(30,'reading','Variant A',9,'../uploads/6845e5191770b_A - reading.txt','2025-06-09 02:31:37','2025-06-09 02:31:37'),(31,'listening','Variant A',9,'../uploads/6845e79dbed21_a - listening.mp3','2025-06-09 02:42:21','2025-06-09 02:42:21'),(32,'reading','Variant B',9,'../uploads/6845e9b0ebb40_B - reading.txt','2025-06-09 02:51:12','2025-06-09 02:51:12'),(33,'listening','Variant B',9,'../uploads/6845eca2d134d_b - listening.mp3','2025-06-09 03:03:46','2025-06-09 03:03:46'),(40,'reading','Variant A',11,'../uploads/68601ca81179c_xidiak 1 var1.txt','2025-06-28 23:47:36','2025-06-28 23:47:36'),(41,'listening','Variant A',11,'../uploads/68601e75c3881_WhatsApp Audio 2025-06-28 at 20.06.46.mp3','2025-06-28 23:55:17','2025-06-28 23:55:17'),(46,'reading','Variant A',13,'../uploads/68614be0b753e_EXAM -IT -2 KURS (2).txt','2025-06-29 21:21:20','2025-06-29 21:21:20'),(47,'listening','Variant A',13,'../uploads/68618682ee461_WhatsApp-Audio-2025-06-28-at-20.42.46-_3_.mp3','2025-06-30 01:31:30','2025-06-30 01:31:30'),(48,'listening','Variant A',14,'../uploads/686239bc1900b_WhatsApp-Audio-2025-06-30-at-10.54.03.mp3','2025-06-30 14:16:12','2025-06-30 14:16:12'),(49,'reading','Variant A',14,'../uploads/686239ee4fc30_variant_A.txt','2025-06-30 14:17:02','2025-06-30 14:17:02');
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

-- Dump completed on 2025-12-16 13:53:40
