SET FOREIGN_KEY_CHECKS = 0;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_detail` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES (1,1,'login','Logged into the system','2026-05-05 06:02:49'),(2,1,'login','Logged into the system','2026-05-05 06:03:33'),(3,1,'login','Logged into the system','2026-05-05 06:03:51'),(4,1,'login','Logged into the system','2026-05-05 06:12:24'),(5,1,'login','Logged into the system','2026-05-05 06:16:54'),(6,1,'login','Logged into the system','2026-05-05 06:17:46'),(7,1,'login','Logged into the system','2026-05-05 06:18:55'),(8,1,'task_create','Created a new task: Setup New Project','2026-05-05 06:20:22'),(9,1,'status_update','Updated task #1 to Doing','2026-05-05 06:22:12'),(10,1,'login','Logged into the system','2026-05-05 07:23:31'),(11,1,'login','Logged into the system','2026-05-05 07:25:00'),(12,1,'status_update','Updated task #1 to Finish','2026-05-05 07:25:44'),(13,1,'login','Logged into the system','2026-05-05 07:26:27'),(14,1,'login','Logged into the system','2026-05-05 07:26:59'),(15,1,'login','Logged into the system','2026-05-05 07:40:59'),(16,1,'login','Logged into the system','2026-05-05 07:51:03'),(17,1,'login','Logged into the system','2026-05-05 07:51:23'),(18,1,'login','Logged into the system','2026-05-05 08:14:49'),(19,2,'login','Logged into the system','2026-05-05 08:17:38'),(20,2,'task_create','Created a new task: Fix Point History Not Respond Issue','2026-05-05 08:19:53'),(21,2,'status_update','Updated task #2 to Doing','2026-05-05 08:20:03'),(22,1,'login','Logged into the system','2026-05-05 08:22:23'),(23,1,'login','Logged into the system','2026-05-05 08:34:56'),(24,1,'login','Logged into the system','2026-05-05 08:36:39'),(25,1,'login','Logged into the system','2026-05-05 08:39:20'),(26,2,'login','Logged into the system','2026-05-05 08:41:18'),(27,1,'login','Logged into the system','2026-05-05 08:42:33'),(28,1,'login','Logged into the system','2026-05-05 08:48:45'),(29,1,'login','Logged into the system','2026-05-05 09:09:23'),(30,1,'login','Logged into the system','2026-05-05 10:35:49'),(31,1,'login','Logged into the system','2026-05-05 10:46:20'),(32,1,'task_create','Created a new task: Bugfix for MD 5.0','2026-05-05 10:47:07'),(33,1,'status_update','Updated task #3 to Doing','2026-05-05 10:47:15'),(34,1,'login','Logged into the system','2026-05-05 12:28:03'),(35,1,'login','Logged into the system','2026-05-06 04:29:30');
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_members`
--

DROP TABLE IF EXISTS `project_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_members` (
  `project_id` int NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`project_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `project_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_members`
--

LOCK TABLES `project_members` WRITE;
/*!40000 ALTER TABLE `project_members` DISABLE KEYS */;
INSERT INTO `project_members` VALUES (1,2);
/*!40000 ALTER TABLE `project_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `project_leader_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_leader_id` (`project_leader_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`project_leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,'MD 5.0','',2,'2026-05-05 06:19:12','2026-05-05 06:19:12');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `data` text NOT NULL,
  `last_accessed` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('8cf1b292c6549a206d4d1cbf6bc8be09','user_id|i:1;username|s:8:\"Hui Shan\";role|s:5:\"admin\";','2026-05-05 12:28:28'),('a1296ba18717163168ff38808f8771c2','user_id|i:1;username|s:8:\"Hui Shan\";role|s:5:\"admin\";','2026-05-06 04:30:06');
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `project_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `priority` enum('urgent','high','medium','low') DEFAULT 'medium',
  `category` varchar(50) DEFAULT 'General',
  `due_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'Setup New Project','',1,'completed','medium','General','2026-05-05',NULL,1,1,'2026-05-05 06:20:22','2026-05-05 07:25:44'),(2,'Fix Point History Not Respond Issue','',1,'in-progress','urgent','General','2026-05-05',NULL,2,2,'2026-05-05 08:19:52','2026-05-05 08:20:03'),(3,'Bugfix for MD 5.0','',1,'in-progress','high','General','2026-05-05',NULL,1,1,'2026-05-05 10:47:06','2026-05-05 10:47:15');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','project_leader','member') DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Hui Shan','imhuishan29@gmail.com','$2y$12$d7qvTaekxDk9dKGZuSGkTu5ok.J7gONNxtFDSGNBkRHiFlLT8.Rt6','admin','2026-05-05 06:02:27'),(2,'Juin Qi','juinqis@gmail.com','$2y$12$mWyzHK5pU6oAdosNzNJ6rOX5SqjWTCnXmA8kk.PCjp4wPNBacgZri','project_leader','2026-05-05 06:18:34');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


-- Dump completed on 2026-05-06 12:50:55
