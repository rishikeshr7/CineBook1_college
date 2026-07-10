-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 10, 2026 at 07:31 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cinebook_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password_hash`, `created_at`) VALUES
(1, 'admin@cinebook.com', '$2y$10$BFatd6ht0sBRWxwLNYs/a.7tviYxjuTbOQsA1mFl1TObQxHQX4fp.', '2026-05-18 10:17:50');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `showtime_id` int(11) NOT NULL,
  `seat_numbers` varchar(255) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Confirmed','Cancelled') DEFAULT 'Confirmed',
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `showtime_id`, `seat_numbers`, `total_amount`, `booking_date`, `status`, `refund_amount`, `cancelled_at`) VALUES
(4, 4, 17, 'B8,B9,D8,D9,J8,J9', 2434.32, '2026-06-26 15:08:09', 'Confirmed', NULL, NULL),
(5, 4, 17, 'H2,H3', 544.32, '2026-06-26 15:18:03', 'Confirmed', NULL, NULL),
(6, 7, 22, 'A1', 328.32, '2026-06-28 15:11:33', 'Confirmed', NULL, NULL),
(7, 9, 19, 'H10, H11', 88.00, '2026-05-03 06:30:00', 'Confirmed', NULL, NULL),
(8, 9, 20, 'G5, G6, G7', 140.00, '2026-05-03 07:00:00', 'Confirmed', NULL, NULL),
(9, 4, 37, 'A8,A9', 976.32, '2026-07-03 15:11:56', 'Cancelled', 300.00, '2026-07-03 15:33:41'),
(10, 9, 46, 'A8,A9', 1073.52, '2026-07-06 05:13:47', 'Confirmed', NULL, NULL),
(11, 9, 46, 'A8,A9', 1073.52, '2026-07-06 05:13:52', 'Confirmed', NULL, NULL),
(12, 9, 46, 'D7,D8,D9,D10,D11,D12,D13', 2542.32, '2026-07-06 05:21:34', 'Confirmed', NULL, NULL),
(13, 4, 47, 'A7,A8', 1354.32, '2026-07-06 05:53:07', 'Confirmed', NULL, NULL),
(14, 4, 47, 'A7,A8', 1354.32, '2026-07-06 05:59:34', 'Confirmed', NULL, NULL),
(15, 4, 47, 'A7,A8', 1354.32, '2026-07-06 06:02:40', 'Confirmed', NULL, NULL),
(16, 4, 47, 'A7,A8', 1354.32, '2026-07-06 06:07:11', 'Confirmed', NULL, NULL),
(17, 4, 47, 'A7,A8', 1354.32, '2026-07-06 06:07:17', 'Confirmed', NULL, NULL),
(18, 4, 47, 'A7,A8', 1354.32, '2026-07-06 06:10:28', 'Confirmed', NULL, NULL),
(19, 9, 48, 'A7,A8', 1354.32, '2026-07-06 08:27:07', 'Confirmed', NULL, NULL),
(20, 9, 48, 'A7,A8', 1354.32, '2026-07-06 08:30:13', 'Confirmed', NULL, NULL),
(21, 9, 48, 'A10,A11', 1548.72, '2026-07-06 08:34:02', 'Cancelled', 830.00, '2026-07-06 09:29:34'),
(22, 9, 48, 'G9,G10', 954.72, '2026-07-06 08:53:18', 'Cancelled', 580.00, '2026-07-06 09:29:18'),
(23, 9, 48, 'G9,G10', 954.72, '2026-07-06 09:24:06', 'Cancelled', 580.00, '2026-07-06 09:25:23'),
(24, 4, 54, 'A6,A7,A8,A9,A10,A11,A12', 4324.32, '2026-07-10 15:58:29', 'Confirmed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_food`
--

