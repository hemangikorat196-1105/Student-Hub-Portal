-- ============================================================================
-- Student Hub Portal - Seed Data (Exactly 15 Records per Table)
-- Password for all accounts: Admin@123 (for admin) / Student@123 (for students)
-- Bcrypt Hash Admin@123:   $2y$10$eA7swyN6XZyunaFlQVFfEO8WvOePG7RO.LEQNNPHBovhTpeIk5o1O
-- Bcrypt Hash Student@123: $2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq
-- ============================================================================

USE `student_portal`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `audit_logs`;
TRUNCATE TABLE `registrations`;
TRUNCATE TABLE `events`;
TRUNCATE TABLE `students`;
TRUNCATE TABLE `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. USERS (Exactly 15 Records)
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@studenthub.edu', '$2y$10$eA7swyN6XZyunaFlQVFfEO8WvOePG7RO.LEQNNPHBovhTpeIk5o1O', 'admin', 'active', '2026-10-27 09:30:00', '2026-08-01 09:00:00'),
(2, '25CS035', 'hemangi.korat@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-27 08:45:00', '2026-08-05 10:15:00'),
(3, '25CS001', 'aarav.sharma@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-26 14:10:00', '2026-08-05 10:30:00'),
(4, '25IT012', 'diya.patel@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-25 11:20:00', '2026-08-06 11:00:00'),
(5, '25ME018', 'rohan.mehta@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-24 16:05:00', '2026-08-06 11:30:00'),
(6, '24CS022', 'ananya.singh@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-26 17:40:00', '2026-08-07 12:00:00'),
(7, '24EC014', 'kabir.joshi@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-23 10:15:00', '2026-08-07 14:00:00'),
(8, '26CS007', 'meera.shah@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-25 09:50:00', '2026-08-08 09:20:00'),
(9, '25CE011', 'vivaan.kapoor@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-22 15:30:00', '2026-08-08 10:00:00'),
(10, '24IT031', 'ishita.desai@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-26 13:00:00', '2026-08-09 11:15:00'),
(11, '26ME004', 'aditya.verma@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-21 18:25:00', '2026-08-09 13:45:00'),
(12, '25CS028', 'sara.khan@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-24 12:10:00', '2026-08-10 15:00:00'),
(13, '24CE019', 'dhruv.trivedi@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-23 14:40:00', '2026-08-11 16:30:00'),
(14, '26IT009', 'nisha.iyer@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-26 10:00:00', '2026-08-12 11:00:00'),
(15, '25EC026', 'yash.patel@studenthub.edu', '$2y$10$m0S8z7HNLb.vJseJdc4.n.Fk03qUg6j6Pu22FkLO/rR9pGsRH57Oq', 'student', 'active', '2026-10-25 15:15:00', '2026-08-12 14:20:00');

-- ----------------------------------------------------------------------------
-- 2. STUDENTS (Exactly 15 Records matching students.json)
-- ----------------------------------------------------------------------------
INSERT INTO `students` (`id`, `user_id`, `student_id`, `name`, `email`, `mobile`, `course`, `year`, `department`, `gender`, `cgpa`, `country`, `state`, `city`, `status`, `created_at`) VALUES
(1, 2, '25CS035', 'Hemangi Korat', 'hemangi.korat@studenthub.edu', '9876543210', 'B.Tech Computer Science', 2, 'Computer Science & Engineering', 'Female', 8.75, 'India', 'Gujarat', 'Ahmedabad', 'active', '2026-08-05 10:15:00'),
(2, 3, '25CS001', 'Aarav Sharma', 'aarav.sharma@studenthub.edu', '9876543211', 'B.Tech Computer Science', 3, 'Computer Science & Engineering', 'Male', 9.20, 'India', 'Maharashtra', 'Mumbai', 'active', '2026-08-05 10:30:00'),
(3, 4, '25IT012', 'Diya Patel', 'diya.patel@studenthub.edu', '9876543212', 'B.Tech Information Technology', 2, 'Information Technology', 'Female', 8.60, 'India', 'Gujarat', 'Surat', 'active', '2026-08-06 11:00:00'),
(4, 5, '25ME018', 'Rohan Mehta', 'rohan.mehta@studenthub.edu', '9876543213', 'B.Tech Mechanical Engineering', 2, 'Mechanical Engineering', 'Male', 8.10, 'India', 'Gujarat', 'Vadodara', 'active', '2026-08-06 11:30:00'),
(5, 6, '24CS022', 'Ananya Singh', 'ananya.singh@studenthub.edu', '9876543214', 'B.Tech Computer Science', 3, 'Computer Science & Engineering', 'Female', 9.45, 'India', 'Rajasthan', 'Jaipur', 'active', '2026-08-07 12:00:00'),
(6, 7, '24EC014', 'Kabir Joshi', 'kabir.joshi@studenthub.edu', '9876543215', 'B.Tech Electronics', 3, 'Electronics Engineering', 'Male', 8.35, 'India', 'Gujarat', 'Ahmedabad', 'active', '2026-08-07 14:00:00'),
(7, 8, '26CS007', 'Meera Shah', 'meera.shah@studenthub.edu', '9876543216', 'B.Tech Computer Science', 1, 'Computer Science & Engineering', 'Female', 8.90, 'India', 'Gujarat', 'Rajkot', 'active', '2026-08-08 09:20:00'),
(8, 9, '25CE011', 'Vivaan Kapoor', 'vivaan.kapoor@studenthub.edu', '9876543217', 'B.Tech Civil Engineering', 2, 'Civil Engineering', 'Male', 7.95, 'India', 'Delhi', 'New Delhi', 'active', '2026-08-08 10:00:00'),
(9, 10, '24IT031', 'Ishita Desai', 'ishita.desai@studenthub.edu', '9876543218', 'B.Tech Information Technology', 3, 'Information Technology', 'Female', 9.05, 'India', 'Gujarat', 'Anand', 'active', '2026-08-09 11:15:00'),
(10, 11, '26ME004', 'Aditya Verma', 'aditya.verma@studenthub.edu', '9876543219', 'B.Tech Mechanical Engineering', 1, 'Mechanical Engineering', 'Male', 7.80, 'India', 'Uttar Pradesh', 'Lucknow', 'active', '2026-08-09 13:45:00'),
(11, 12, '25CS028', 'Sara Khan', 'sara.khan@studenthub.edu', '9876543220', 'B.Tech Computer Science', 2, 'Computer Science & Engineering', 'Female', 8.95, 'India', 'Maharashtra', 'Pune', 'active', '2026-08-10 15:00:00'),
(12, 13, '24CE019', 'Dhruv Trivedi', 'dhruv.trivedi@studenthub.edu', '9876543221', 'B.Tech Civil Engineering', 3, 'Civil Engineering', 'Male', 8.25, 'India', 'Gujarat', 'Vadodara', 'active', '2026-08-11 16:30:00'),
(13, 14, '26IT009', 'Nisha Iyer', 'nisha.iyer@studenthub.edu', '9876543222', 'B.Tech Information Technology', 1, 'Information Technology', 'Female', 9.10, 'India', 'Karnataka', 'Bengaluru', 'active', '2026-08-12 11:00:00'),
(14, 15, '25EC026', 'Yash Patel', 'yash.patel@studenthub.edu', '9876543223', 'B.Tech Electronics', 2, 'Electronics Engineering', 'Male', 8.40, 'India', 'Gujarat', 'Nadiad', 'active', '2026-08-12 14:20:00'),
(15, NULL, '24CS040', 'Priya Nair', 'priya.nair@studenthub.edu', '9876543224', 'B.Tech Computer Science', 3, 'Computer Science & Engineering', 'Female', 9.30, 'India', 'Kerala', 'Kochi', 'active', '2026-08-13 10:00:00');

-- ----------------------------------------------------------------------------
-- 3. EVENTS (Exactly 15 Records matching events.json)
-- ----------------------------------------------------------------------------
INSERT INTO `events` (`id`, `title`, `category`, `event_date`, `time`, `venue`, `speaker`, `description`, `seats`, `poster`, `status`, `created_by`, `created_at`) VALUES
(1, 'Annual National Tech Summit 2026', 'Technical', '2026-09-25', '09:30 AM - 04:30 PM', 'Main Auditorium', 'Dr. Aris Thorne', 'Keynotes, AI demonstrations and student technology showcases.', 350, 'poster_tech_summit.jpg', 'open', 1, '2026-08-01 10:00:00'),
(2, 'Full-Stack Web Development Bootcamp', 'Workshop', '2026-09-28', '10:00 AM - 01:00 PM', 'Computer Lab 3', 'Prof. Hitesh Patel', 'Hands-on JavaScript, REST API and responsive web development workshop.', 60, 'poster_web_dev.jpg', 'upcoming', 1, '2026-08-02 11:00:00'),
(3, 'Inter-College Code Marathon', 'Technical', '2026-10-05', '11:00 AM - 05:00 PM', 'Software Lab 1', 'Coding Club Mentors', 'Competitive programming and algorithm debugging competition.', 120, 'poster_code_marathon.jpg', 'open', 1, '2026-08-03 14:00:00'),
(4, 'Cloud Computing and DevOps Workshop', 'Workshop', '2026-10-10', '10:30 AM - 02:30 PM', 'Innovation Lab', 'Neha Shah', 'Introduction to cloud deployment, containers and CI/CD practices.', 80, 'poster_cloud_devops.jpg', 'upcoming', 1, '2026-08-04 09:30:00'),
(5, 'Freshers Cultural Night', 'Cultural', '2026-10-15', '05:00 PM - 09:00 PM', 'Open Air Theatre', 'Student Council', 'Music, dance, drama and student performances for the new batch.', 500, 'poster_cultural_night.jpg', 'upcoming', 1, '2026-08-05 16:00:00'),
(6, 'AI and Machine Learning Seminar', 'Seminar', '2026-10-20', '10:00 AM - 12:30 PM', 'Seminar Hall A', 'Dr. Kavya Mehta', 'A practical introduction to machine learning workflows and responsible AI.', 180, 'poster_ai_seminar.jpg', 'upcoming', 1, '2026-08-06 10:15:00'),
(7, 'Cyber Security Awareness Day', 'Seminar', '2026-10-24', '09:00 AM - 03:00 PM', 'Main Auditorium', 'Cyber Cell Team', 'Sessions on phishing, passwords, privacy and safe digital practices.', 300, 'poster_cybersecurity.jpg', 'upcoming', 1, '2026-08-07 11:30:00'),
(8, 'Inter-Department Football Cup', 'Sports', '2026-10-29', '03:00 PM - 07:00 PM', 'College Ground', 'Sports Committee', 'Department-level football tournament for registered student teams.', 220, 'poster_football.jpg', 'open', 1, '2026-08-08 15:45:00'),
(9, 'Resume and Interview Masterclass', 'Career', '2026-11-03', '02:00 PM - 05:00 PM', 'Placement Cell', 'Career Development Team', 'Resume building, interview preparation and professional communication practice.', 100, 'poster_resume_masterclass.jpg', 'open', 1, '2026-08-09 12:00:00'),
(10, 'Research Paper Writing Workshop', 'Workshop', '2026-11-08', '10:00 AM - 01:00 PM', 'Library Conference Room', 'Research Support Cell', 'Learn research questions, literature review, methodology and paper structure.', 70, 'poster_research_paper.jpg', 'upcoming', 1, '2026-08-10 10:00:00'),
(11, 'Hackathon 2026', 'Technical', '2026-11-14', '09:00 AM - 09:00 PM', 'Innovation Hub', 'Developer Club', 'A twelve-hour team hackathon focused on real-world campus problems.', 150, 'poster_hackathon.jpg', 'open', 1, '2026-08-11 13:00:00'),
(12, 'Alumni Interaction Session', 'Career', '2026-11-19', '11:00 AM - 01:00 PM', 'Main Auditorium', 'Alumni Association', 'Alumni share career journeys, internships and industry experiences.', 250, 'poster_alumni.jpg', 'upcoming', 1, '2026-08-12 14:30:00'),
(13, 'Photography and Media Contest', 'Cultural', '2026-11-24', '10:00 AM - 04:00 PM', 'Student Activity Centre', 'Media Club', 'Campus photography and creative media competition with certificates.', 90, 'poster_photography.jpg', 'upcoming', 1, '2026-08-13 11:20:00'),
(14, 'Entrepreneurship Pitch Day', 'Career', '2026-12-02', '10:30 AM - 03:30 PM', 'Startup Centre', 'Innovation Cell', 'Student teams pitch startup ideas to mentors and invited entrepreneurs.', 75, 'poster_pitch_day.jpg', 'open', 1, '2026-08-14 16:00:00'),
(15, 'Annual Sports and Fitness Day', 'Sports', '2026-12-10', '08:00 AM - 05:00 PM', 'College Sports Complex', 'Physical Education Department', 'Athletics, fitness challenges and recreational activities for students.', 400, 'poster_sports_day.jpg', 'upcoming', 1, '2026-08-15 09:00:00');

-- ----------------------------------------------------------------------------
-- 4. REGISTRATIONS (Exactly 15 Records)
-- ----------------------------------------------------------------------------
INSERT INTO `registrations` (`id`, `student_id`, `event_id`, `registration_date`, `status`, `notes`) VALUES
(1, 1, 1, '2026-08-20 10:15:00', 'confirmed', 'Delegate pass approved'),
(2, 2, 1, '2026-08-20 11:30:00', 'confirmed', 'Technical keynote attendee'),
(3, 3, 2, '2026-08-21 09:00:00', 'confirmed', 'Seat allocated in Computer Lab 3'),
(4, 4, 3, '2026-08-22 14:20:00', 'confirmed', 'Team lead: Code Warriors'),
(5, 5, 3, '2026-08-22 14:25:00', 'confirmed', 'Competitive track participant'),
(6, 6, 4, '2026-08-23 16:40:00', 'confirmed', 'AWS sandbox credit issued'),
(7, 7, 5, '2026-08-24 10:10:00', 'confirmed', 'Cultural pass confirmed'),
(8, 8, 6, '2026-08-25 12:00:00', 'confirmed', 'Seat reserved in Hall A'),
(9, 9, 7, '2026-08-26 15:30:00', 'confirmed', 'Security quiz registration included'),
(10, 10, 8, '2026-08-27 11:45:00', 'confirmed', 'Civil department team member'),
(11, 11, 9, '2026-08-28 13:15:00', 'confirmed', 'Mock interview slot confirmed'),
(12, 12, 10, '2026-08-29 09:50:00', 'confirmed', 'Draft paper review requested'),
(13, 13, 11, '2026-08-30 14:00:00', 'confirmed', 'Team lead: Hack Innovators'),
(14, 14, 12, '2026-08-31 16:10:00', 'confirmed', 'Alumni networking registered'),
(15, 15, 14, '2026-09-01 10:30:00', 'confirmed', 'Startup pitch submission accepted');

-- ----------------------------------------------------------------------------
-- 5. AUDIT_LOGS (Exactly 15 Records)
-- ----------------------------------------------------------------------------
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'SYSTEM_INIT', 'Database schema created and initial admin configured', '127.0.0.1', 'System/Installer', '2026-08-01 09:00:00'),
(2, 1, 'CREATE_EVENT', 'Admin created event ID 1: Annual National Tech Summit 2026', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-01 10:00:00'),
(3, 1, 'UPLOAD_POSTER', 'Poster uploaded for event ID 1 (poster_tech_summit.jpg)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-01 10:05:00'),
(4, 2, 'REGISTER', 'New student registered: 25CS035 (Hemangi Korat)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-05 10:15:00'),
(5, 2, 'LOGIN', 'Student Hemangi Korat logged in successfully', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-05 10:16:00'),
(6, 1, 'CREATE_STUDENT', 'Admin added student ID 25CS001 (Aarav Sharma)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-05 10:30:00'),
(7, 1, 'UPDATE_STUDENT', 'Admin updated contact info for student 25IT012', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-06 11:05:00'),
(8, 2, 'EVENT_REGISTRATION', 'Student 25CS035 registered for Event 1 (Tech Summit)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-08-20 10:15:00'),
(9, 1, 'EXPORT_CSV', 'Admin exported student records to CSV', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-01 15:30:00'),
(10, 1, 'CREATE_EVENT', 'Admin created event ID 11: Hackathon 2026', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-10 11:00:00'),
(11, 1, 'SOFT_DELETE_STUDENT', 'Admin soft-deleted inactive student ID 15', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-15 16:45:00'),
(12, 1, 'RESTORE_STUDENT', 'Admin restored student ID 15 to active status', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-09-16 09:10:00'),
(13, NULL, 'UNAUTHORIZED_ACCESS', 'Guest user attempted to access restricted admin dashboard', '192.168.1.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5)', '2026-10-20 14:22:00'),
(14, 2, 'LOGOUT', 'Student 25CS035 logged out securely', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-10-26 18:00:00'),
(15, 1, 'LOGIN', 'Admin authenticated with session ID regeneration', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', '2026-10-27 09:30:00');
