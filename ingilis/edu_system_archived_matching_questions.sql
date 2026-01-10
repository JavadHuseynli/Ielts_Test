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
-- Table structure for table `archived_matching_questions`
--

DROP TABLE IF EXISTS `archived_matching_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archived_matching_questions` (
  `id_archived_matching` int NOT NULL AUTO_INCREMENT,
  `id_archived_question` int NOT NULL,
  `variants` text NOT NULL,
  `corr_variant` text NOT NULL,
  PRIMARY KEY (`id_archived_matching`),
  KEY `idx_archived_question` (`id_archived_question`),
  CONSTRAINT `archived_matching_questions_ibfk_1` FOREIGN KEY (`id_archived_question`) REFERENCES `archived_questions` (`id_archived_question`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archived_matching_questions`
--

LOCK TABLES `archived_matching_questions` WRITE;
/*!40000 ALTER TABLE `archived_matching_questions` DISABLE KEYS */;
INSERT INTO `archived_matching_questions` VALUES (1,6,'True,False','True'),(2,7,'True,False','False'),(3,8,'True,False','True'),(4,9,'True,False','False'),(5,10,'True,False','True'),(6,16,'True,False','False'),(7,17,'True,False','True'),(8,18,'True,False','False'),(9,19,'True,False','False'),(10,20,'True,False','True'),(11,26,'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep','Why Sleep Is Important'),(12,27,'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep','Dangers of Not Sleeping'),(13,28,'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep','How Randy Recovered After the Experiment'),(14,29,'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy&#39;s Sleep Experiment, What Happened to Randy Without Sleep','What Happened to Randy Without Sleep'),(15,30,'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep','Randy\'s Sleep Experiment'),(16,31,'True,False','True'),(17,32,'True,False','False'),(18,33,'True,False','False'),(19,34,'True,False','True'),(20,35,'True,False','False'),(21,36,'environment,private,online,science,opportunities','environment'),(22,37,'environment,private,online,science,opportunities','science'),(23,38,'environment,private,online,science,opportunities','private'),(24,39,'environment,private,online,science,opportunities','online'),(25,40,'environment,private,online,science,opportunities','opportunities'),(26,41,'True,False','True'),(27,42,'True,False','False'),(28,43,'True,False','True'),(29,44,'True,False','False'),(30,45,'True,False','True'),(31,56,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','e-zine'),(32,57,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','currency converter'),(33,58,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','instant messaging'),(34,59,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','portal'),(35,60,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','webmail'),(36,61,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','online radio'),(37,62,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','online music store'),(38,63,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','online encyclopedia'),(39,64,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','online casino'),(40,65,'webmail, blog, currency converter, online radio, online encyclopedia, e-zine, instant messaging, online casino, portal','blog'),(41,86,'about the history of the Corona virus?, always makes me think of summer. , When it rained, water came in through the windows and I practically got wet, This one is much easier to use. , because the dishes were small and the food wasn’t tasty.','This one is much easier to use.'),(42,87,'about the history of the Corona virus?, always makes me think of summer. , When it rained, water came in through the windows and I practically got wet, This one is much easier to use. , because the dishes were small and the food wasn’t tasty.','When it rained, water came in through the windows and I practically got wet'),(43,88,'about the history of the Corona virus?, always makes me think of summer. , When it rained, water came in through the windows and I practically got wet, This one is much easier to use. , because the dishes were small and the food wasn’t tasty.','because the dishes were small and the food wasn’t tasty.'),(44,89,'about the history of the Corona virus?, always makes me think of summer. , When it rained, water came in through the windows and I practically got wet, This one is much easier to use. , because the dishes were small and the food wasn’t tasty.','about the history of the Corona virus?'),(45,90,'about the history of the Corona virus?, always makes me think of summer. , When it rained, water came in through the windows and I practically got wet, This one is much easier to use. , because the dishes were small and the food wasn’t tasty.','always makes me think of summer.'),(46,91,'Confident ,landmark, lost touch, interview,reliable','landmark'),(47,92,'confident ,landmark, lost touch, interview,reliable','confident'),(48,93,'Confident ,landmark, lost touch, interview,reliable','interview'),(49,94,'Confident ,landmark, lost touch, interview,reliable','reliable'),(50,95,'Confident ,landmark, lost touch, interview,reliable','lost touch');
/*!40000 ALTER TABLE `archived_matching_questions` ENABLE KEYS */;
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