CREATE TABLE `booking_food` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `food_id` int(11) NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_food`
--

INSERT INTO `booking_food` (`id`, `booking_id`, `food_id`, `food_name`, `quantity`, `price`) VALUES
(6, 4, 9, 'Premium Combo', 1, 950.00),
(7, 5, 5, 'Pepsi (Large)', 1, 200.00),
(8, 7, 1, 'Popcorn (Large)', 1, 12.00),
(9, 7, 2, 'Pepsi (Medium)', 2, 5.00),
(10, 8, 3, 'Nachos', 2, 8.00),
(11, 8, 4, 'Coke (Large)', 3, 8.00),
(12, 9, 6, 'Nachos with Cheese', 1, 300.00),
(13, 10, 6, 'Cheese Popcorn (Large)', 1, 390.00),
(14, 11, 6, 'Cheese Popcorn (Large)', 1, 390.00),
(15, 12, 22, 'Premium Combo', 1, 950.00),
(16, 13, 23, 'Classic Combo', 1, 650.00),
(17, 14, 23, 'Classic Combo', 1, 650.00),
(18, 15, 23, 'Classic Combo', 1, 650.00),
(19, 16, 23, 'Classic Combo', 1, 650.00),
(20, 17, 23, 'Classic Combo', 1, 650.00),
(21, 18, 23, 'Classic Combo', 1, 650.00),
(22, 19, 23, 'Classic Combo', 1, 650.00),
(23, 20, 23, 'Classic Combo', 1, 650.00),
(24, 21, 19, 'Nachos with Cheese', 1, 300.00),
(25, 21, 20, 'Hot Dog', 1, 250.00),
(26, 21, 21, 'Chicken Nuggets', 1, 280.00),
(27, 22, 19, 'Nachos with Cheese', 1, 300.00),
(28, 22, 21, 'Chicken Nuggets', 1, 280.00),
(29, 23, 19, 'Nachos with Cheese', 1, 300.00),
(30, 23, 21, 'Chicken Nuggets', 1, 280.00),
(31, 24, 22, 'Premium Combo', 2, 950.00);

-- --------------------------------------------------------

--
-- Table structure for table `movies`
--

CREATE TABLE `movies` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `genre` varchar(255) NOT NULL,
  `language` varchar(255) DEFAULT NULL,
  `certification` varchar(10) DEFAULT NULL,
  `synopsis` text DEFAULT NULL,
  `director` varchar(255) DEFAULT NULL,
  `release_date` varchar(100) DEFAULT NULL,
  `rating` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `formats` varchar(255) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `poster_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_rerelease` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movies`
--

