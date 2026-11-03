-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: 03 نوفمبر 2026 الساعة 15:18
-- إصدار الخادم: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meras`
--

-- --------------------------------------------------------

--
-- بنية الجدول `achievement`
--

CREATE TABLE `achievement` (
  `achievement_id` int(11) NOT NULL,
  `university_id` varchar(10) NOT NULL,
  `club_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `achievement_type` varchar(50) NOT NULL,
  `achievement_date` date NOT NULL,
  `volunteer_hours` int(11) DEFAULT NULL,
  `certificate_file` varchar(255) DEFAULT NULL,
  `opportunity_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `application`
--

CREATE TABLE `application` (
  `application_id` int(11) NOT NULL,
  `university_id` varchar(10) NOT NULL,
  `club_id` int(11) NOT NULL,
  `opportunity_id` int(11) DEFAULT NULL,
  `application_date` date NOT NULL,
  `application_type` varchar(50) NOT NULL,
  `review_notes` text,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `application_tracking`
--

CREATE TABLE `application_tracking` (
  `tracking_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `club`
--

CREATE TABLE `club` (
  `club_id` int(11) NOT NULL,
  `club_name` varchar(75) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(75) NOT NULL,
  `logo` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `leaders_structure` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `club`
--

INSERT INTO `club` (`club_id`, `club_name`, `description`, `category`, `logo`, `is_active`, `leaders_structure`) VALUES
(2, 'نادي قوقل للمطورين', 'نادي يهتم بتطوير البرمجيات والتقنيات الحديثة.', 'تقني', 'gdg.png', 1, NULL),
(3, 'نادي الصيدلة', 'نادي يهتم بالمجالات الصيدلانية والتوعية الصحية.', 'صحي', 'pharmacy.png', 1, NULL),
(4, 'نادي رؤية 2030', 'نادي يهتم بمبادرات رؤية المملكة 2030.', 'ثقافي', 'vision2030.png', 1, NULL),
(5, 'نادي الابتكار وريادة الأعمال', 'نادي يهتم بالابتكار والمشاريع الريادية.', 'ريادي', 'innovation.png', 1, NULL),
(6, 'نادي الرياضات الإلكترونية', 'نادي يهتم بالرياضات الإلكترونية وأنشطتها.', 'تقني', 'esports.png', 1, NULL),
(7, 'نادي الدرونز والروبوت', 'نادي يهتم بالطائرات دون طيار والروبوتات.', 'تقني', 'robotics.png', 1, NULL),
(8, 'نادي المحاسبة', 'نادي يهتم بالمحاسبة وتطوير المهارات المهنية.', 'مهني', 'accounting.png', 1, NULL),
(9, 'نادي التسويق', 'نادي يهتم بالتسويق ومهاراته وتطبيقاته.', 'مهني', 'marketing.png', 1, NULL),
(10, 'نادي الطباعة ثلاثية الأبعاد', 'نادي يهتم بتقنيات الطباعة ثلاثية الأبعاد.', 'تقني', 'printing3d.png', 1, NULL),
(11, 'نادي طويق', 'نادي يهتم بتنمية المهارات التقنية.', 'تقني', 'tuwaiq.png', 1, NULL),
(12, 'نادي الهندسة الصناعية', 'نادي يهتم بالهندسة الصناعية وتطبيقاتها.', 'هندسي', 'industrial.png', 1, NULL),
(13, 'نادي تقنية المستقبل', 'نادي يهتم بالتقنيات المستقبلية والابتكار.', 'تقني', 'futuretech.png', 1, NULL),
(14, 'نادي ضياء', 'نادي يهتم بالأنشطة والمبادرات الطلابية.', 'طلابي', 'diyaa.png', 1, NULL),
(15, 'نادي الشراكة الطلابية', 'نادي طلابي يهتم بتعزيز الشراكات والتعاون.', 'طلابي', 'partnership.png', 1, NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `club_account`
--

CREATE TABLE `club_account` (
  `club_account_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `club_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- إرجاع أو استيراد بيانات الجدول `club_account`
--

INSERT INTO `club_account` (`club_account_id`, `email`, `password_hash`, `club_id`) VALUES
(1, 'gdg@meras.test', '$2y$12$93o.zmKcEmyaFCnBslsh3.aVQDnqjFQw7edBKnIjA8Ph2k2p0vpDm', 2),
(2, 'pharmacy@meras.test', '$2y$12$hOcnOLjIeya9Xzg1Yje2O.cji1G.0bgeQe7ZCdNr8b32doBsM0D2m', 3),
(3, 'vision2030@meras.test', '$2y$12$.fCEOpkAxr.BqR6Eu0.Uh.T8wZ1s91MqBSlMt5W5rJy994nJLW94i', 4),
(4, 'innovation@meras.test', '$2y$12$nflqG9.zJQoTOZ72fOoVaualND3ARX1CSMzetxUGRiS2oV5VddTce', 5),
(5, 'esports@meras.test', '$2y$12$6zFfnHwTvIwNhHAas4sIL.4fyXvqzX7qJDTgFl3ZfBER4hqEqOIPa', 6),
(6, 'robotics@meras.test', '$2y$12$v207ZT0C34EFybIS22PRrO/Gw7f2CoY/CUpX0KxhX3Q71OJjDYlp6', 7),
(7, 'accounting@meras.test', '$2y$12$sWed1KkGD40HBf2TC.B6JOtPBqktoOS3fJHvepPBNqpVGRHTt2R32', 8),
(8, 'marketing@meras.test', '$2y$12$9Zu5uTHwjiZKC3dE4GTNKOrO2r8pbpa0BRgCwrc4eKTLOR2sDD5Wy', 9),
(9, 'printing3d@meras.test', '$2y$12$bFJvt1HjROm.KBofIcA2O.ge7pabFEYxDB9JhK1fU8fqQWXxsTIs.', 10),
(10, 'tuwaiq@meras.test', '$2y$12$m6zhwADNwaVHMtbGSL6TEusGCNGSOigNDQM5vGXRrAKVwmMcuVIe6', 11),
(11, 'industrial@meras.test', '$2y$12$I9l.MAjllDWtSXc5xaJSaOC468ygwi2kqGnYiq2JqwgDtSQ5L4UYq', 12),
(12, 'futuretech@meras.test', '$2y$12$g93YA2xoUO6fKXG1n15vtuwcsvfKg0kHQKvZMJmYb85h.l/sXsoh.', 13),
(13, 'diyaa@meras.test', '$2y$12$I7fzyisNPzJHjxV6bziuEOYJ.rGMyDRd7Iv4J214hGmhlcdGD/1Q6', 14),
(14, 'partnership@meras.test', '$2y$12$6bqlXv.RzIbr..xZdIAs4OiO4gIuGFvbbv1YkcjLKapUHbEd8ctLG', 15);

-- --------------------------------------------------------

--
-- بنية الجدول `membership`
--

CREATE TABLE `membership` (
  `membership_id` int(11) NOT NULL,
  `university_id` varchar(10) NOT NULL,
  `club_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `joined_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `opportunity`
--

CREATE TABLE `opportunity` (
  `opportunity_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `title` varchar(75) NOT NULL,
  `description` text NOT NULL,
  `type` varchar(75) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `work_mode` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `location` varchar(75) NOT NULL,
  `status` varchar(50) NOT NULL,
  `required_skills` varchar(255) DEFAULT NULL,
  `registration_deadline` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `max_seats` int(11) NOT NULL,
  `main_tasks` text NOT NULL,
  `application_questions` text,
  `volunteer_hours` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `post`
--

CREATE TABLE `post` (
  `post_id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `media` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `requirement`
--

CREATE TABLE `requirement` (
  `requirement_id` int(11) NOT NULL,
  `opportunity_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `is_mandatory` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- بنية الجدول `student`
--

CREATE TABLE `student` (
  `university_id` varchar(10) NOT NULL,
  `full_name` varchar(75) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `major` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `bio` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievement`
--
ALTER TABLE `achievement`
  ADD PRIMARY KEY (`achievement_id`),
  ADD UNIQUE KEY `uq_achievement_student_opportunity` (`university_id`,`opportunity_id`),
  ADD KEY `idx_achievement_student` (`university_id`),
  ADD KEY `idx_achievement_club` (`club_id`),
  ADD KEY `fk_achievement_opportunity` (`opportunity_id`);

--
-- Indexes for table `application`
--
ALTER TABLE `application`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `idx_application_student` (`university_id`),
  ADD KEY `idx_application_club` (`club_id`),
  ADD KEY `idx_application_opportunity` (`opportunity_id`);

--
-- Indexes for table `application_tracking`
--
ALTER TABLE `application_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD UNIQUE KEY `uq_tracking_application` (`application_id`);

--
-- Indexes for table `club`
--
ALTER TABLE `club`
  ADD PRIMARY KEY (`club_id`);

--
-- Indexes for table `club_account`
--
ALTER TABLE `club_account`
  ADD PRIMARY KEY (`club_account_id`),
  ADD UNIQUE KEY `uq_club_account_email` (`email`),
  ADD UNIQUE KEY `uq_club_account_club` (`club_id`);

--
-- Indexes for table `membership`
--
ALTER TABLE `membership`
  ADD PRIMARY KEY (`membership_id`),
  ADD UNIQUE KEY `uq_membership_student_club` (`university_id`,`club_id`),
  ADD KEY `idx_membership_student` (`university_id`),
  ADD KEY `idx_membership_club` (`club_id`);

--
-- Indexes for table `opportunity`
--
ALTER TABLE `opportunity`
  ADD PRIMARY KEY (`opportunity_id`),
  ADD KEY `idx_opportunity_club` (`club_id`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_post_club` (`club_id`);

--
-- Indexes for table `requirement`
--
ALTER TABLE `requirement`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `idx_requirement_opportunity` (`opportunity_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`university_id`),
  ADD UNIQUE KEY `uq_student_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievement`
--
ALTER TABLE `achievement`
  MODIFY `achievement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `application`
--
ALTER TABLE `application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `application_tracking`
--
ALTER TABLE `application_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `club`
--
ALTER TABLE `club`
  MODIFY `club_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `club_account`
--
ALTER TABLE `club_account`
  MODIFY `club_account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `membership`
--
ALTER TABLE `membership`
  MODIFY `membership_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `opportunity`
--
ALTER TABLE `opportunity`
  MODIFY `opportunity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `requirement`
--
ALTER TABLE `requirement`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- قيود الجداول المحفوظة
--

--
-- القيود للجدول `achievement`
--
ALTER TABLE `achievement`
  ADD CONSTRAINT `fk_achievement_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`club_id`),
  ADD CONSTRAINT `fk_achievement_opportunity` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunity` (`opportunity_id`),
  ADD CONSTRAINT `fk_achievement_student` FOREIGN KEY (`university_id`) REFERENCES `student` (`university_id`);

--
-- القيود للجدول `application`
--
ALTER TABLE `application`
  ADD CONSTRAINT `fk_application_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`club_id`),
  ADD CONSTRAINT `fk_application_opportunity` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunity` (`opportunity_id`),
  ADD CONSTRAINT `fk_application_student` FOREIGN KEY (`university_id`) REFERENCES `student` (`university_id`);

--
-- القيود للجدول `application_tracking`
--
ALTER TABLE `application_tracking`
  ADD CONSTRAINT `fk_tracking_application` FOREIGN KEY (`application_id`) REFERENCES `application` (`application_id`);

--
-- القيود للجدول `club_account`
--
ALTER TABLE `club_account`
  ADD CONSTRAINT `fk_club_account_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`club_id`);

--
-- القيود للجدول `membership`
--
ALTER TABLE `membership`
  ADD CONSTRAINT `fk_membership_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`club_id`),
  ADD CONSTRAINT `fk_membership_student` FOREIGN KEY (`university_id`) REFERENCES `student` (`university_id`);

--
-- القيود للجدول `opportunity`
--
ALTER TABLE `opportunity`
  ADD CONSTRAINT `fk_opportunity_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`club_id`);

--
-- القيود للجدول `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_post_club` FOREIGN KEY (`club_id`) REFERENCES `club` (`club_id`);

--
-- القيود للجدول `requirement`
--
ALTER TABLE `requirement`
  ADD CONSTRAINT `fk_requirement_opportunity` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunity` (`opportunity_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
