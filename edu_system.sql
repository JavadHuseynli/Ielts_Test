
create databse  edu_system;
use  edu_system;

CREATE TABLE `answers` (
  `id_answer` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `id_questions` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_answer` text NOT NULL,
  `correct_var` text NOT NULL,
  `is_correct` tinyint(1) GENERATED ALWAYS AS (`user_answer` = `correct_var`) STORED,
  `datetime` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id_exam` int(11) NOT NULL,
  `date_exam` date NOT NULL,
  `id_subject` int(11) NOT NULL,
  `id_student_group` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id_exam`, `date_exam`, `id_subject`, `id_student_group`, `datetime`, `status`, `created_at`, `updated_at`) VALUES
(29, '2025-05-30', 5, 2, '2025-05-30 00:03:00', 'in_progress', '2025-05-29 20:02:26', '2025-05-29 20:02:29');

-- --------------------------------------------------------

--
-- Table structure for table `matching_questions`
--

CREATE TABLE `matching_questions` (
  `id_matching` int(11) NOT NULL,
  `id_question_text` int(11) NOT NULL,
  `variants` text NOT NULL,
  `corr_variant` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `matching_questions`
--

INSERT INTO `matching_questions` (`id_matching`, `id_question_text`, `variants`, `corr_variant`, `created_at`, `updated_at`) VALUES
(1, 34, 'Check,have,fall,send,break', 'break', '2025-05-24 15:22:58', '2025-05-24 15:22:58'),
(2, 35, 'Check,have,fall,send,break', 'have', '2025-05-24 15:23:52', '2025-05-24 15:23:52'),
(3, 36, 'Check,have,fall,send,break', 'send', '2025-05-24 15:24:55', '2025-05-24 15:24:55'),
(4, 37, 'Check,have,fall,send,break', 'fall', '2025-05-24 15:25:30', '2025-05-24 15:25:30'),
(5, 38, 'Check,have,fall,send,break', 'check', '2025-05-24 15:25:51', '2025-05-24 15:25:51'),
(6, 64, 'watch, go,go out,remind,check', 'check', '2025-05-25 14:46:15', '2025-05-26 05:37:38'),
(7, 65, 'watch, go,go out,remind,check', 'go out', '2025-05-25 14:46:31', '2025-05-26 05:37:50'),
(8, 66, 'watch, go,go out,remind,check', 'watch', '2025-05-25 14:46:52', '2025-05-26 05:38:09'),
(9, 67, 'watch, go,go out,remind,check', 'remind', '2025-05-25 14:47:17', '2025-05-25 14:47:17'),
(10, 68, 'watch, go,go out,remind,check', 'go', '2025-05-25 14:47:41', '2025-05-26 05:38:47'),
(11, 94, 'disagree,do, pay,learn,study', 'do', '2025-05-25 16:28:41', '2025-05-25 16:28:41'),
(12, 95, 'disagree,do, pay,learn,study', 'disagree', '2025-05-25 16:28:59', '2025-05-25 16:28:59'),
(13, 96, 'disagree,do, pay,learn,study', 'learn', '2025-05-25 16:29:32', '2025-05-25 16:29:32'),
(14, 97, 'disagree,do, pay,learn,study', 'pay', '2025-05-25 16:29:58', '2025-05-25 16:29:58'),
(15, 98, 'disagree,do, pay,learn,study', 'study', '2025-05-25 16:30:16', '2025-05-25 16:30:16'),
(16, 125, 'book,do,go,have,rent', 'go', '2025-05-26 05:46:22', '2025-05-26 05:46:22'),
(17, 126, 'book,do,go,have,rent', 'do', '2025-05-26 05:46:46', '2025-05-26 05:46:46'),
(18, 127, 'book,do,go,have,rent', 'book', '2025-05-26 05:47:03', '2025-05-26 05:47:03'),
(19, 128, 'book,do,go,have,rent', 'have', '2025-05-26 05:47:23', '2025-05-26 05:47:23'),
(20, 129, 'book,do,go,have,rent', 'rent', '2025-05-26 05:47:43', '2025-05-26 05:47:43'),
(67, 201, 'True,False', 'True', '2025-05-27 07:04:37', '2025-05-27 07:04:37'),
(68, 202, 'True,False', 'False', '2025-05-27 07:05:02', '2025-05-27 07:05:02'),
(69, 203, 'True,False', 'True', '2025-05-27 07:05:23', '2025-05-27 07:05:23'),
(70, 204, 'True,False', 'False', '2025-05-27 07:05:46', '2025-05-27 07:05:46'),
(71, 205, 'True,False', 'True', '2025-05-27 07:06:08', '2025-05-27 07:06:08'),
(72, 211, 'environment,private,online,science,opportunities', 'opportunities', '2025-05-27 07:09:38', '2025-05-27 07:09:38'),
(73, 212, 'environment,private,online,science,opportunities', 'online', '2025-05-27 07:11:06', '2025-05-27 07:11:06'),
(74, 213, 'environment,private,online,science,opportunities', 'private', '2025-05-27 07:11:47', '2025-05-27 07:11:47'),
(75, 214, 'environment,private,online,science,opportunities', 'science', '2025-05-27 07:12:26', '2025-05-27 07:12:26'),
(76, 215, 'environment,private,online,science,opportunities', 'environment', '2025-05-27 07:13:38', '2025-05-27 07:13:38'),
(77, 216, 'True,False', 'False', '2025-05-27 07:14:54', '2025-05-27 08:14:19'),
(78, 217, 'True,False', 'True', '2025-05-27 07:15:17', '2025-05-27 08:14:39'),
(79, 218, 'True,False', 'False', '2025-05-27 07:15:57', '2025-05-27 07:15:57'),
(80, 219, 'True,False', 'False', '2025-05-27 07:16:20', '2025-05-27 08:15:21'),
(81, 220, 'True,False', 'True', '2025-05-27 07:16:41', '2025-05-27 07:16:41'),
(82, 221, 'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep', 'Randy\'s Sleep Experiment', '2025-05-27 07:25:43', '2025-05-27 08:16:18'),
(83, 222, 'How Gen Z Uses the Internet,A Digital Start to the Day,Gen Z’s Privacy Concerns,Social Life Through a Screen,Who Is Generation Z?', 'What Happened to Randy Without Sleep', '2025-05-27 07:26:15', '2025-05-27 08:17:56'),
(84, 223, 'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep', 'How Randy Recovered After the Experiment', '2025-05-27 07:26:36', '2025-05-27 08:18:48'),
(85, 224, 'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep', 'Dangers of Not Sleeping', '2025-05-27 07:27:11', '2025-05-27 08:25:25'),
(86, 225, 'Why Sleep Is Important, How Randy Recovered After the Experiment, The Dangers of Not Sleeping, Randy\'s Sleep Experiment, What Happened to Randy Without Sleep', 'Why Sleep Is Important', '2025-05-27 07:27:37', '2025-05-27 08:26:51'),
(87, 226, 'True,False', 'True', '2025-05-27 07:30:25', '2025-05-27 07:30:25'),
(88, 227, 'True,False', 'False', '2025-05-27 07:31:02', '2025-05-27 07:31:02'),
(89, 228, 'True,False', 'True', '2025-05-27 07:31:21', '2025-05-27 07:31:21'),
(90, 229, 'True,False', 'False', '2025-05-27 07:31:46', '2025-05-27 07:31:46'),
(91, 230, 'True,False', 'True', '2025-05-27 07:32:10', '2025-05-27 07:32:10'),
(92, 236, 'emissions,warming,climate,effects,release', 'release', '2025-05-27 07:36:21', '2025-05-27 07:36:21'),
(93, 237, 'emissions,warming,climate,effects,release', 'climate', '2025-05-27 07:36:52', '2025-05-27 07:36:52'),
(94, 238, 'emissions,warming,climate,effects,release', 'warming', '2025-05-27 07:47:50', '2025-05-27 07:47:50'),
(95, 239, 'emissions,warming,climate,effects,release', 'emissions', '2025-05-27 07:48:33', '2025-05-27 07:48:33'),
(96, 240, 'emissions,warming,climate,effects,release', 'effects', '2025-05-27 07:49:01', '2025-05-27 07:49:01'),
(97, 241, 'True,False', 'True', '2025-05-27 07:59:40', '2025-05-27 07:59:40'),
(98, 242, 'True,False', 'False', '2025-05-27 08:00:12', '2025-05-27 08:00:12'),
(99, 243, 'True,False', 'False', '2025-05-27 08:00:32', '2025-05-27 08:00:32'),
(100, 244, 'True,False', 'True', '2025-05-27 08:00:49', '2025-05-27 08:00:49'),
(101, 245, 'True,False', 'True', '2025-05-27 08:01:10', '2025-05-27 08:01:10'),
(102, 246, 'How Gen Z Uses the Internet, A Digital Start to the Day, Gen Z’s Privacy Concerns, Social Life Through a Screen, Who Is Generation Z?', 'A Digital Start to the Day', '2025-05-27 08:01:41', '2025-05-27 08:01:41'),
(103, 247, 'How Gen Z Uses the Internet, A Digital Start to the Day, Gen Z’s Privacy Concerns, Social Life Through a Screen, Who Is Generation Z?', 'Social Life Through a Screen', '2025-05-27 08:08:43', '2025-05-27 08:08:43'),
(104, 248, 'How Gen Z Uses the Internet,A Digital Start to the Day,Gen Z’s Privacy Concerns,Social Life Through a Screen,Who Is Generation Z?', 'Who Is Generation Z?', '2025-05-27 08:09:19', '2025-05-27 08:09:19'),
(105, 249, 'How Gen Z Uses the Internet,A Digital Start to the Day,Gen Z’s Privacy Concerns,Social Life Through a Screen,Who Is Generation Z?', 'How Gen Z Uses the Internet', '2025-05-27 08:10:06', '2025-05-27 08:10:06'),
(106, 250, 'How Gen Z Uses the Internet,A Digital Start to the Day,Gen Z’s Privacy Concerns,Social Life Through a Screen,Who Is Generation Z?', 'Gen Z’s Privacy Concerns', '2025-05-27 08:11:20', '2025-05-27 08:11:20');

-- --------------------------------------------------------

--
-- Table structure for table `multiple_questions`
--

CREATE TABLE `multiple_questions` (
  `id_multiple` int(11) NOT NULL,
  `id_question_text` int(11) NOT NULL,
  `var_a` varchar(255) NOT NULL,
  `var_b` varchar(255) NOT NULL,
  `var_c` varchar(255) NOT NULL,
  `var_d` varchar(255) NOT NULL,
  `correct_v` enum('a','b','c','d') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `multiple_questions`
--

INSERT INTO `multiple_questions` (`id_multiple`, `id_question_text`, `var_a`, `var_b`, `var_c`, `var_d`, `correct_v`, `created_at`, `updated_at`) VALUES
(12, 24, 'True', 'False', '', '', 'b', '2025-05-24 15:17:25', '2025-05-25 16:34:34'),
(13, 25, 'True', 'False', '', '', 'b', '2025-05-24 15:17:44', '2025-05-25 16:34:34'),
(14, 26, 'True', 'False', '', '', 'a', '2025-05-24 15:18:03', '2025-05-25 16:34:34'),
(15, 27, 'True', 'False', '', '', 'b', '2025-05-24 15:18:25', '2025-05-25 16:34:34'),
(16, 28, 'True', 'False', '', '', 'a', '2025-05-24 15:18:45', '2025-05-25 16:34:34'),
(17, 29, 'True', 'False', '', '', 'a', '2025-05-24 15:19:03', '2025-05-25 16:34:34'),
(18, 30, 'True', 'False', '', '', 'a', '2025-05-24 15:19:31', '2025-05-25 16:34:34'),
(19, 31, 'True', 'False', '', '', 'b', '2025-05-24 15:20:13', '2025-05-25 16:34:34'),
(20, 32, 'True', 'False', '', '', 'a', '2025-05-24 15:20:36', '2025-05-25 16:34:34'),
(21, 33, 'True', 'False', '', '', 'a', '2025-05-24 15:20:57', '2025-05-25 16:34:34'),
(22, 39, 'Stockholm', 'China', 'France', '', 'a', '2025-05-24 15:27:16', '2025-05-25 16:34:34'),
(23, 40, '5', '6', '7', '', 'b', '2025-05-24 15:27:38', '2025-05-25 16:34:34'),
(24, 41, 'New York', 'Rome', 'Tokyo', '', 'c', '2025-05-24 15:28:08', '2025-05-25 16:34:34'),
(25, 42, 'Oxford', 'Japan', 'Turkey', '', 'a', '2025-05-24 15:28:34', '2025-05-25 16:34:34'),
(26, 43, 'month', 'year', 'day', '', 'b', '2025-05-24 15:29:02', '2025-05-25 16:34:34'),
(27, 44, 'Tokyo', 'Paris', 'Japan', '', 'b', '2025-05-24 15:29:31', '2025-05-25 16:34:34'),
(28, 45, 'Yes', 'No', 'No information', '', 'a', '2025-05-24 15:30:00', '2025-05-25 16:34:34'),
(29, 46, 'parks', 'cafes', 'bars', '', 'a', '2025-05-24 15:30:31', '2025-05-25 16:34:34'),
(30, 47, 'garden', 'nature', 'library', '', 'b', '2025-05-24 15:30:57', '2025-05-25 16:34:34'),
(31, 48, 'cats', 'things', 'friends', '', 'c', '2025-05-24 15:31:29', '2025-05-25 16:34:34'),
(32, 49, 'cheaper', 'the cheapest', '', '', 'a', '2025-05-24 16:46:21', '2025-05-24 16:46:21'),
(33, 50, 'ever', 'never', '', '', 'a', '2025-05-24 16:46:41', '2025-05-24 16:46:41'),
(34, 51, 'have finished', 'finished', '', '', 'b', '2025-05-24 16:47:17', '2025-05-24 16:47:17'),
(35, 52, 'I will', 'I won’t', '', '', 'a', '2025-05-24 16:47:40', '2025-05-24 16:47:40'),
(36, 53, 'younger', 'young', '', '', 'b', '2025-05-24 16:48:05', '2025-05-24 16:48:05'),
(37, 54, 'True', 'False', '', '', 'a', '2025-05-25 14:39:22', '2025-05-25 16:34:34'),
(38, 55, 'True', 'False', '', '', 'a', '2025-05-25 14:39:44', '2025-05-25 16:34:34'),
(39, 56, 'True', 'False', '', '', 'b', '2025-05-25 14:40:15', '2025-05-25 16:34:34'),
(40, 57, 'True', 'False', '', '', 'a', '2025-05-25 14:40:44', '2025-05-25 16:34:34'),
(41, 58, 'True', 'False', '', '', 'a', '2025-05-25 14:41:07', '2025-05-25 16:34:34'),
(42, 59, 'True', 'False', '', '', 'b', '2025-05-25 14:42:54', '2025-05-25 16:34:34'),
(43, 60, 'True', 'False', '', '', 'a', '2025-05-25 14:43:31', '2025-05-25 16:34:34'),
(44, 61, 'True', 'False', '', '', 'b', '2025-05-25 14:44:23', '2025-05-25 16:34:34'),
(45, 62, 'True', 'False', '', '', 'a', '2025-05-25 14:44:47', '2025-05-25 16:34:34'),
(46, 63, 'True', 'False', '', '', 'a', '2025-05-25 14:45:11', '2025-05-25 16:34:34'),
(47, 69, 'wakes up', 'woke up', '', '', 'a', '2025-05-25 14:48:56', '2025-05-25 14:48:56'),
(48, 70, 'go', 'went', '', '', 'b', '2025-05-25 14:49:20', '2025-05-26 06:01:28'),
(49, 71, 'likes', 'liked', '', '', 'a', '2025-05-25 14:49:49', '2025-05-25 14:49:49'),
(50, 72, 'visit', 'visited', '', '', 'b', '2025-05-25 14:50:11', '2025-05-25 14:50:11'),
(51, 73, 'reads', 'read', '', '', 'a', '2025-05-25 14:51:02', '2025-05-25 14:51:02'),
(52, 74, 'True', 'False', '', '', 'a', '2025-05-25 14:52:02', '2025-05-25 16:34:34'),
(53, 75, 'True', 'False', '', '', 'a', '2025-05-25 14:52:20', '2025-05-25 16:34:34'),
(54, 76, 'True', 'False', '', '', 'b', '2025-05-25 14:52:44', '2025-05-25 16:34:34'),
(55, 77, 'True', 'False', '', '', 'a', '2025-05-25 14:53:08', '2025-05-25 16:34:34'),
(56, 78, 'True', 'False', '', '', 'a', '2025-05-25 14:53:38', '2025-05-25 16:35:26'),
(57, 79, 'True', 'False', '', '', 'a', '2025-05-25 14:54:10', '2025-05-25 16:35:26'),
(58, 80, 'True', 'False', '', '', 'a', '2025-05-25 14:54:40', '2025-05-25 16:35:26'),
(59, 81, 'true', 'False', '', '', 'b', '2025-05-25 14:55:16', '2025-05-25 16:35:26'),
(60, 82, 'True', 'False', '', '', 'a', '2025-05-25 14:55:39', '2025-05-25 16:35:26'),
(61, 83, 'True', 'False', '', '', 'a', '2025-05-25 14:55:53', '2025-05-25 16:35:26'),
(62, 84, 'True', 'False', '', '', 'b', '2025-05-25 16:23:53', '2025-05-25 16:35:26'),
(63, 85, 'True', 'False', '', '', 'a', '2025-05-25 16:24:14', '2025-05-25 16:35:26'),
(64, 86, 'True', 'False', '', '', 'b', '2025-05-25 16:24:38', '2025-05-25 16:35:26'),
(65, 87, 'True', 'False', '', '', 'a', '2025-05-25 16:24:59', '2025-05-25 16:35:26'),
(66, 88, 'True', 'False', '', '', 'a', '2025-05-25 16:25:27', '2025-05-25 16:35:26'),
(67, 89, 'True', 'False', '', '', 'b', '2025-05-25 16:25:52', '2025-05-25 16:35:26'),
(68, 90, 'True', 'False', '', '', 'a', '2025-05-25 16:26:14', '2025-05-25 16:35:26'),
(69, 91, 'True', 'False', '', '', 'b', '2025-05-25 16:26:34', '2025-05-25 16:35:26'),
(70, 92, 'True', 'False', '', '', 'a', '2025-05-25 16:27:10', '2025-05-25 16:35:26'),
(71, 93, 'True', 'False', '', '', 'b', '2025-05-25 16:27:30', '2025-05-25 16:35:26'),
(72, 99, 'bored', 'boring', '', '', 'b', '2025-05-25 16:30:42', '2025-05-25 16:30:42'),
(73, 100, 'exciting', 'excited', '', '', 'b', '2025-05-25 16:31:27', '2025-05-25 16:31:27'),
(74, 101, 'interested', 'interesting', '', '', 'b', '2025-05-25 16:31:54', '2025-05-25 16:31:54'),
(75, 102, 'frightened', 'frightening', '', '', 'a', '2025-05-25 16:32:17', '2025-05-25 16:32:17'),
(76, 103, 'disappointed', 'disappointing', '', '', 'b', '2025-05-25 16:32:37', '2025-05-26 05:30:10'),
(78, 105, 'On Sunday', 'On Saturday', 'On Friday', '', 'b', '2025-05-25 16:37:14', '2025-05-25 16:37:14'),
(79, 106, 'By car', 'By bus', 'By train', '', 'c', '2025-05-25 16:37:43', '2025-05-25 16:37:43'),
(80, 107, '10 a.m.', '9 a.m.', '11 a.m.', '', 'b', '2025-05-25 16:38:14', '2025-05-25 16:38:14'),
(81, 108, 'To a restaurant', 'To a museum', 'To a park', '', 'b', '2025-05-25 16:38:41', '2025-05-25 16:38:41'),
(82, 109, 'Dinosaurs', 'Robots', 'Space exhibition', '', 'c', '2025-05-25 16:39:12', '2025-05-25 16:39:12'),
(83, 110, 'Robots', 'Space', 'Animals', '', 'a', '2025-05-25 16:39:41', '2025-05-25 16:39:41'),
(84, 111, 'Pasta', 'Pizza', 'Salad', '', 'b', '2025-05-25 16:40:10', '2025-05-25 16:40:10'),
(85, 112, 'Home', 'The cinema', 'The park', '', 'c', '2025-05-25 16:40:37', '2025-05-25 16:40:37'),
(86, 113, 'A book', 'A cap', 'A T-shirt', '', 'c', '2025-05-25 16:41:06', '2025-05-25 16:41:06'),
(87, 114, '5 p.m.', '6 p.m.', '7 p.m.', '', 'b', '2025-05-25 16:41:34', '2025-05-25 16:41:34'),
(88, 115, 'True', 'False', '', '', 'b', '2025-05-26 05:42:30', '2025-05-26 05:42:30'),
(89, 116, 'True', 'False', '', '', 'a', '2025-05-26 05:42:50', '2025-05-26 05:42:50'),
(90, 117, 'True', 'False', '', '', 'b', '2025-05-26 05:43:09', '2025-05-26 05:43:09'),
(91, 118, 'True', 'False', '', '', 'a', '2025-05-26 05:43:25', '2025-05-26 05:43:25'),
(92, 119, 'True', 'False', '', '', 'b', '2025-05-26 05:43:45', '2025-05-26 05:43:45'),
(93, 120, 'True', 'False', '', '', 'b', '2025-05-26 05:44:00', '2025-05-26 05:44:00'),
(94, 121, 'True', 'False', '', '', 'a', '2025-05-26 05:44:29', '2025-05-26 05:44:29'),
(95, 122, 'True', 'False', '', '', 'b', '2025-05-26 05:44:47', '2025-05-26 05:44:47'),
(96, 123, 'True', 'False', '', '', 'a', '2025-05-26 05:45:03', '2025-05-26 05:45:03'),
(97, 124, 'True', 'False', '', '', 'a', '2025-05-26 05:45:26', '2025-05-26 05:45:26'),
(98, 130, 'doesn`t   have', 'has', '', '', 'a', '2025-05-26 05:48:33', '2025-05-26 05:48:33'),
(99, 131, 'as big', 'bigger', '', '', 'b', '2025-05-26 05:48:59', '2025-05-26 05:48:59'),
(100, 132, 'say', 'to say', '', '', 'a', '2025-05-26 05:49:46', '2025-05-26 05:49:46'),
(101, 133, 'ever', 'never', '', '', 'a', '2025-05-26 05:50:07', '2025-05-26 05:50:07'),
(102, 134, 'check', 'to check', '', '', 'a', '2025-05-26 05:50:26', '2025-05-26 05:50:26'),
(103, 135, 'Their parents are out.', 'Their parents will be late.', 'Their parents are sick.', '', 'b', '2025-05-26 05:53:08', '2025-05-26 05:53:08'),
(104, 136, 'Tomatoes, cucumber, mushrooms.', 'Lettuce and onions.', 'Carrots and celery.', '', 'a', '2025-05-26 05:53:34', '2025-05-26 05:53:34'),
(105, 137, 'They have no meat.', 'Ken doesn’t like hamburgers.', 'Mum doesn’t eat meat.', '', 'c', '2025-05-26 05:54:08', '2025-05-26 05:54:08'),
(106, 138, 'It`s not enough food.', 'It`s too hard to make.', 'They have no vegetables.', '', 'a', '2025-05-26 05:54:58', '2025-05-26 05:54:58'),
(107, 139, 'Fish pie', 'Pizza', 'Salad', '', 'a', '2025-05-26 05:55:24', '2025-05-26 05:55:24'),
(108, 140, 'It takes too long.', 'They have no fish.', 'They have no potatoes.', '', 'a', '2025-05-26 05:55:55', '2025-05-26 05:55:55'),
(109, 141, 'They ate pizza yesterday.', 'They have no cheese.', 'They have no pizza bases.', '', 'a', '2025-05-26 05:58:12', '2025-05-26 05:58:12'),
(110, 142, 'They have no eggs.', 'They don’t know how.', 'Dad wants to save eggs.', '', 'c', '2025-05-26 05:58:48', '2025-05-26 05:58:48'),
(111, 143, 'Soup', 'Pasta', 'Salad', '', 'b', '2025-05-26 05:59:30', '2025-05-26 05:59:30'),
(112, 144, 'Cheese and onions', 'Tomatoes and mushrooms', 'Carrots and celery', '', 'b', '2025-05-26 06:00:01', '2025-05-26 06:00:01'),
(123, 206, 'Artists and experts in artificial intelligence', 'Only computer repair technicians', '', '', 'a', '2025-05-27 07:06:47', '2025-05-27 07:06:47'),
(124, 207, 'Business and finance skills', 'Engineering principles and design', '', '', 'b', '2025-05-27 07:07:12', '2025-05-27 07:07:12'),
(125, 208, 'Web designers', 'Physical and occupational therapists', '', '', 'b', '2025-05-27 07:07:36', '2025-05-27 07:07:36'),
(126, 209, 'Between Earth and the Moon', 'Between Mars and Jupiter', '', '', 'b', '2025-05-27 07:08:09', '2025-05-27 07:08:09'),
(127, 210, 'The universe’s properties like temperature and density', 'The history of the Earth', '', '', 'a', '2025-05-27 07:08:29', '2025-05-27 07:08:29'),
(128, 231, 'Human activities such as burning fossil fuels', 'Natural weather changes', '', '', 'a', '2025-05-27 07:32:48', '2025-05-27 07:32:48'),
(129, 232, 'Carbon dioxide', 'Nitrogen', '', '', 'a', '2025-05-27 07:33:25', '2025-05-27 07:33:25'),
(130, 233, 'The temperature will still keep rising for many years', 'The temperature will stay the same', '', '', 'a', '2025-05-27 07:34:24', '2025-05-27 07:34:24'),
(131, 234, 'Because the atmosphere is getting hotter', 'Because humans stopped using fossil fuels', '', '', 'a', '2025-05-27 07:34:50', '2025-05-27 07:34:50'),
(132, 235, 'All countries will stop using energy', 'Some places will become drier, wetter, or windier', '', '', 'b', '2025-05-27 07:35:10', '2025-05-27 07:35:10');

-- --------------------------------------------------------

--
-- Table structure for table `open_questions`
--

CREATE TABLE `open_questions` (
  `id_open` int(11) NOT NULL,
  `id_question_text` int(11) NOT NULL,
  `users_v` text DEFAULT NULL,
  `corr_v` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_files`
--

CREATE TABLE `question_files` (
  `id_read_quest_file` int(11) NOT NULL,
  `file_type` enum('reading','listening') NOT NULL,
  `file_title` varchar(255) DEFAULT NULL,
  `subject_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `question_files`
--

INSERT INTO `question_files` (`id_read_quest_file`, `file_type`, `file_title`, `subject_id`, `file_path`, `created_at`, `updated_at`) VALUES
(7, 'reading', 'Variant A', 3, '../uploads/6831e2b927dd8_A Variant.txt', '2025-05-24 15:16:09', '2025-05-24 15:16:09'),
(8, 'listening', 'Variant A', 3, '../uploads/6831e3f598b8e_exam 1.mp3', '2025-05-24 15:21:25', '2025-05-24 15:21:25'),
(9, 'reading', 'Variant B', 3, '../uploads/68332b5b9cda4_B Variant.txt', '2025-05-25 14:38:19', '2025-05-25 14:38:19'),
(10, 'listening', 'Variant B', 3, '../uploads/68332e6eaba03_exam 2.mp3', '2025-05-25 14:51:26', '2025-05-25 14:51:26'),
(11, 'reading', 'Variant C', 3, '../uploads/683343e247094_C Variant.txt', '2025-05-25 16:22:58', '2025-05-25 16:22:58'),
(12, 'listening', 'Variant C', 3, '../uploads/683347118c27c_exam 3.mp3', '2025-05-25 16:36:33', '2025-05-25 16:36:33'),
(13, 'reading', 'Variant D', 3, '../uploads/6833ff35109b1_D Variant.txt', '2025-05-26 05:42:13', '2025-05-26 05:42:13'),
(14, 'listening', 'Variant D', 3, '../uploads/6834019aa73ce_exam 4.mp3', '2025-05-26 05:52:26', '2025-05-26 05:52:26'),
(19, 'reading', 'Variant 1', 5, '../uploads/6835638eaf46c_OR Variant 1.txt', '2025-05-27 07:02:38', '2025-05-27 07:02:38'),
(20, 'reading', 'Variant 1', 5, '../uploads/683563a0c1174_OR Variant 1 metn 2 .txt', '2025-05-27 07:02:56', '2025-05-27 07:02:56'),
(21, 'reading', 'Variant 2', 5, '../uploads/683569c09f7d1_OR Variant 2.txt', '2025-05-27 07:29:04', '2025-05-27 07:29:04'),
(22, 'reading', 'Variant 2', 5, '../uploads/68356ea42e408_OR Variant 2 metn 2.txt', '2025-05-27 07:49:56', '2025-05-27 07:49:56');

-- --------------------------------------------------------

--
-- Table structure for table `question_read`
--

CREATE TABLE `question_read` (
  `id_question_text` int(11) NOT NULL,
  `id_read_quest_file` int(11) NOT NULL,
  `id_question_type` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_score` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `question_read`
--

INSERT INTO `question_read` (`id_question_text`, `id_read_quest_file`, `id_question_type`, `question_text`, `question_score`, `created_at`, `updated_at`) VALUES
(24, 7, 1, 'Lygos was the old name for Montréal.', 1.00, '2025-05-24 15:17:25', '2025-05-24 15:17:25'),
(25, 7, 1, 'Istanbul isn’t as old as the other cities.', 1.00, '2025-05-24 15:17:44', '2025-05-24 15:17:44'),
(26, 7, 1, 'Istanbul’s second name was Byzantium.', 1.00, '2025-05-24 15:18:03', '2025-05-24 15:18:03'),
(27, 7, 1, 'The name Byzantium came from an emperor.', 1.00, '2025-05-24 15:18:25', '2025-05-24 15:18:25'),
(28, 7, 1, 'Istanbul was previously called New Rome.', 1.00, '2025-05-24 15:18:45', '2025-05-24 15:18:45'),
(29, 7, 1, 'About 50 people lived in Ville-Marie at first.', 1.00, '2025-05-24 15:19:03', '2025-05-24 15:19:03'),
(30, 7, 1, 'There is a big hill in or near Montréal.', 1.00, '2025-05-24 15:19:31', '2025-05-24 15:19:31'),
(31, 7, 1, 'The Dutch first called New York Mannahatta.', 1.00, '2025-05-24 15:20:13', '2025-05-24 15:20:13'),
(32, 7, 1, 'New York was New Orange for a short time.', 1.00, '2025-05-24 15:20:36', '2025-05-24 15:20:36'),
(33, 7, 1, 'A few cities have changed their names.', 1.00, '2025-05-24 15:20:57', '2025-05-24 15:20:57'),
(34, 7, 3, 'Fill in the gaps \r\nI hope I never _____  any bones!', 1.00, '2025-05-24 15:22:58', '2025-05-24 15:22:58'),
(35, 7, 3, 'I’m looking forward to your party. We’re really going to ________ fun!', 1.00, '2025-05-24 15:23:52', '2025-05-24 15:23:52'),
(36, 7, 3, 'Did you remember to ________ the invitations for our dinner party next week?', 1.00, '2025-05-24 15:24:55', '2025-05-24 15:24:55'),
(37, 7, 3, 'What time did you ________ asleep last night?', 1.00, '2025-05-24 15:25:30', '2025-05-24 15:25:30'),
(38, 7, 3, 'We have to ________ out of the hotel before 12 o’clock, so we have to hurry.', 1.00, '2025-05-24 15:25:51', '2025-05-24 15:25:51'),
(39, 8, 1, 'Where was Karl born ?', 1.00, '2025-05-24 15:27:16', '2025-05-24 15:27:16'),
(40, 8, 1, 'Karl has lived in Milan for ______years.', 1.00, '2025-05-24 15:27:38', '2025-05-24 15:27:38'),
(41, 8, 1, 'What was Karl favourite city?', 1.00, '2025-05-24 15:28:08', '2025-05-24 15:28:08'),
(42, 8, 1, 'Where did Ines move ?', 1.00, '2025-05-24 15:28:34', '2025-05-24 15:28:34'),
(43, 8, 1, 'Ines was living in Madrid last ______', 1.00, '2025-05-24 15:29:02', '2025-05-24 15:29:02'),
(44, 8, 1, 'Two years ago, Ines visited _____', 1.00, '2025-05-24 15:29:31', '2025-05-24 15:29:31'),
(45, 8, 1, 'Did Ines like Paris food ?', 1.00, '2025-05-24 15:30:00', '2025-05-24 15:30:00'),
(46, 8, 1, 'Sofia really likes the _____in São Paulo.', 1.00, '2025-05-24 15:30:31', '2025-05-24 15:30:31'),
(47, 8, 1, 'Sofia says she enjoys spending time in ______', 1.00, '2025-05-24 15:30:57', '2025-05-24 15:30:57'),
(48, 8, 1, 'Lili wants to go to Budapest because she’s got ________ who live there.', 1.00, '2025-05-24 15:31:29', '2025-05-24 15:31:29'),
(49, 7, 1, 'This shop is _______than that one.', 1.00, '2025-05-24 16:46:21', '2025-05-24 16:46:21'),
(50, 7, 1, 'Have you _______been to a cricket match?', 1.00, '2025-05-24 16:46:41', '2025-05-24 16:46:41'),
(51, 7, 1, 'We _______it a few days ago.', 1.00, '2025-05-24 16:47:17', '2025-05-24 16:47:17'),
(52, 7, 1, 'It might rain, so _______take an umbrella.', 1.00, '2025-05-24 16:47:40', '2025-05-24 16:47:40'),
(53, 7, 1, 'Is Rachel as ……as    Margaret?', 1.00, '2025-05-24 16:48:05', '2025-05-24 16:48:05'),
(54, 9, 1, 'Wilmer McLean lived in Virginia in 1861.', 1.00, '2025-05-25 14:39:22', '2025-05-25 14:39:22'),
(55, 9, 1, 'A battle happened in McLean’s house, but his family was safe.', 1.00, '2025-05-25 14:39:44', '2025-05-25 14:39:44'),
(56, 9, 1, 'After the battle, McLean stayed in the same house.', 1.00, '2025-05-25 14:40:15', '2025-05-25 14:40:15'),
(57, 9, 1, 'The Civil War ended in McLean’s new house.', 1.00, '2025-05-25 14:40:44', '2025-05-25 14:40:44'),
(58, 9, 1, 'Violet Jessop worked on three different ships.', 1.00, '2025-05-25 14:41:07', '2025-05-25 14:41:07'),
(59, 9, 1, 'The Titanic hit another ship and almost sank.', 1.00, '2025-05-25 14:42:54', '2025-05-25 14:42:54'),
(60, 9, 1, 'Jessop survived the Titanic disaster.', 1.00, '2025-05-25 14:43:31', '2025-05-25 14:43:31'),
(61, 9, 1, 'Roy Sullivan was hit by lightning only once.', 1.00, '2025-05-25 14:44:23', '2025-05-25 14:44:23'),
(62, 9, 1, 'People gave Sullivan the nickname ‘Human Lightning Rod.’', 1.00, '2025-05-25 14:44:47', '2025-05-25 14:44:47'),
(63, 9, 1, 'Sullivan’s workplace was close to McLean’s houses and farm.', 1.00, '2025-05-25 14:45:11', '2025-05-25 14:45:11'),
(64, 9, 3, 'We have to ________ out of the hotel before 12 o’clock, so we have to hurry.', 1.00, '2025-05-25 14:46:15', '2025-05-25 14:46:15'),
(65, 9, 3, 'Let’s ________for dinner tonight. I’m tired of eating at home.', 1.00, '2025-05-25 14:46:31', '2025-05-25 14:46:31'),
(66, 9, 3, 'I’m tired this evening, so I’ll just ________some TV and then go to bed early.', 1.00, '2025-05-25 14:46:52', '2025-05-25 14:46:52'),
(67, 9, 3, 'We need to ________ everyone about the date of the party.', 1.00, '2025-05-25 14:47:17', '2025-05-25 14:47:17'),
(68, 9, 3, 'I love swimming in the sea, and next year I’d like to ________ waterskiing.', 1.00, '2025-05-25 14:47:41', '2025-05-25 14:47:41'),
(69, 9, 1, 'Sarah ____early every morning and drinks coffee.', 1.00, '2025-05-25 14:48:56', '2025-05-25 14:48:56'),
(70, 9, 1, 'Yesterday, Tom ___to the park with his friends', 1.00, '2025-05-25 14:49:20', '2025-05-25 14:49:20'),
(71, 9, 1, 'My brother ____ football, but he doesn’t play it often.', 1.00, '2025-05-25 14:49:49', '2025-05-25 14:49:49'),
(72, 9, 1, 'Last weekend, we  ____our grandparents in the countryside.', 1.00, '2025-05-25 14:50:11', '2025-05-25 14:50:11'),
(73, 9, 1, 'Emma always _____a book before going to bed.', 1.00, '2025-05-25 14:51:02', '2025-05-25 14:51:02'),
(74, 10, 1, 'Ben and Zoe woke up late for their holiday.', 1.00, '2025-05-25 14:52:02', '2025-05-25 14:52:02'),
(75, 10, 1, 'They forgot their passports', 1.00, '2025-05-25 14:52:20', '2025-05-25 14:52:20'),
(76, 10, 1, 'They did not pick up their passports', 1.00, '2025-05-25 14:52:44', '2025-05-25 14:52:44'),
(77, 10, 1, 'Ben and Zoe’s flight was not very good.', 1.00, '2025-05-25 14:53:08', '2025-05-25 14:53:08'),
(78, 10, 1, 'They took a taxi', 1.00, '2025-05-25 14:53:38', '2025-05-25 14:53:38'),
(79, 10, 1, 'Ben’s phone battery had no power.', 1.00, '2025-05-25 14:54:10', '2025-05-25 14:54:10'),
(80, 10, 1, 'Zoe needed to buy socks and T-shirts.', 1.00, '2025-05-25 14:54:40', '2025-05-25 14:54:40'),
(81, 10, 1, 'They did`t do shopping', 1.00, '2025-05-25 14:55:16', '2025-05-25 14:55:16'),
(82, 10, 1, 'Ben and Zoe were hurt in an accident.', 1.00, '2025-05-25 14:55:39', '2025-05-25 14:55:39'),
(83, 10, 1, 'Ben and Zoe want to go on another holiday.', 1.00, '2025-05-25 14:55:53', '2025-05-25 14:55:53'),
(84, 11, 1, 'A healthy lifestyle is only important for young people.', 1.00, '2025-05-25 16:23:53', '2025-05-25 16:23:53'),
(85, 11, 1, 'Eating fruits and vegetables is part of a healthy diet.', 1.00, '2025-05-25 16:24:14', '2025-05-25 16:24:14'),
(86, 11, 1, 'Drinking lots of sugary drinks is good for your health.', 1.00, '2025-05-25 16:24:38', '2025-05-25 16:24:38'),
(87, 11, 1, 'People should do physical activity every day.', 1.00, '2025-05-25 16:24:59', '2025-05-25 16:24:59'),
(88, 11, 1, 'Walking and swimming are examples of physical activity.', 1.00, '2025-05-25 16:25:27', '2025-05-25 16:25:27'),
(89, 11, 1, 'Teenagers need only 5 hours of sleep each night.', 1.00, '2025-05-25 16:25:52', '2025-05-25 16:25:52'),
(90, 11, 1, 'Getting enough sleep can help students focus better.', 1.00, '2025-05-25 16:26:14', '2025-05-25 16:26:14'),
(91, 11, 1, 'Smoking and drinking alcohol help you live longer.', 1.00, '2025-05-25 16:26:34', '2025-05-25 16:26:34'),
(92, 11, 1, 'Harmful substances can damage the body.', 1.00, '2025-05-25 16:27:10', '2025-05-25 16:27:10'),
(93, 11, 1, 'Living a healthy life requires very difficult changes.', 1.00, '2025-05-25 16:27:30', '2025-05-25 16:27:30'),
(94, 11, 3, 'When he’s not busy, what does he_________for fun?', 1.00, '2025-05-25 16:28:41', '2025-05-26 07:02:02'),
(95, 11, 3, 'We like Tania, but we often ________ with her.', 1.00, '2025-05-25 16:28:59', '2025-05-25 16:28:59'),
(96, 11, 3, 'Ed used the internet to ________ about the history of Poland.', 1.00, '2025-05-25 16:29:32', '2025-05-25 16:29:32'),
(97, 11, 3, 'I`ll use my credit card to ________ for these shoes.', 1.00, '2025-05-25 16:29:58', '2025-05-25 16:29:58'),
(98, 11, 3, 'He had to ________ for six weeks to pass his exam.', 1.00, '2025-05-25 16:30:16', '2025-05-25 16:30:16'),
(99, 11, 1, 'The movie was so __________ that I fell asleep.', 1.00, '2025-05-25 16:30:42', '2025-05-25 16:30:42'),
(100, 11, 1, 'I was really __________ when I heard the good news.', 1.00, '2025-05-25 16:31:27', '2025-05-25 16:31:27'),
(101, 11, 1, 'This book is very __________. I can’t stop reading it!', 1.00, '2025-05-25 16:31:54', '2025-05-25 16:31:54'),
(102, 11, 1, 'My little brother is __________ of the dark.', 1.00, '2025-05-25 16:32:17', '2025-05-25 16:32:17'),
(103, 11, 1, 'I didn’t enjoy the trip. It was really __________.', 1.00, '2025-05-25 16:32:37', '2025-05-25 16:32:37'),
(105, 12, 1, 'When did Sarah and her family go to the city?', 1.00, '2025-05-25 16:37:14', '2025-05-25 16:37:14'),
(106, 12, 1, 'How did they travel to the city?', 1.00, '2025-05-25 16:37:43', '2025-05-25 16:37:43'),
(107, 12, 1, 'What time did they take the train?', 1.00, '2025-05-25 16:38:14', '2025-05-25 16:38:14'),
(108, 12, 1, 'Where did they go first in the city?', 1.00, '2025-05-25 16:38:41', '2025-05-25 16:38:41'),
(109, 12, 1, 'What did Sarah like in the museum?', 1.00, '2025-05-25 16:39:12', '2025-05-25 16:39:12'),
(110, 12, 1, 'What did Jack enjoy at the museum?', 1.00, '2025-05-25 16:39:41', '2025-05-25 16:39:41'),
(111, 12, 1, 'What did Sarah eat for lunch?', 1.00, '2025-05-25 16:40:10', '2025-05-25 16:40:10'),
(112, 12, 1, 'Where did they go after lunch?', 1.00, '2025-05-25 16:40:37', '2025-05-25 16:40:37'),
(113, 12, 1, 'What did Jack buy while shopping?', 1.00, '2025-05-25 16:41:06', '2025-05-25 16:41:06'),
(114, 12, 1, 'What time did they go back home?', 1.00, '2025-05-25 16:41:34', '2025-05-25 16:41:34'),
(115, 13, 1, 'Uluru has three official names.', 1.00, '2025-05-26 05:42:30', '2025-05-26 05:42:30'),
(116, 13, 1, 'Uluru is not as high as Angel Falls.', 1.00, '2025-05-26 05:42:50', '2025-05-26 05:42:50'),
(117, 13, 1, 'People cannot climb Uluru now.', 1.00, '2025-05-26 05:43:09', '2025-05-26 05:43:09'),
(118, 13, 1, 'Lake Baikal does not have salty water.', 1.00, '2025-05-26 05:43:25', '2025-05-26 05:43:25'),
(119, 13, 1, 'Uluru is in the middle of a forest.', 1.00, '2025-05-26 05:43:45', '2025-05-26 05:43:45'),
(120, 13, 1, 'Almost 400 rivers flow into Lake Baikal.', 1.00, '2025-05-26 05:44:00', '2025-05-26 05:44:00'),
(121, 13, 1, 'Victoria Falls is taller than Niagara Falls.', 1.00, '2025-05-26 05:44:29', '2025-05-26 05:44:29'),
(122, 13, 1, 'Angel Falls is located in two countries.', 1.00, '2025-05-26 05:44:47', '2025-05-26 05:44:47'),
(123, 13, 1, 'Lake Baikal contains a lot of the Earth’s fresh water.', 1.00, '2025-05-26 05:45:03', '2025-05-26 05:45:03'),
(124, 13, 1, 'The Grand Canyon is a beautiful place.', 1.00, '2025-05-26 05:45:26', '2025-05-26 05:45:26'),
(125, 13, 3, 'We could_______shopping or sightseeing today.', 1.00, '2025-05-26 05:46:22', '2025-05-26 08:33:31'),
(126, 13, 3, 'I’m going to eat after I ________ my homework.', 1.00, '2025-05-26 05:46:46', '2025-05-26 05:46:46'),
(127, 13, 3, 'Tickets are on sale. Let’s________ a flight today.', 1.00, '2025-05-26 05:47:03', '2025-05-26 05:47:03'),
(128, 13, 3, 'He didn’t ________ an appointment with me.', 1.00, '2025-05-26 05:47:23', '2025-05-26 05:47:23'),
(129, 13, 3, 'Do you ________ your flat, or did you buy it?', 1.00, '2025-05-26 05:47:43', '2025-05-26 05:47:43'),
(130, 13, 1, 'She ___ any free time today.', 1.00, '2025-05-26 05:48:33', '2025-05-26 05:48:33'),
(131, 13, 1, 'My old flat was ___ than this one.', 1.00, '2025-05-26 05:48:59', '2025-05-26 05:48:59'),
(132, 13, 1, 'You shouldn’t ___that. It isn’t nice.', 1.00, '2025-05-26 05:49:46', '2025-05-26 05:49:46'),
(133, 13, 1, 'Has Phil ___ met anybody famous?', 1.00, '2025-05-26 05:50:07', '2025-05-26 05:50:07'),
(134, 13, 1, 'Always ___ the receipt.', 1.00, '2025-05-26 05:50:26', '2025-05-26 05:50:26'),
(135, 14, 1, 'Why do Ken and Lily need to make dinner?', 1.00, '2025-05-26 05:53:08', '2025-05-26 05:53:08'),
(136, 14, 1, 'What do they have for salad?', 1.00, '2025-05-26 05:53:34', '2025-05-26 05:53:34'),
(137, 14, 1, 'Why don’t they make hamburgers?', 1.00, '2025-05-26 05:54:08', '2025-05-26 05:54:08'),
(138, 14, 1, 'Why don’t they make soup?', 1.00, '2025-05-26 05:54:58', '2025-05-26 05:54:58'),
(139, 14, 1, 'What is Dad’s favourite meal?', 1.00, '2025-05-26 05:55:24', '2025-05-26 05:55:24'),
(140, 14, 1, 'Why don’t they make fish pie?', 1.00, '2025-05-26 05:55:55', '2025-05-26 05:55:55'),
(141, 14, 1, 'Why don’t they make pizza?', 1.00, '2025-05-26 05:58:12', '2025-05-26 05:58:12'),
(142, 14, 1, 'Why can’t they make an omelette?', 1.00, '2025-05-26 05:58:48', '2025-05-26 05:58:48'),
(143, 14, 1, 'What do they decide to make?', 1.00, '2025-05-26 05:59:30', '2025-05-26 05:59:30'),
(144, 14, 1, 'What will they use for the pasta sauce?', 1.00, '2025-05-26 06:00:01', '2025-05-26 06:00:01'),
(201, 19, 3, 'TASK OF PASSAGE 1\r\nDo the following statements agree with the information given in reading passage 1. \r\nChoose True or False. \r\nTrue- if the statement agrees with the information\r\nFalse- if the statement contradicts the information\r\nSocial media managers are important today because companies need to connect with customers online.', 2.00, '2025-05-27 07:04:37', '2025-05-27 07:04:37'),
(202, 19, 3, 'Game development is only used in the entertainment industry and has no other purpose.', 2.00, '2025-05-27 07:05:02', '2025-05-27 07:05:02'),
(203, 19, 3, 'Cybersecurity officers need to understand how hackers work to protect information.', 2.00, '2025-05-27 07:05:23', '2025-05-27 07:05:23'),
(204, 19, 3, 'The demand for healthcare professionals is decreasing because people are healthier now.', 2.00, '2025-05-27 07:05:46', '2025-05-27 07:05:46'),
(205, 19, 3, 'Asteroid mining engineers will help explore space to find resources like metal and water.', 2.00, '2025-05-27 07:06:08', '2025-05-27 07:06:08'),
(206, 19, 1, 'Choose the correct letter A or B.\r\nWho can work in the field of game design and development?', 2.00, '2025-05-27 07:06:47', '2025-05-27 07:07:44'),
(207, 19, 1, 'What do biomedical engineers apply to medicine?', 2.00, '2025-05-27 07:07:12', '2025-05-27 07:07:12'),
(208, 19, 1, 'What kind of jobs will always be necessary according to the passage?', 2.00, '2025-05-27 07:07:36', '2025-05-27 07:07:36'),
(209, 19, 1, 'Where are asteroids located in space?', 2.00, '2025-05-27 07:08:09', '2025-05-27 07:08:09'),
(210, 19, 1, 'What do astrophysicists study?', 2.00, '2025-05-27 07:08:29', '2025-05-27 07:08:29'),
(211, 19, 3, 'Complete the summary using the list of words below\r\nThe 21st century has brought many new and exciting job ____________', 2.00, '2025-05-27 07:09:38', '2025-05-27 07:09:38'),
(212, 19, 3, 'Some of these jobs didn’t exist before, especially those related to technology and space. Social media managers help companies connect with customers ______', 2.00, '2025-05-27 07:11:06', '2025-05-27 07:11:06'),
(213, 19, 3, 'Game designers and developers create games not just for fun but also for education and therapy. Cybersecurity officers protect _________  information from hackers.', 2.00, '2025-05-27 07:11:47', '2025-05-27 07:11:47'),
(214, 19, 3, 'In the health field, more dental assistants, doctors, and nurses are needed as the population grows and older workers retire. Biomedical engineers combine _____________ and engineering to improve medicine and help people live longer. Physical and occupational therapists support people after injuries.', 2.00, '2025-05-27 07:12:26', '2025-05-27 07:12:26'),
(215, 19, 3, 'Sustainability directors work to protect the ___________  by helping companies reduce waste. Jobs in space, like asteroid mining engineers and astrophysicists, are also growing as space exploration becomes more important.', 2.00, '2025-05-27 07:13:38', '2025-05-27 07:13:38'),
(216, 20, 3, 'Tasks of Passage 2\r\nDo the following statements agree with the information given in reading passage 2. \r\nChoose True or False. \r\nTrue- if the statement agrees with the information\r\nFalse- if the statement contradicts the information\r\n\r\nRandy Gardner stayed awake for 10 days and nights.', 2.00, '2025-05-27 07:14:54', '2025-05-27 08:14:19'),
(217, 20, 3, 'Randy had trouble reading and watching TV after 24 hours without sleep.', 2.00, '2025-05-27 07:15:17', '2025-05-27 08:14:39'),
(218, 20, 3, 'Gardner’s speech became clearer as he stayed awake longer.', 2.00, '2025-05-27 07:15:57', '2025-05-27 08:14:54'),
(219, 20, 3, 'Randy Gardner did his experiment to find out what happens when people sleep too much.', 2.00, '2025-05-27 07:16:20', '2025-05-27 08:15:21'),
(220, 20, 3, 'Sleep helps the brain and body to stay healthy.', 2.00, '2025-05-27 07:16:41', '2025-05-27 08:15:56'),
(221, 20, 3, 'Choose the correct heading for paragraph 1.', 2.00, '2025-05-27 07:25:43', '2025-05-27 07:25:43'),
(222, 20, 3, 'Choose the correct heading for paragraph 2.', 2.00, '2025-05-27 07:26:15', '2025-05-27 07:26:15'),
(223, 20, 3, 'Choose the correct heading for paragraph 3.', 2.00, '2025-05-27 07:26:36', '2025-05-27 07:26:36'),
(224, 20, 3, 'Choose the correct heading for paragraph 4.', 2.00, '2025-05-27 07:27:11', '2025-05-27 07:27:11'),
(225, 20, 3, 'Choose the correct heading for paragraph 5.', 2.00, '2025-05-27 07:27:37', '2025-05-27 07:27:37'),
(226, 21, 3, 'Tasks For Passage 1\r\nDo the following statements agree with the information given in reading passage 1. \r\nChoose True or False. \r\nTrue- if the statement agrees with the information\r\nFalse- if the statement contradicts the information\r\n\r\nThe climate is changing because of human activities.', 2.00, '2025-05-27 07:30:25', '2025-05-27 07:30:31'),
(227, 21, 3, 'Carbon dioxide is a gas that helps cool the Earth.', 2.00, '2025-05-27 07:31:02', '2025-05-27 07:31:02'),
(228, 21, 3, 'Some places may become wetter, drier, or windier because of climate change.', 2.00, '2025-05-27 07:31:21', '2025-05-27 07:31:21'),
(229, 21, 3, 'If we stop all greenhouse gas emissions today, the climate will stop changing immediately.', 2.00, '2025-05-27 07:31:46', '2025-05-27 07:31:46'),
(230, 21, 3, 'Almost every country wants to reduce greenhouse gas emissions.', 2.00, '2025-05-27 07:32:10', '2025-05-27 07:32:10'),
(231, 21, 1, 'Choose the correct letter A or B.\r\nWhat is the main reason for climate change?', 2.00, '2025-05-27 07:32:48', '2025-05-27 07:32:48'),
(232, 21, 1, 'Which gas is mainly responsible for warming the atmosphere?', 2.00, '2025-05-27 07:33:25', '2025-05-27 07:33:25'),
(233, 21, 1, 'What will happen to the Earth’s temperature even if we reduce emissions by 50%?', 1.00, '2025-05-27 07:34:24', '2025-05-27 07:34:24'),
(234, 21, 1, 'Why is the atmosphere becoming more energetic?', 2.00, '2025-05-27 07:34:50', '2025-05-27 07:34:50'),
(235, 21, 1, 'What is one effect of climate change mentioned in the text?', 2.00, '2025-05-27 07:35:10', '2025-05-27 07:35:10'),
(236, 21, 3, 'Complete the summary using the list of words, below.\r\nOur climate is changing, and it will continue to change for a long time. This is mostly because of human activities, like burning fossil fuels, which __________  greenhouse gases such as carbon dioxide into the atmosphere.', 1.00, '2025-05-27 07:36:21', '2025-05-27 07:36:21'),
(237, 21, 3, 'Carbon dioxide levels have increased a lot since before 1900, and this has caused the Earth’s temperature to rise. Scientists know that ___________  change is real because they see a strong connection between rising CO2 and rising temperatures.', 2.00, '2025-05-27 07:36:52', '2025-05-27 07:36:52'),
(238, 21, 3, 'But climate change is not just about heat. Some areas may become windier, wetter, or drier, and some may even get cooler. That’s why we say “climate change” instead of just “global ____________ ”', 2.00, '2025-05-27 07:47:50', '2025-05-27 07:47:50'),
(239, 21, 3, 'Even if we stop adding more greenhouse gases now, the Earth will still keep getting hotter for many years. However, if we act quickly and reduce ________  , we can manage the changes better and reduce the risks.', 2.00, '2025-05-27 07:48:33', '2025-05-27 07:48:33'),
(240, 21, 3, 'Greenhouse gases like carbon dioxide, methane, and nitrous oxide trap heat in the atmosphere. Carbon dioxide is the most important one, so reducing it is key. Countries around the world are working to cut emissions, and if they act together and act soon, the worst _________  of climate change can be avoided.', 2.00, '2025-05-27 07:49:01', '2025-05-27 07:49:01'),
(241, 22, 3, 'Tasks Of Passage 2\r\nDo the following statements agree with the information given in reading passage 2. \r\nChoose True or False. \r\nTrue- if the statement agrees with the information\r\nFalse- if the statement contradicts the information\r\n\r\nYesim reads her biology chapter on her laptop during the train ride.', 2.00, '2025-05-27 07:59:40', '2025-05-27 07:59:40'),
(242, 22, 3, 'Min-ho buys his movie tickets at the cinema.', 2.00, '2025-05-27 08:00:12', '2025-05-27 08:00:12'),
(243, 22, 3, 'Generation Z has never used the internet or mobile phones.', 2.00, '2025-05-27 08:00:32', '2025-05-27 08:00:32'),
(244, 22, 3, 'Generation Z is sometimes called Generation C because of their creativity and online community.', 2.00, '2025-05-27 08:00:49', '2025-05-27 08:00:49'),
(245, 22, 3, 'Valerie Chen is more concerned about her parents seeing her online activity than companies knowing about it.', 2.00, '2025-05-27 08:01:10', '2025-05-27 08:01:10'),
(246, 22, 3, 'Choose the correct heading for paragraph 1.', 2.00, '2025-05-27 08:01:41', '2025-05-27 08:01:41'),
(247, 22, 3, 'Choose the correct heading for paragraph 2.', 2.00, '2025-05-27 08:08:43', '2025-05-27 08:08:43'),
(248, 22, 3, 'Choose the correct heading for paragraph 3.', 2.00, '2025-05-27 08:09:19', '2025-05-27 08:09:19'),
(249, 22, 3, 'Choose the correct heading for paragraph 4.', 2.00, '2025-05-27 08:10:06', '2025-05-27 08:10:06'),
(250, 22, 3, 'Choose the correct heading for paragraph 5', 2.00, '2025-05-27 08:11:20', '2025-05-27 08:11:20');

-- --------------------------------------------------------

--
-- Table structure for table `question_types`
--

CREATE TABLE `question_types` (
  `id_quest_type` int(11) NOT NULL,
  `quest_type_name` varchar(50) NOT NULL,
  `question_var` enum('multiple','open','matching') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `question_types`
--

INSERT INTO `question_types` (`id_quest_type`, `quest_type_name`, `question_var`, `created_at`, `updated_at`) VALUES
(1, 'Çoxseçimli sual', 'multiple', '2025-05-14 12:05:12', '2025-05-14 12:05:12'),
(2, 'Açıq cavab', 'open', '2025-05-14 12:05:12', '2025-05-14 12:05:12'),
(3, 'Uyğunlaşdırma', 'matching', '2025-05-14 12:05:12', '2025-05-14 12:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `scores`
--

CREATE TABLE `scores` (
  `id_score` int(11) NOT NULL,
  `id_answer` int(11) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `datetime` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_group`
--

CREATE TABLE `student_group` (
  `id_student_group` int(11) NOT NULL,
  `group_number` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `student_group`
--

INSERT INTO `student_group` (`id_student_group`, `group_number`, `created_at`, `updated_at`) VALUES
(1, '102A', '2025-05-14 12:05:12', '2025-05-14 12:05:12'),
(2, '1022A', '2025-05-14 13:07:25', '2025-05-14 13:07:25');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id_subject` int(11) NOT NULL,
  `subjectname` varchar(100) NOT NULL,
  `timer` int(11) NOT NULL COMMENT 'Time in minutes',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id_subject`, `subjectname`, `timer`, `created_at`, `updated_at`) VALUES
(3, 'XDIAK 2', 60, '2025-05-24 15:14:53', '2025-05-24 15:14:53'),
(5, 'Oxu Vərdişləri', 80, '2025-05-27 06:53:28', '2025-05-27 06:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_users` int(11) NOT NULL,
  `f_name` varchar(100) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('admin','student') NOT NULL DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_users`, `f_name`, `group_id`, `username`, `password`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin', NULL, 'admin', 'admin123', 'admin', '2025-05-14 12:05:12', '2025-05-14 12:05:12'),
(2, 'Əli Məmmədov', 1, 'student', 'student123', 'student', '2025-05-14 12:05:12', '2025-05-14 12:05:12'),
(3, 'Rahib Imamaguluyev', 1, 'rahibImamguluyev1982', '$2y$10$p.UFBY/0Hi7vFAB.cRuryuneXVVwU4.vz71rgW1y97YOTOIpGZwTi', 'student', '2025-05-14 12:06:03', '2025-05-14 12:06:03'),
(5, 'Cavad HUseynli', 2, 'cavad123', '$2y$10$Aqm.h6mRMEKWRV33ZfEkt.7yjdjuLdNrSKrSxVXPVbnKl8Wef8P12', 'student', '2025-05-14 13:12:57', '2025-05-14 13:12:57'),
(6, 'Malik Murselov', 2, 'Malik123', '$2y$10$yBf5VSWmGtUGV6ASNEFe/e.1gdaf6Dd9LkiF4Se.Nbb7a.XxgR8fS', 'student', '2025-05-26 06:47:14', '2025-05-26 06:47:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id_answer`),
  ADD KEY `idx_exam` (`exam_id`),
  ADD KEY `idx_question` (`id_questions`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_correct` (`is_correct`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id_exam`),
  ADD KEY `idx_subject` (`id_subject`),
  ADD KEY `idx_group` (`id_student_group`),
  ADD KEY `idx_date` (`date_exam`);

--
-- Indexes for table `matching_questions`
--
ALTER TABLE `matching_questions`
  ADD PRIMARY KEY (`id_matching`),
  ADD KEY `idx_question` (`id_question_text`);

--
-- Indexes for table `multiple_questions`
--
ALTER TABLE `multiple_questions`
  ADD PRIMARY KEY (`id_multiple`),
  ADD KEY `idx_question` (`id_question_text`);

--
-- Indexes for table `open_questions`
--
ALTER TABLE `open_questions`
  ADD PRIMARY KEY (`id_open`),
  ADD KEY `idx_question` (`id_question_text`);

--
-- Indexes for table `question_files`
--
ALTER TABLE `question_files`
  ADD PRIMARY KEY (`id_read_quest_file`),
  ADD KEY `idx_file_type` (`file_type`),
  ADD KEY `idx_subject` (`subject_id`);

--
-- Indexes for table `question_read`
--
ALTER TABLE `question_read`
  ADD PRIMARY KEY (`id_question_text`),
  ADD KEY `idx_quest_file` (`id_read_quest_file`),
  ADD KEY `idx_quest_type` (`id_question_type`);

--
-- Indexes for table `question_types`
--
ALTER TABLE `question_types`
  ADD PRIMARY KEY (`id_quest_type`),
  ADD KEY `idx_question_var` (`question_var`);

--
-- Indexes for table `scores`
--
ALTER TABLE `scores`
  ADD PRIMARY KEY (`id_score`),
  ADD KEY `idx_answer` (`id_answer`);

--
-- Indexes for table `student_group`
--
ALTER TABLE `student_group`
  ADD PRIMARY KEY (`id_student_group`),
  ADD UNIQUE KEY `group_number` (`group_number`),
  ADD KEY `idx_group_number` (`group_number`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id_subject`),
  ADD KEY `idx_subjectname` (`subjectname`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_users`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_group` (`group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `id_answer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=446;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id_exam` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `matching_questions`
--
ALTER TABLE `matching_questions`
  MODIFY `id_matching` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `multiple_questions`
--
ALTER TABLE `multiple_questions`
  MODIFY `id_multiple` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `open_questions`
--
ALTER TABLE `open_questions`
  MODIFY `id_open` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `question_files`
--
ALTER TABLE `question_files`
  MODIFY `id_read_quest_file` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `question_read`
--
ALTER TABLE `question_read`
  MODIFY `id_question_text` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT for table `question_types`
--
ALTER TABLE `question_types`
  MODIFY `id_quest_type` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `scores`
--
ALTER TABLE `scores`
  MODIFY `id_score` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_group`
--
ALTER TABLE `student_group`
  MODIFY `id_student_group` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id_subject` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_users` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id_exam`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`id_questions`) REFERENCES `question_read` (`id_question_text`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_users`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`id_subject`) REFERENCES `subjects` (`id_subject`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`id_student_group`) REFERENCES `student_group` (`id_student_group`) ON DELETE CASCADE;

--
-- Constraints for table `matching_questions`
--
ALTER TABLE `matching_questions`
  ADD CONSTRAINT `matching_questions_ibfk_1` FOREIGN KEY (`id_question_text`) REFERENCES `question_read` (`id_question_text`) ON DELETE CASCADE;

--
-- Constraints for table `multiple_questions`
--
ALTER TABLE `multiple_questions`
  ADD CONSTRAINT `multiple_questions_ibfk_1` FOREIGN KEY (`id_question_text`) REFERENCES `question_read` (`id_question_text`) ON DELETE CASCADE;

--
-- Constraints for table `open_questions`
--
ALTER TABLE `open_questions`
  ADD CONSTRAINT `open_questions_ibfk_1` FOREIGN KEY (`id_question_text`) REFERENCES `question_read` (`id_question_text`) ON DELETE CASCADE;

--
-- Constraints for table `question_files`
--
ALTER TABLE `question_files`
  ADD CONSTRAINT `question_files_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id_subject`) ON DELETE CASCADE;

--
-- Constraints for table `question_read`
--
ALTER TABLE `question_read`
  ADD CONSTRAINT `question_read_ibfk_1` FOREIGN KEY (`id_read_quest_file`) REFERENCES `question_files` (`id_read_quest_file`) ON DELETE CASCADE,
  ADD CONSTRAINT `question_read_ibfk_2` FOREIGN KEY (`id_question_type`) REFERENCES `question_types` (`id_quest_type`) ON DELETE CASCADE;

--
-- Constraints for table `scores`
--
ALTER TABLE `scores`
  ADD CONSTRAINT `scores_ibfk_1` FOREIGN KEY (`id_answer`) REFERENCES `answers` (`id_answer`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `student_group` (`id_student_group`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