INSERT INTO `movies` (`id`, `title`, `duration`, `genre`, `language`, `certification`, `synopsis`, `director`, `release_date`, `rating`, `status`, `formats`, `trailer_url`, `poster_image`, `created_at`, `is_rerelease`) VALUES
(13, 'K.G.F: Chapter 2', '2h 48m', 'Action, Crime, Drama', 'Kannada, Hindi, Telugu, Tamil, Malayalam', 'UA', 'In the blood-soaked Kolar Gold Fields, Rocky\'s name strikes fear into his foes. While his allies look up to him, the government sees him as a threat to law and order. Rocky must battle threats from all sides for unchallenged supremacy.', 'Prashanth Neel', 'April 14, 2022', '8.3', 'Now Showing', 'IMAX, Standard', 'https://www.youtube.com/watch?v=JKa05nyUmuQ', 'uploads/posters/poster_6a39152a3af046.91445338.jpg', '2026-06-22 10:57:46', 1),
(14, 'Jawan', '2h 49m', 'Action, Thriller, Drama', 'Hindi, Tamil, Telugu', 'UA', 'A high-octane action thriller that outlines the emotional journey of a man who is set to rectify the wrongs in society, driven by a personal vendetta and a promise made years ago, while going up against a monstrous outlaw.', 'Atlee', 'September 7, 2023', '7.5', 'Now Showing', 'IMAX, 4DX, Standard', 'https://www.youtube.com/watch?v=MWOlnZSnXJo', 'uploads/posters/poster_6a3916435c36d7.74068123.webp', '2026-06-22 11:02:27', 1),
(15, 'Spider-Man: No Way Home', '2h 28m', 'Action, Adventure, Sci-Fi', 'English, Hindi, Tamil, Telugu', 'UA', 'With Spider-Man\'s identity now revealed, Peter asks Doctor Strange for help. When a spell goes wrong, dangerous foes from other worlds start to appear, forcing Peter to discover what it truly means to be Spider-Man.', 'Jon Watts', 'December 16, 2021', '8.2', 'Now Showing', 'IMAX 3D, Dolby Cinema, Standard', 'https://www.youtube.com/watch?v=JfVOs4VSpmA', 'uploads/posters/poster_6a39176c856401.74916895.jpg', '2026-06-22 11:07:24', 1),
(16, 'The Mandalorian and Grogu', '2h 15m', 'Sci-Fi, Action, Adventure', 'English', 'UA', 'Din Djarin and his apprentice Grogu embark on a new galactic adventure to secure the future of Mandalore while facing heavily armed remnant Imperial forces.', 'Jon Favreau', 'May 22, 2026', 'N/A', 'Now Showing', 'IMAX, Dolby Cinema, Standard', 'https://www.youtube.com/watch?v=IHWlvwu8t1w', 'uploads/posters/poster_6a3918bae18b70.83289417.webp', '2026-06-22 11:12:58', 0),
(17, 'Toy Story 5', '1h 45m', 'Animation, Kids & Family, Adventure', 'English', 'U', 'Woody, Buzz, and the rest of the gang reunite for a brand new adventure when they must navigate the challenges of children growing up in an increasingly digital world.', 'Andrew Stanton', 'June 19, 2026', 'N/A', 'Now Showing', '3D, IMAX, Standard', 'https://www.youtube.com/watch?v=c51ND9Hdbw0', 'uploads/posters/poster_6a3919abaa1354.90436971.jpg', '2026-06-22 11:16:59', 0),
(18, 'Main Vaapas Aaunga', '2h 22m', 'Drama, Romance', 'Hindi', 'UA', 'An aging grandfather clings to memories of a lost love from his youth while his grandson attempts to anchor him. Set across two different timelines, the film explores a deeply moving romance relived in the modern day.', 'Imtiaz Ali', 'June 11, 2026', '7.8', 'Now Showing', 'Standard', 'https://www.youtube.com/watch?v=PRUTWluKRW8', 'uploads/posters/poster_6a391bbbb2b890.33035763.jpg', '2026-06-22 11:25:47', 0),
(19, 'Hai Jawani Toh Ishq Hona Hai', '2h 18m', 'Romance, Comedy', 'Hindi', 'UA', 'Marking the fourth ultimate collaboration between Varun Dhawan and his hitmaker father David Dhawan, this film promises to revive the classic, energetic Bollywood rom-com era.', 'David Dhawan', 'June 4, 2026', '7.2', 'Now Showing', 'Standard', 'https://www.youtube.com/watch?v=rFOdIv1jwhc', 'uploads/posters/poster_6a391e9d40ea58.11251619.jpg', '2026-06-22 11:38:05', 0),
(20, 'Peddi', '2h 38m', 'Sports, Drama', 'Telugu, Hindi, Tamil', 'UA', 'A high-octane sports drama following a relentless man fighting against the odds to protect his legacy.', 'Sukumar', 'June 3, 2026', '8.5', 'Now Showing', 'IMAX, Standard', 'https://www.youtube.com/watch?v=noatJVfA22U', 'uploads/posters/poster_6a392167d7ac39.65285247.jpg', '2026-06-22 11:49:59', 0),
(21, 'Obsession', '1h 49m', 'Horror, Thriller', 'English', 'A', 'After breaking the mysterious \"One Wish Willow\" to win his crush\'s heart, a hopeless romantic gets exactly what he asked for, but soon discovers that some desires come at a dark, sinister price.', 'Curry Barker', 'May 15, 2026', '8.6', 'Now Showing', 'Standard', 'https://www.youtube.com/watch?v=gMC8kkwbIQQ', 'uploads/posters/poster_6a39245fcb8a50.21263171.jpg', '2026-06-22 12:02:39', 0),
(22, 'Spider-Man: Brand New Day', '2h 25m', 'Action, Adventure, Superhero', 'English', 'UA', 'Fighting crime full-time as Spider-Man in a world that doesn\'t remember him sparks a change in Peter Parker he may not have the power to control—but that transformation might be the only thing that can stop a powerful new threat to the city.', 'Destin Daniel Cretton', 'July 31, 2026', 'N/A', 'Coming Soon', 'IMAX, Standard, 3D', 'https://www.youtube.com/watch?v=62bIsvRcPv0', 'uploads/posters/poster_6a3926227a6e42.68230164.jpg', '2026-06-22 12:10:10', 0);

-- --------------------------------------------------------

--
-- Table structure for table `movie_cast`
--

