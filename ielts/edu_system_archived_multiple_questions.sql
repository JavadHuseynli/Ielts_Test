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
-- Table structure for table `archived_multiple_questions`
--

DROP TABLE IF EXISTS `archived_multiple_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archived_multiple_questions` (
  `id_archived_multiple` int NOT NULL AUTO_INCREMENT,
  `id_archived_question` int NOT NULL,
  `var_a` varchar(255) NOT NULL,
  `var_b` varchar(255) NOT NULL,
  `var_c` varchar(255) NOT NULL,
  `var_d` varchar(255) NOT NULL,
  `correct_v` enum('a','b','c','d') NOT NULL,
  PRIMARY KEY (`id_archived_multiple`),
  KEY `idx_archived_question` (`id_archived_question`),
  CONSTRAINT `archived_multiple_questions_ibfk_1` FOREIGN KEY (`id_archived_question`) REFERENCES `archived_questions` (`id_archived_question`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archived_multiple_questions`
--

LOCK TABLES `archived_multiple_questions` WRITE;
/*!40000 ALTER TABLE `archived_multiple_questions` DISABLE KEYS */;
INSERT INTO `archived_multiple_questions` VALUES (1,1,'has an end','is beautiful','','','a'),(2,2,'nature','Sculpture','','','a'),(3,3,'clouds','Sunlight','','','a'),(4,4,'control','Understand','','','a'),(5,5,'houses','world','','','b'),(6,11,'Tiring','Great','Dangerous','','b'),(7,12,'His taste buds','The ice cream','The factory machines','','a'),(8,13,'A factory worker','A chef','An ice-cream taster','','c'),(9,14,'Restaurant','Factory','Bakery','','b'),(10,15,'His last name','His job','His age','','b'),(11,21,'The universe’s properties like temperature and density','The history of the Earth','','','a'),(12,22,'Between Earth and the Moon','Between Mars and Jupiter','','','b'),(13,23,'Web designers','Physical and occupational therapists','','','b'),(14,24,'Business and finance skills','Engineering principles and design','','','b'),(15,25,'Artists and experts in artificial intelligence','Only computer repair technicians','','','a'),(16,46,'To make the fastest cars in the world','To become the largest gas-powered carmaker','To help the world switch to sustainable energy','To sell cars in every country','c'),(17,47,'2008','2012','2010','2020','c'),(18,48,'SmartDrive','Autopilot','Tesla Assist','Auto Navigator','b'),(19,49,'Selling Tesla cars','Making gas engines','Producing batteries and cars','Designing mobile apps','c'),(20,50,'2012','2015','2017','2020','c'),(21,51,'Model S','Model 3','Model Y','Model X','d'),(22,52,'100 miles','150 miles','Over 200 miles','300 miles','a'),(23,53,'Model 3','Model S','Roadster','Model Y','c'),(24,54,'Jeff Bezos','Bill Gates','Elon Musk','Steve Jobs','c'),(25,55,'2010','2003','2008','2012','b'),(26,66,'True','False','','','a'),(27,67,'True','False','','','b'),(28,68,'True','False','','','b'),(29,69,'True','False','','','a'),(30,70,'True','False','','','b'),(31,71,'True','False','','','b'),(32,72,'True','False','','','a'),(33,73,'True','False','','','b'),(34,74,'True','False','','','b'),(35,75,'True','False','','','a'),(36,76,'Do not apply for the job','Apply for many jobs','Apply for the job','','c'),(37,77,'a)Online learning, Setting up a website','b)Setting up a website','c)Online learning ,Technical English , Setting up a website','','c'),(38,78,'Yes, he should.','No, he should not.','She does not say.','','a'),(39,79,'he did worse','he did better','didn’t manage with the situation','','b'),(40,80,'the best teacher','hardworking','irresponsible','','b'),(41,81,'a student in his class','someone who is important','someone who saw him teach','','c'),(42,82,'long answers to different  questions','different answers to a question','no answers to questions','','a'),(43,83,'a couple of weeks','three couples of weeks','more than a couple of weeks','','a'),(44,84,'a good idea','not recommended','very helpful','','b'),(45,85,'Start paperwork','Write a resume','Request an application','','a');
/*!40000 ALTER TABLE `archived_multiple_questions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-10 16:08:17