CREATE TABLE `movie_cast` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `actor_name` varchar(255) NOT NULL,
  `character_name` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movie_cast`
--

INSERT INTO `movie_cast` (`id`, `movie_id`, `actor_name`, `character_name`, `profile_image`) VALUES
(34, 13, 'Yash', 'Rocky', 'uploads/cast/cast_6a39152a3c2858.65255251.jpg'),
(35, 13, 'Sanjay Dutt', 'Adheera', 'uploads/cast/cast_6a39152a3cd643.32387700.jpg'),
(36, 13, 'Raveena Tandon', 'Ramika Sen', 'uploads/cast/cast_6a39152a3d7dc9.94254614.jpg'),
(37, 14, 'Shah Rukh Khan', 'Vikram Rathore / Azad', 'uploads/cast/cast_6a3916435d4c98.66778937.jpg'),
(38, 14, 'Nayanthara', 'Narmada', 'uploads/cast/cast_6a3916435e6ae2.81585163.jpg'),
(39, 14, 'Vijay Sethupathi', 'Kalee', 'uploads/cast/cast_6a3916435fe2e6.13693037.jpg'),
(40, 15, 'Tom Holland', 'Peter Parker', 'uploads/cast/cast_6a39176c86b795.14799504.jpg'),
(41, 15, 'Zendaya', 'MJ', 'uploads/cast/cast_6a39176c878410.65124496.jpg'),
(42, 15, 'Willem Dafoe', 'Green Goblin', 'uploads/cast/cast_6a39176c885390.07097656.jpg'),
(52, 19, 'Varun Dhawan', 'Jass', 'uploads/cast/cast_6a391e9d421502.56443893.jpg'),
(53, 19, 'Pooja Hegde', 'Preet', 'uploads/cast/cast_6a391e9d42ebd8.10139303.jpg'),
(54, 19, 'Mrunal Thakur', 'Baani', 'uploads/cast/cast_6a391e9d43bc00.59866064.jpg'),
(55, 20, 'Ram Charan', 'Peddi', 'uploads/cast/cast_6a392167d8c684.41030057.jpg'),
(56, 20, 'Janhvi Kapoor', 'Female Lead', 'uploads/cast/cast_6a392167d9ef07.97779569.jpg'),
(57, 20, 'Shiva Rajkumar', 'Mentor', 'uploads/cast/cast_6a392167daaaf2.90148540.jpg'),
(58, 21, 'Michael Johnston', 'Bear', 'uploads/cast/cast_6a39245fccd4a0.59223911.jpg'),
(59, 21, 'Inde Navarrette', 'Nikki', 'uploads/cast/cast_6a39245fcda665.44444122.jpg'),
(60, 21, 'Cooper Tomlinson', 'Ian', 'uploads/cast/cast_6a39245fceebc4.61098492.jpg'),
(85, 22, 'Tom Holland', 'Peter Parker / Spider-Man', 'uploads/cast/cast_6a3926227b7ad2.52859758.jpg'),
(86, 22, 'Zendaya', 'MJ', 'uploads/cast/cast_6a3926227c4010.88170030.jpg'),
(87, 22, 'Jon Bernthal', 'Frank Castle / Punisher', 'uploads/cast/cast_6a3926227cf2d9.57472839.jpg'),
(88, 16, 'Pedro Pascal', 'Din Djarin', 'uploads/cast/cast_6a3918bae2caa4.95237254.jpg'),
(89, 16, 'Jeremy Allen White', 'Rotta the Hutt', 'uploads/cast/cast_6a3918bae3fbd8.57819862.jpg'),
(90, 16, 'Sigourney Weaver', 'TBA', 'uploads/cast/cast_6a3918bae49fa0.48979215.jpg'),
(91, 18, 'Diljit Dosanjh', 'Grandson', 'uploads/cast/cast_6a391bbbb3c7b6.79637649.jpg'),
(92, 18, 'Sharvari', 'Young Love', 'uploads/cast/cast_6a391bbbb4f055.80155654.jpg'),
(93, 18, 'Naseeruddin Shah', 'Grandfather', 'uploads/cast/cast_6a391bbbb5a4b3.53306796.jpg'),
(94, 17, 'Tom Hanks', 'Woody', 'uploads/cast/cast_6a3919abaaeca0.71813250.jpg'),
(95, 17, 'Tim Allen', 'Buzz Lightyear', 'uploads/cast/cast_6a3919abab85b9.83857556.jpg'),
(96, 17, 'Joan Cusack', 'Jessie', 'uploads/cast/cast_6a3919abac27d6.71283115.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `movie_crew`
--

CREATE TABLE `movie_crew` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `crew_name` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movie_crew`
--

INSERT INTO `movie_crew` (`id`, `movie_id`, `crew_name`, `role`, `profile_image`) VALUES
(23, 13, 'Prashanth Neel', 'Director', 'uploads/crew/crew_6a39152a3f3922.77039470.jpg'),
(24, 13, 'Bhuvan Gowda', 'Cinematographer', 'uploads/crew/crew_6a39152a3ff163.83321964.jpg'),
(25, 14, 'Atlee', 'Director', 'uploads/crew/crew_6a39164360d8a4.50065631.jpg'),
(26, 14, 'G.K. Vishnu', 'Cinematographer', 'uploads/crew/crew_6a391643618e57.97048874.jpg'),
(27, 15, 'Jon Watts', 'Director', 'uploads/crew/crew_6a39176c89a606.98724794.jpg'),
(28, 15, 'Mauro Fiore', 'Cinematographer', 'uploads/crew/crew_6a39176c8a52a4.37603896.jpg'),
(34, 19, 'David Dhawan', 'Director', 'uploads/crew/crew_6a391e9d44b130.39502883.jpg'),
(35, 20, 'Sukumar', 'Director', 'uploads/crew/crew_6a392167dbbad9.70353152.jpg'),
(36, 20, 'Devi Sri Prasad', 'Music Composer', 'uploads/crew/crew_6a392167dc8135.49787836.jpg'),
(37, 21, 'Curry Barker', 'Director, Writer', 'uploads/crew/crew_6a39245fcfb7d3.51880901.jpg'),
(38, 21, 'James Harris', 'Producer', 'uploads/crew/crew_6a39245fd05c95.69779238.jpg'),
(61, 22, 'Destin Daniel Cretton', 'Director', 'uploads/crew/crew_6a3926991a0eb8.19534653.jpg'),
(62, 22, 'Kevin Feige', 'Producer', 'uploads/crew/crew_6a3926991b2a52.60050895.jpg'),
(63, 22, 'Amy Pascal', 'Producer', 'uploads/crew/crew_6a3926991bf5a5.83413795.jpg'),
(64, 16, 'Jon Favreau', 'Director', 'uploads/crew/crew_6a3918bae5efa3.25172279.jpg'),
(65, 16, 'Dave Filoni', 'Writer/Producer', 'uploads/crew/crew_6a3918bae68f82.73703532.jpg'),
(66, 18, 'Imtiaz Ali', 'Director', 'uploads/crew/crew_6a391bbbb73570.92682857.jpg'),
(67, 17, 'Andrew Stanton', 'Director', 'uploads/crew/crew_6a3919abad6b49.49315063.jpg'),
(68, 17, 'Pete Docter', 'Producer', 'uploads/crew/crew_6a3919abae1dc5.86902552.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `movie_reviews`
--

CREATE TABLE `movie_reviews` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 10),
  `review_text` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `movie_reviews`
--

INSERT INTO `movie_reviews` (`id`, `movie_id`, `user_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 18, 7, 9, 'Amazing movie! Loved the action.', '2026-06-28 20:43:02');

-- --------------------------------------------------------

--
-- Table structure for table `movie_trailers`
--

CREATE TABLE `movie_trailers` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `language` varchar(50) NOT NULL,
  `trailer_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `movie_trailers`
--

INSERT INTO `movie_trailers` (`id`, `movie_id`, `language`, `trailer_url`) VALUES
(1, 18, 'Hindi', 'https://www.youtube.com/watch?v=A8590lswE4E'),
(2, 18, 'Tamil', 'https://www.youtube.com/watch?v=kYJzXv08iWc'),
(3, 17, 'Hindi', 'https://youtu.be/XCHjLot4Xi0?si=fMg0WNkaDUY3bZOP');

-- --------------------------------------------------------

--
-- Table structure for table `showtimes`
--

CREATE TABLE `showtimes` (
  `id` int(11) NOT NULL,
  `movie_id` int(11) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `theater_id` varchar(100) DEFAULT NULL,
  `screen_id` varchar(50) DEFAULT NULL,
  `format` varchar(50) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `show_date` date NOT NULL,
  `show_time` time NOT NULL,
  `total_seats` int(11) DEFAULT 0,
  `price_regular` decimal(10,2) DEFAULT NULL,
  `price_premium` decimal(10,2) DEFAULT NULL,
  `price_vip` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `city`, `theater_id`, `screen_id`, `format`, `language`, `show_date`, `show_time`, `total_seats`, `price_regular`, `price_premium`, `price_vip`, `created_at`) VALUES
(6, 19, 'Mumbai', 'Carnival Cinemas Mumbai', 'Audi 2', '2D', 'Hindi', '2026-06-23', '19:30:00', 200, 150.00, 200.00, 300.00, '2026-06-22 13:58:28'),
(7, 14, 'Mumbai', 'Carnival Cinemas Mumbai', 'Audi 2', '2D', 'Hindi', '2026-06-23', '10:30:00', 200, 150.00, 200.00, 300.00, '2026-06-22 13:59:15'),
(8, 21, 'Kolkata', 'PVR Cinemas', 'Audi 1', '2D', 'English', '2026-06-23', '17:05:00', 200, 150.00, 200.00, 300.00, '2026-06-22 13:59:55'),
(9, 18, 'Delhi', 'INOX Nehru Place', 'Audi 3', 'IMAX 2D', 'Hindi', '2026-06-29', '20:30:00', 200, 150.00, 200.00, 300.00, '2026-06-22 14:03:26'),
(10, 20, 'Bangalore', 'INOX Garuda Mall', 'Audi 5', '2D', 'Kannada', '2026-06-23', '13:15:00', 200, 150.00, 200.00, 300.00, '2026-06-22 14:04:18'),
(11, 15, 'Hyderabad', 'Cinepolis CCPL Mall', 'Audi 4', '3D', 'English', '2026-06-23', '16:05:00', 200, 150.00, 200.00, 300.00, '2026-06-22 14:05:13'),
(12, 21, 'Kolkata', 'INOX Quest Mall', 'Audi 2', '2D', 'English', '2026-06-24', '20:30:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:30:17'),
(13, 19, 'Kolkata', 'INOX Quest Mall', 'Audi 1', '2D', 'English', '2026-06-24', '18:05:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:31:09'),
(14, 18, 'Bangalore', 'Cinepolis ETA Mall', 'Audi 3', 'IMAX 2D', 'Hindi', '2026-06-25', '10:15:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:32:12'),
(15, 20, 'Bangalore', 'PVR IMAX Koramangala', 'Audi 4', 'IMAX 2D', 'Kannada', '2026-06-25', '14:10:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:33:19'),
(16, 14, 'Mumbai', 'Cinepolis Andheri', 'Audi 5', '2D', 'Hindi', '2026-06-24', '19:30:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:35:19'),
(17, 13, 'Pune', 'PVR Phoenix Mall', 'Audi 2', '2D', 'Hindi', '2026-06-26', '21:20:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:36:54'),
(18, 21, 'Hyderabad', 'PVR Inorbit Mall', 'Audi 1', '2D', 'English', '2026-06-26', '16:40:00', 200, 150.00, 200.00, 300.00, '2026-06-24 11:39:04'),
(19, 23, 'Mumbai', 'PVR Juhu', 'Audi 1', 'IMAX 70mm', 'English', '2026-05-03', '14:00:00', 150, 33.00, NULL, NULL, '2026-06-26 14:47:45'),
(20, 24, 'Mumbai', 'INOX Megaplex', 'Screen 2', 'IMAX 3D', 'English', '2026-05-03', '15:30:00', 180, 33.33, NULL, NULL, '2026-06-26 14:47:45'),
(21, 18, 'Bangalore', 'PVR Forum Mall', 'Audi 2', '2D', 'Hindi', '2026-06-28', '20:30:00', 200, 150.00, 200.00, 300.00, '2026-06-28 14:18:31'),
(22, 18, 'Mumbai', 'Carnival Cinemas Mumbai', 'Audi 1', '2D', 'Hindi', '2026-07-01', '18:00:00', 200, 150.00, 200.00, 300.00, '2026-06-28 14:19:08'),
(23, 15, 'Bangalore', 'PVR IMAX Koramangala', 'Audi 2', '2D', 'English', '2026-06-30', '19:45:00', 200, 150.00, 200.00, 300.00, '2026-06-30 13:20:18'),
(24, 18, 'Bangalore', 'PVR IMAX Koramangala', 'Audi 1', '2D', 'Hindi', '2026-06-30', '20:30:00', 200, 150.00, 200.00, 300.00, '2026-06-30 13:21:26'),
(25, 21, 'Kolkata', 'PVR South City', 'Audi 1', '2D', 'English', '2026-06-30', '20:25:00', 200, 150.00, 200.00, 300.00, '2026-06-30 14:54:39'),
(26, 19, 'Kolkata', 'PVR South City', 'Audi 2', '2D', 'Hindi', '2026-06-30', '21:15:00', 200, 150.00, 200.00, 300.00, '2026-06-30 15:08:24'),
(27, 21, 'Bangalore', 'Cinepolis ETA Mall', 'Audi 2', '2D', 'English', '2026-07-01', '20:25:00', 200, 150.00, 200.00, 300.00, '2026-07-01 14:09:52'),
(28, 21, 'Bangalore', 'Cinepolis, Bannerghatta', 'Audi 1', '3D', 'English', '2026-07-02', '18:00:00', 200, 150.00, 200.00, 300.00, '2026-07-02 10:17:04'),
(29, 18, 'Bangalore', 'Cinepolis, Bannerghatta', 'Audi 2', '2D', 'Hindi', '2026-07-02', '19:15:00', 200, 150.00, 200.00, 300.00, '2026-07-02 10:53:33'),
(30, 16, 'Kolkata', 'INOX South City', 'Audi 1', 'IMAX 3D', 'English', '2026-07-02', '20:05:00', 200, 150.00, 200.00, 300.00, '2026-07-02 12:17:55'),
(31, 17, 'Kolkata', 'INOX South City', 'Audi 2', '3D', 'English', '2026-07-02', '19:05:00', 200, 150.00, 200.00, 300.00, '2026-07-02 12:19:03'),
(32, 19, 'Bangalore', 'PVR IMAX, Koramangala', 'Audi 1', '2D', 'Hindi', '2026-07-02', '18:00:00', 200, 150.00, 200.00, 300.00, '2026-07-02 12:32:54'),
(33, 19, 'Mumbai', 'PVR ICON, Andheri', 'Audi 1', '2D', 'Hindi', '2026-07-03', '21:45:00', 200, 150.00, 200.00, 300.00, '2026-07-03 15:00:14'),
(34, 19, 'Mumbai', 'INOX Megaplex, Malad', 'Audi 1', '2D', 'Hindi', '2026-07-03', '21:05:00', 200, 150.00, 200.00, 300.00, '2026-07-03 15:01:38'),
(35, 21, 'Mumbai', 'PVR ICON, Andheri', 'Audi 2', '2D', 'English', '2026-07-03', '22:05:00', 200, 150.00, 200.00, 300.00, '2026-07-03 15:02:36'),
(36, 19, 'Mumbai', 'INOX Megaplex, Malad', 'Audi 1', '2D', 'Hindi', '2026-07-04', '12:30:00', 200, 150.00, 200.00, 300.00, '2026-07-03 15:05:49'),
(37, 19, 'Mumbai', 'INOX Megaplex, Malad', 'Audi 1', '2D', 'Hindi', '2026-07-04', '15:45:00', 200, 150.00, 200.00, 300.00, '2026-07-03 15:07:44'),
(38, 21, 'Mumbai', 'PVR ICON, Andheri', 'Audi 1', '2D', 'English', '2026-07-04', '20:05:00', 200, 150.00, 200.00, 300.00, '2026-07-04 14:24:16'),
(39, 21, 'Mumbai', 'Cinepolis, Kurla', 'Audi 1', '2D', 'English', '2026-07-04', '21:15:00', 200, 150.00, 200.00, 300.00, '2026-07-04 14:24:59'),
(40, 15, 'Mumbai', 'Sterling Cinema, Fort', 'Audi 1', '2D', 'English', '2026-07-04', '22:00:00', 200, 150.00, 200.00, 300.00, '2026-07-04 15:18:29'),
(41, 21, 'Mumbai', 'PVR ICON, Andheri', 'Audi 1', '2D', 'English', '2026-07-05', '14:45:00', 200, 150.00, 200.00, 300.00, '2026-07-04 18:06:12'),
(42, 21, 'Bangalore', 'Cinepolis, Bannerghatta', 'Audi 2', '3D', 'English', '2026-07-05', '16:05:00', 200, 100.00, 150.00, 300.00, '2026-07-05 00:01:19'),
(43, 19, 'Mumbai', 'PVR ICON, Andheri', 'Audi 1', '2D', 'Hindi', '2026-07-06', '10:05:00', 200, 150.00, 200.00, 300.00, '2026-07-06 03:09:53'),
(44, 14, 'Mumbai', 'PVR ICON, Andheri', 'Audi 2', '2D', 'Hindi', '2026-07-06', '10:00:00', 200, 150.00, 200.00, 300.00, '2026-07-06 03:12:03'),
(45, 13, 'Mumbai', 'PVR ICON, Andheri', 'Audi 3', '2D', 'Hindi', '2026-07-06', '10:10:00', 200, 150.00, 200.00, 300.00, '2026-07-06 03:13:53'),
(46, 18, 'Mumbai', 'PVR ICON, Andheri', 'Audi 4', '2D', 'Hindi', '2026-07-06', '10:30:00', 200, 150.00, 200.00, 300.00, '2026-07-06 03:15:32'),
(47, 21, 'Mumbai', 'PVR ICON, Andheri', 'Audi 5', '3D', 'English', '2026-07-06', '14:00:00', 192, 150.00, 200.00, 300.00, '2026-07-06 05:47:15'),
(48, 20, 'Mumbai', 'INOX Megaplex, Malad', 'Audi 1', '2D', 'Hindi', '2026-07-06', '15:20:00', 192, 150.00, 200.00, 300.00, '2026-07-06 06:26:58'),
(49, 15, 'Mumbai', 'INOX Megaplex, Malad', 'Audi 2', '3D', 'English', '2026-07-06', '15:30:00', 192, 150.00, 200.00, 300.00, '2026-07-06 06:28:56'),
(50, 19, 'Mumbai', 'PVR ICON, Andheri', 'Audi 1', '2D', 'Hindi', '2026-07-10', '21:45:00', 192, 150.00, 200.00, 300.00, '2026-07-10 15:47:24'),
(51, 14, 'Mumbai', 'PVR ICON, Andheri', 'Audi 2', '2D', 'Hindi', '2026-07-10', '21:30:00', 192, 150.00, 200.00, 300.00, '2026-07-10 15:47:58'),
(52, 13, 'Mumbai', 'PVR ICON, Andheri', 'Audi 3', '3D', 'Hindi', '2026-07-10', '21:30:00', 192, 150.00, 200.00, 300.00, '2026-07-10 15:48:34'),
(53, 18, 'Mumbai', 'PVR ICON, Andheri', 'Audi 4', '2D', 'Hindi', '2026-07-10', '22:05:00', 192, 150.00, 200.00, 300.00, '2026-07-10 15:49:13'),
(54, 21, 'Mumbai', 'PVR ICON, Andheri', 'Audi 5', '3D', 'English', '2026-07-10', '22:10:00', 192, 150.00, 200.00, 300.00, '2026-07-10 15:49:40'),
(55, 20, 'Mumbai', 'INOX Megaplex, Malad', 'Audi 1', '2D', 'Hindi', '2026-07-10', '22:00:00', 192, 150.00, 200.00, 300.00, '2026-07-10 15:50:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password`, `created_at`) VALUES
(1, 'John Doe', 'johndoe@gmail.com', '1234567890', '$2y$10$6luhu8me8veQ4K4xVMR/KO.8FmJoj8OgjN7IVlZy6TLCyEZXAfyoK', '2026-05-23 09:57:21'),
(4, 'John Singh', 'r@gmail.com', '9876543210', '$2y$10$3wyAUzdcz/f/s7H1LfAb5.FMMUJqApICRXKpbl4iIAqkZnl2FwcWq', '2026-06-26 15:06:29'),
(5, 'S', 's@gmail.com', '+1 234 567 8900', '$2y$10$pFwAIxuyr5sR25TtdwqMhurju7X98INIBhQjp5MsGdcXSQ3qNb5qC', '2026-06-26 15:26:30'),
(6, 'Review Tester', 'testreview@test.com', '1234567890', '$2y$10$GXxHoSCcxHQk7yXbn/ab0uNBmQYdfk9wFu/JNP/hyFka3X16vH1Ni', '2026-06-28 15:06:15'),
(7, 'Test Reviewer', 'testreviewer@test.com', '1234567890', '$2y$10$ziz4w1sgicQCZWL8WKi.beWA04RzO.m9pAnRJHvjnlMmrSn/gl35S', '2026-06-28 15:08:15'),
(8, 'John Doe', 'john.doe@example.com', '9876543210', '$2y$10$Mj06MW1d4EzeLa2xf.xlwuPnIW.9B7P5fZYNvBfZLT0SPtjqcHt7m', '2026-06-30 13:47:48'),
(9, 'CineBook Admin', 'admin@cinebook.com', '0000000000', '$2y$10$/ucQfct5gNXevuMk2/8lBeLci/CeMNwgAuEuxB8LKmqXqQxMu.Np6', '2026-07-01 20:09:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `showtime_id` (`showtime_id`);

--
-- Indexes for table `booking_food`
--
ALTER TABLE `booking_food`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `movie_cast`
--
ALTER TABLE `movie_cast`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `movie_crew`
--
ALTER TABLE `movie_crew`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `movie_reviews`
--
ALTER TABLE `movie_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_movie` (`user_id`,`movie_id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `movie_trailers`
--
ALTER TABLE `movie_trailers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`);

--
-- Indexes for table `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `booking_food`
--
ALTER TABLE `booking_food`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `movie_cast`
--
ALTER TABLE `movie_cast`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `movie_crew`
--
ALTER TABLE `movie_crew`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `movie_reviews`
--
ALTER TABLE `movie_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `movie_trailers`
--
ALTER TABLE `movie_trailers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `booking_food`
--
ALTER TABLE `booking_food`
  ADD CONSTRAINT `booking_food_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `movie_cast`
--
ALTER TABLE `movie_cast`
  ADD CONSTRAINT `movie_cast_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `movie_crew`
--
ALTER TABLE `movie_crew`
  ADD CONSTRAINT `movie_crew_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `movie_reviews`
--
ALTER TABLE `movie_reviews`
  ADD CONSTRAINT `movie_reviews_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movie_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `movie_trailers`
--
ALTER TABLE `movie_trailers`
  ADD CONSTRAINT `movie_trailers_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
