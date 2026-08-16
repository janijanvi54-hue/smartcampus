<?php
/**
 * DSVV SmartCampus SQL generator.
 * Generates database/smartcampus.sql with schema and realistic DSVV (Dev Sanskriti
 * Vishwavidyalaya, Shantikunj Haridwar) infrastructure seed data.
 * Run: php database/generate_sql.php
 */

$out = [];

$out[] = "-- ============================================================";
$out[] = "-- DSVV SmartCampus - Campus Resource & Facility Management System";
$out[] = "-- Dev Sanskriti Vishwavidyalaya, Shantikunj, Haridwar";
$out[] = "-- Database schema + demo seed data";
$out[] = "-- Auto-generated. Install via phpMyAdmin import or:";
$out[] = "--   mysql -u root -p < database/smartcampus.sql";
$out[] = "-- ============================================================";

$out[] = "SET NAMES utf8mb4;";
$out[] = "SET FOREIGN_KEY_CHECKS = 0;";
$out[] = "DROP DATABASE IF EXISTS smartcampus;";
$out[] = "CREATE DATABASE smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
$out[] = "USE smartcampus;";
$out[] = "";

/* ------------------------------------------------------------------ */
/* TABLES                                                              */
/* ------------------------------------------------------------------ */

$out[] = "CREATE TABLE users (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  name VARCHAR(120) NOT NULL,";
$out[] = "  email VARCHAR(160) NOT NULL UNIQUE,";
$out[] = "  password VARCHAR(255) NOT NULL,";
$out[] = "  role ENUM('student','faculty','admin') NOT NULL DEFAULT 'student',";
$out[] = "  department VARCHAR(120) NULL,";
$out[] = "  user_identifier VARCHAR(50) NULL,";
$out[] = "  status ENUM('active','inactive') NOT NULL DEFAULT 'active',";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE resources (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  name VARCHAR(120) NOT NULL,";
$out[] = "  type ENUM('classroom','computer_lab','library','seminar_hall','meeting_room','study_room','auditorium','hostel','canteen','health_centre','guest_house','amenity') NOT NULL,";
$out[] = "  capacity INT UNSIGNED NOT NULL DEFAULT 0,";
$out[] = "  location VARCHAR(160) NOT NULL,";
$out[] = "  description TEXT NULL,";
$out[] = "  facilities TEXT NULL,";
$out[] = "  bookable_by ENUM('all','student','faculty','admin') NOT NULL DEFAULT 'all',";
$out[] = "  status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  KEY idx_type (type), KEY idx_status (status)";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE bookings (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  user_id INT UNSIGNED NOT NULL,";
$out[] = "  resource_id INT UNSIGNED NOT NULL,";
$out[] = "  date DATE NOT NULL,";
$out[] = "  start_time TIME NOT NULL,";
$out[] = "  end_time TIME NOT NULL,";
$out[] = "  expected_users INT UNSIGNED NOT NULL DEFAULT 0,";
$out[] = "  purpose VARCHAR(255) NULL,";
$out[] = "  status ENUM('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,";
$out[] = "  CONSTRAINT fk_bookings_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,";
$out[] = "  KEY idx_date (date), KEY idx_status (status), KEY idx_resource (resource_id, date)";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE usage_records (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  resource_id INT UNSIGNED NOT NULL,";
$out[] = "  date DATE NOT NULL,";
$out[] = "  start_time TIME NOT NULL,";
$out[] = "  end_time TIME NOT NULL,";
$out[] = "  users_count INT UNSIGNED NOT NULL DEFAULT 0,";
$out[] = "  utilization_percentage DECIMAL(6,2) NOT NULL DEFAULT 0,";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  CONSTRAINT fk_usage_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,";
$out[] = "  KEY idx_resource_date (resource_id, date)";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE notifications (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  user_id INT UNSIGNED NOT NULL,";
$out[] = "  title VARCHAR(160) NOT NULL,";
$out[] = "  message TEXT NULL,";
$out[] = "  type VARCHAR(40) NOT NULL DEFAULT 'info',";
$out[] = "  is_read TINYINT(1) NOT NULL DEFAULT 0,";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,";
$out[] = "  KEY idx_user_read (user_id, is_read)";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE complaints (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  user_id INT UNSIGNED NOT NULL,";
$out[] = "  resource_id INT UNSIGNED NULL,";
$out[] = "  category VARCHAR(60) NOT NULL,";
$out[] = "  description TEXT NOT NULL,";
$out[] = "  priority ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',";
$out[] = "  status ENUM('reported','in_progress','resolved') NOT NULL DEFAULT 'reported',";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  resolved_at DATETIME NULL,";
$out[] = "  CONSTRAINT fk_complaint_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,";
$out[] = "  CONSTRAINT fk_complaint_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL,";
$out[] = "  KEY idx_status (status)";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE announcements (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  title VARCHAR(160) NOT NULL,";
$out[] = "  message TEXT NOT NULL,";
$out[] = "  created_by INT UNSIGNED NOT NULL,";
$out[] = "  status ENUM('draft','published') NOT NULL DEFAULT 'draft',";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  CONSTRAINT fk_announce_author FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE recommendations (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  booking_id INT UNSIGNED NULL,";
$out[] = "  requested_resource_id INT UNSIGNED NOT NULL,";
$out[] = "  recommended_resource_id INT UNSIGNED NOT NULL,";
$out[] = "  reason TEXT NULL,";
$out[] = "  score DECIMAL(5,2) NOT NULL DEFAULT 0,";
$out[] = "  status ENUM('proposed','accepted','declined','booked') NOT NULL DEFAULT 'proposed',";
$out[] = "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,";
$out[] = "  CONSTRAINT fk_rec_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,";
$out[] = "  CONSTRAINT fk_rec_requested FOREIGN KEY (requested_resource_id) REFERENCES resources(id) ON DELETE CASCADE,";
$out[] = "  CONSTRAINT fk_rec_recommended FOREIGN KEY (recommended_resource_id) REFERENCES resources(id) ON DELETE CASCADE,";
$out[] = "  KEY idx_status (status)";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

$out[] = "CREATE TABLE settings (";
$out[] = "  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
$out[] = "  setting_key VARCHAR(60) NOT NULL UNIQUE,";
$out[] = "  setting_value VARCHAR(255) NULL,";
$out[] = "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
$out[] = ") ENGINE=InnoDB;";
$out[] = "";

/* ------------------------------------------------------------------ */
/* USERS                                                               */
/* ------------------------------------------------------------------ */

$hash = password_hash('Password123!', PASSWORD_DEFAULT);
$hash = "'" . $hash . "'";

$users = [
    ['Admin User',              'admin@smartcampus.local',   'admin',   'Administration',       'ADMIN001'],
    ['Prof. Dr. Anil Kumar',    'faculty@smartcampus.local', 'faculty', 'Yoga & Yogic Science', 'FAC001'],
    ['Rahul Verma',             'student@smartcampus.local', 'student', 'Computer Science',     'STU2026001'],
    ['Priya Sharma',            'priya.sharma@smartcampus.local', 'student', 'Ayurveda',        'STU2026002'],
    ['Aman Gupta',              'aman.gupta@smartcampus.local',  'student', 'Computer Science', 'STU2026003'],
    ['Dr. Meena Joshi',         'm.joshi@smartcampus.local',     'faculty', 'Sanskrit',         'FAC002'],
    ['Dr. Rajesh Bhatt',        'r.bhatt@smartcampus.local',     'faculty', 'Yoga Therapy',     'FAC003'],
    ['Sneha Patel',             'sneha.patel@smartcampus.local', 'student', 'Psychology',       'STU2026004'],
];

$out[] = "INSERT INTO users (name, email, password, role, department, user_identifier, status, created_at) VALUES";
$vals = [];
foreach ($users as $u) {
    $vals[] = "('{$u[0]}', '{$u[1]}', $hash, '{$u[2]}', '{$u[3]}', '{$u[4]}', 'active', NOW() - INTERVAL " . rand(30, 120) . " DAY)";
}
$out[] = implode(",\n", $vals) . ";";
$out[] = "";

/* ------------------------------------------------------------------ */
/* RESOURCES  — DSVV campus infrastructure                            */
/* ------------------------------------------------------------------ */

// Which roles may book each resource type:
//  - 'all'     : both students and faculty may book
//  - 'faculty' : only faculty may book (teaching / events)
//  - 'admin'   : managed by administration, not bookable by students/faculty
function bookable_by_for_type(string $type): string {
    return match ($type) {
        'classroom', 'seminar_hall', 'meeting_room', 'auditorium' => 'faculty',
        'computer_lab', 'library', 'study_room' => 'all',
        default => 'admin',
    };
}

$resources = [
    // ---- Auditoriums (6) ----
    ['Auditorium 1 (Air Cooled)', 'auditorium', 1300, 'Main Auditorium Complex', 'Air cooled auditorium with full modern ICT facilities.', 'PA System, Audio-Visual System, LCD Projector, Cyclorama'],
    ['Auditorium 2', 'auditorium', 400, 'Auditorium Complex', 'Auditorium with modern ICT facilities.', 'LCD Projector, PA System, Audio-Visual System'],
    ['Auditorium 3', 'auditorium', 400, 'Auditorium Complex', 'Auditorium with modern ICT facilities.', 'LCD Projector, PA System, Audio-Visual System'],
    ['Auditorium 4', 'auditorium', 200, 'Auditorium Complex', 'Auditorium with modern ICT facilities.', 'LCD Projector, PA System, Audio-Visual System'],
    ['Auditorium 5', 'auditorium', 200, 'Auditorium Complex', 'Auditorium with modern ICT facilities.', 'LCD Projector, PA System, Audio-Visual System'],
    ['Auditorium 6', 'auditorium', 120, 'Auditorium Complex', 'Auditorium with modern ICT facilities.', 'LCD Projector, PA System, Audio-Visual System'],

    // ---- Seminar Halls (3) ----
    ['Seminar Hall 1', 'seminar_hall', 100, 'Academic Block, First Floor', 'Seminar hall with full ICT facilities.', 'LCD Projector, Audio System, Wi-Fi'],
    ['Seminar Hall 2', 'seminar_hall', 80, 'Academic Block, First Floor', 'Seminar hall with full ICT facilities.', 'LCD Projector, Audio System, Wi-Fi'],
    ['Seminar Hall 3', 'seminar_hall', 60, 'Academic Block, Second Floor', 'Seminar hall with full ICT facilities.', 'LCD Projector, Audio System, Wi-Fi'],

    // ---- Smart Classrooms ----
    ['Smart Classroom 1', 'classroom', 60, 'Academic Block, Ground Floor', 'Smart classroom with digital teaching aids.', 'Smart Board, LCD Projector, AC, Wi-Fi'],
    ['Smart Classroom 2', 'classroom', 50, 'Academic Block, Ground Floor', 'Smart classroom with digital teaching aids.', 'Smart Board, LCD Projector, AC, Wi-Fi'],
    ['Smart Classroom 3', 'classroom', 45, 'Academic Block, First Floor', 'Smart classroom with digital teaching aids.', 'Smart Board, LCD Projector, AC, Wi-Fi'],

    // ---- Computer Labs ----
    ['Commercial Computer Lab', 'computer_lab', 60, 'Computer Centre', 'Commercial computer lab open for all courses and practice.', '60 PCs, LCD Projector, AC, Wi-Fi, Software Suite'],
    ['Computer Lab 1', 'computer_lab', 50, 'Computer Centre', 'General purpose computer lab.', '50 PCs, LCD Projector, AC, Wi-Fi'],
    ['Computer Lab 2', 'computer_lab', 40, 'Computer Centre', 'Advanced computing lab.', '40 PCs, LCD Projector, AC, GPU Workstations'],
    ['Computer Lab 3', 'computer_lab', 50, 'Computer Centre', 'Advanced computing lab for AI & data science courses.', '50 PCs, LCD Projector, AC, High-Speed Network'],

    // ---- Library ----
    ['Central Library', 'library', 300, 'Central Library Block', 'Central library with reading halls, journals and reference desk.', 'Reading Halls, Wi-Fi, AC, Reference Desk, Daily Newspapers & Magazines'],

    // ---- Study Rooms ----
    ['Study Room 1', 'study_room', 24, 'Central Library, First Floor', 'Group study room.', 'Tables, Chairs, Wi-Fi, AC'],
    ['Study Room 2', 'study_room', 20, 'Central Library, Second Floor', 'Group study room.', 'Tables, Chairs, Wi-Fi, AC'],

    // ---- Meeting Rooms ----
    ['Meeting Room 1', 'meeting_room', 12, 'Administrative Block', 'Departmental meeting room.', 'Conference Table, TV Screen, Wi-Fi'],
    ['Meeting Room 2', 'meeting_room', 10, 'Administrative Block', 'Departmental meeting room.', 'Conference Table, TV Screen, Wi-Fi'],

    // ---- Hostels (Boys) ----
    ['Panini Bhawan (Boys)', 'hostel', 250, 'Boys Hostel Complex', 'Boys hostel with common facilities and dining hall.', 'Wi-Fi, Common Room with TV, Newspapers, Water Coolers, Hot Water, Launderette, Dining Hall (400)'],
    ['Arvind Bhawan (Boys)', 'hostel', 200, 'Boys Hostel Complex', 'Boys hostel with common facilities and dining hall.', 'Wi-Fi, Common Room with TV, Newspapers, Water Coolers, Hot Water, Launderette, Dining Hall (400)'],
    ['International Hostel (Boys)', 'hostel', 50, 'International Hostel Block', 'International boys hostel.', 'Wi-Fi, Cooking Facilities, Washing Machine, RO System'],

    // ---- Hostels (Girls) ----
    ['Sanghmitra Bhawan (Girls)', 'hostel', 300, 'Girls Hostel Complex', 'Girls hostel with common facilities and dining hall.', 'Wi-Fi, Common Room with TV, Newspapers, Water Coolers, Hot Water, Launderette, Dining Hall (400)'],
    ['Nivedita Bhawan (Girls)', 'hostel', 250, 'Girls Hostel Complex', 'Girls hostel with common facilities and dining hall.', 'Wi-Fi, Common Room with TV, Newspapers, Water Coolers, Hot Water, Launderette, Dining Hall (400)'],
    ['International Hostel (Girls)', 'hostel', 50, 'International Hostel Block', 'International girls hostel.', 'Wi-Fi, Cooking Facilities, Washing Machine, Hot Water, RO System'],

    // ---- Working Women Hostel + Faculty residences ----
    ['Working Women Hostel', 'hostel', 50, 'Hostel Complex', 'Separate hostel facility for 50 working women.', 'Wi-Fi, Common Room, Water Purifiers, Hot Water'],
    ['Shaunak Bhawan', 'hostel', 40, 'Faculty Residence', 'Free faculty accommodation block.', 'Wi-Fi, Free Mobile, Free Water & Electricity, Medical'],
    ['Sandipani Bhawan', 'hostel', 40, 'Faculty Residence', 'Free faculty accommodation block.', 'Wi-Fi, Free Mobile, Free Water & Electricity, Medical'],
    ['Gautam Bhawan', 'hostel', 40, 'Faculty Residence', 'Free faculty accommodation block.', 'Wi-Fi, Free Mobile, Free Water & Electricity, Medical'],
    ['Agastya Bhawan', 'hostel', 40, 'Faculty Residence', 'Free faculty accommodation block.', 'Wi-Fi, Free Mobile, Free Water & Electricity, Medical'],
    ['Kanva Bhawan', 'hostel', 40, 'Faculty Residence', 'Free faculty accommodation block.', 'Wi-Fi, Free Mobile, Free Water & Electricity, Medical'],

    // ---- Cafeterias (3) ----
    ['Annapurna Canteen', 'canteen', 150, 'Cafeteria Complex', 'Canteen with separate area for faculty.', 'Dining Area, Purified Water, Wi-Fi'],
    ['Anandmayee Canteen', 'canteen', 120, 'Cafeteria Complex', 'Canteen with separate area for faculty.', 'Dining Area, Purified Water, Wi-Fi'],
    ['Jagdamba Bhojanalaya', 'canteen', 150, 'Cafeteria Complex', 'Canteen with separate area for faculty.', 'Dining Area, Purified Water, Wi-Fi'],

    // ---- Health Centres ----
    ['Triage & Assessment Centre', 'health_centre', 10, 'Health Centre Complex', 'Dispensary, pharmacy and initial emergency treatment centre.', 'Dispensary, Pharmacy, 4 Emergency Beds'],
    ['Outpatient Clinic', 'health_centre', 20, 'Health Centre Complex', 'OPD with on-site physicians and Ayurvedic consultants.', '2 Physicians, 3 Ayurvedic Consultants'],
    ['Physiotherapy Centre', 'health_centre', 12, 'Health Centre Complex', 'Physiotherapy services.', 'Wax Bath, Ultrasound, IFT, Short Wave Diathermy, Traction'],
    ['Yoga Arogya Polyclinic', 'health_centre', 15, 'Health Centre Complex', 'Specialist yoga therapy polyclinic.', 'Yoga Therapy, Marma Therapy, Acupressure, Yagya Therapy, Pranic Healing'],
    ['Psychological Disorders Clinic', 'health_centre', 8, 'Health Centre Complex', 'Clinic with counselling rooms for psychological therapies.', 'Counselling Rooms'],
    ['Naturopathy Centre', 'health_centre', 10, 'Health Centre Complex', 'Natural treatment centre.', 'Steam Bath, Spinal Bath, Hip Bath, Sun Bath, Mud Therapy, Massage'],
    ['Panchakarma Centre', 'health_centre', 10, 'Health Centre Complex', 'Ayurvedic panchakarma treatments.', 'Massage, Shirodhara, Vaman, Vasti, Nasya'],
    ['50-Bed Multi-Speciality Hospital', 'health_centre', 50, 'Shantikunj Campus', 'Multi-speciality hospital with free health care for staff and students.', 'OPD, IPD, Emergency, Pharmacy, Ambulance'],

    // ---- Guest House ----
    ['Guest House', 'guest_house', 42, 'Guest House Block', 'Guest house with 7 flat systems, each with 3 double bedrooms.', 'Wi-Fi, Dining, Hot Water, RO Water, Launderette, Computer, Printing, Photocopying'],

    // ---- Amenities ----
    ['Pragyeshwar Mahadev Temple', 'amenity', 500, 'Central Campus', 'Central temple with amphitheatre.', 'Amphitheatre, Prayer Hall'],
    ['Smriti Upwan & Herbal Garden', 'amenity', 200, 'Campus South', 'Memorial garden with herbal garden and acupressure path.', 'Herbal Garden, Acupressure Path, Natural Products Stall'],
    ['Solar Observatory & Vedhshala', 'amenity', 30, 'Campus East', 'Solar observatory and astronomy centre.', 'Telescopes, Observatory'],
    ['Cattle Farm', 'amenity', 50, 'Farm Area', 'Cattle farm with 220 cattle.', 'Gobar Gas Plant, Organic Manure System'],
    ['Gayatri Vidyapeeth School', 'amenity', 1200, 'Campus North', 'CBSE secondary education school on-site (capacity 1200).', 'Classrooms, Playground, Library'],
    ['Railway Reservation Counter', 'amenity', 10, 'Shantikunj Premises', 'Railway reservation counter.', 'Reservation Desk'],
    ['Srijna (Earn while Learn)', 'amenity', 30, 'Campus West', 'Training centre for knitting, sewing, embroidery, greeting cards and sweets making.', 'Training Rooms'],
    ['Yajnashala', 'amenity', 100, 'Campus Central', 'Yajna facility for ceremonies and sessions.', 'Yajna Hall'],
    ['Prakritik Ahar Kendra', 'amenity', 40, 'Campus Central', 'Natural food centre.', 'Organic Food Outlet'],
    ['Grocery Store', 'amenity', 20, 'Campus Central', 'Campus grocery store.', 'General Store'],
];

$out[] = "INSERT INTO resources (name, type, capacity, location, description, facilities, status, bookable_by, created_at) VALUES";
$vals = [];
foreach ($resources as $r) {
    $vals[] = "('{$r[0]}', '{$r[1]}', {$r[2]}, '{$r[3]}', '{$r[4]}', '{$r[5]}', 'active', '" . bookable_by_for_type($r[1]) . "', NOW() - INTERVAL " . rand(30, 200) . " DAY)";
}
$out[] = implode(",\n", $vals) . ";";
$out[] = "";

/* ------------------------------------------------------------------ */
/* SETTINGS                                                            */
/* ------------------------------------------------------------------ */

$out[] = "INSERT INTO settings (setting_key, setting_value) VALUES";
$out[] = "('under_utilized_threshold', '30'),";
$out[] = "('normal_threshold', '70'),";
$out[] = "('overcrowded_threshold', '100'),";
$out[] = "('auto_approve_bookings', '0');";
$out[] = "";

/* ------------------------------------------------------------------ */
/* ANNOUNCEMENTS                                                       */
/* ------------------------------------------------------------------ */

$out[] = "INSERT INTO announcements (title, message, created_by, status, created_at) VALUES";
$out[] = "('DSVV SmartCampus is live!', 'DSVV resource booking is now available to all students and faculty. Log in and book auditoriums, seminar halls, computer labs and study spaces online.', 1, 'published', NOW() - INTERVAL 12 DAY),";
$out[] = "('Mid-semester exam scheduling', 'Classrooms and seminar halls will have reduced availability during exam week. Book well in advance to secure your preferred venue.', 1, 'published', NOW() - INTERVAL 6 DAY),";
$out[] = "('Commercial Computer Lab maintenance', 'The Commercial Computer Lab will be under maintenance this Saturday from 9 AM to 1 PM. Please use Computer Lab 3 during that window.', 1, 'published', NOW() - INTERVAL 2 DAY),";
$out[] = "('Yoga & Ayurveda week', 'The Yoga Arogya Polyclinic and Naturopathy Centre are offering special sessions next week. Contact the Health Centre for appointments.', 1, 'draft', NOW() - INTERVAL 1 DAY);";
$out[] = "";

/* ------------------------------------------------------------------ */
/* USAGE RECORDS (last 60 days)                                        */
/* ------------------------------------------------------------------ */

mt_srand(20260815);

// resource config: name => [capacity, base_prob, user_frac]
// Keeps the demo story: Commercial Computer Lab overcrowded, Computer Lab 3 moderate (~38%).
$usageCfg = [
    'Auditorium 1 (Air Cooled)' => [1300, 0.42, 0.65],
    'Auditorium 2'              => [400,  0.28, 0.55],
    'Auditorium 3'              => [400,  0.22, 0.45],
    'Auditorium 4'              => [200,  0.18, 0.40],
    'Auditorium 5'              => [200,  0.15, 0.35],
    'Auditorium 6'              => [120,  0.12, 0.30],
    'Seminar Hall 1'            => [100,  0.55, 0.70],
    'Seminar Hall 2'            => [80,   0.45, 0.60],
    'Seminar Hall 3'            => [60,   0.35, 0.50],
    'Smart Classroom 1'         => [60,   0.70, 0.80],
    'Smart Classroom 2'         => [50,   0.60, 0.75],
    'Smart Classroom 3'         => [45,   0.50, 0.70],
    'Commercial Computer Lab'   => [60,   0.80, 1.05],   // overcrowded
    'Computer Lab 1'            => [50,   0.60, 0.80],
    'Computer Lab 2'            => [40,   0.55, 0.75],
    'Computer Lab 3'            => [50,   0.45, 0.40],   // moderate (~38%)
    'Central Library'           => [300,  0.95, 0.70],
    'Study Room 1'              => [24,   0.60, 0.80],
    'Study Room 2'              => [20,   0.55, 0.75],
    'Meeting Room 1'            => [12,   0.45, 0.70],
    'Meeting Room 2'            => [10,   0.35, 0.60],
    'Panini Bhawan (Boys)'      => [250,  0.75, 0.85],
    'Arvind Bhawan (Boys)'      => [200,  0.70, 0.80],
    'International Hostel (Boys)' => [50, 0.50, 0.70],
    'Sanghmitra Bhawan (Girls)' => [300,  0.78, 0.85],
    'Nivedita Bhawan (Girls)'   => [250,  0.72, 0.80],
    'International Hostel (Girls)' => [50, 0.50, 0.70],
    'Working Women Hostel'      => [50,   0.30, 0.55],
    'Shaunak Bhawan'            => [40,   0.30, 0.30],   // under-utilized
    'Sandipani Bhawan'          => [40,   0.35, 0.40],
    'Gautam Bhawan'             => [40,   0.30, 0.35],
    'Agastya Bhawan'            => [40,   0.35, 0.45],
    'Kanva Bhawan'              => [40,   0.30, 0.40],
    'Annapurna Canteen'         => [150,  0.85, 0.80],
    'Anandmayee Canteen'        => [120,  0.75, 0.75],
    'Jagdamba Bhojanalaya'      => [150,  0.80, 0.80],
    'Triage & Assessment Centre'=> [10,   0.25, 0.50],
    'Outpatient Clinic'         => [20,   0.55, 0.75],
    'Physiotherapy Centre'      => [12,   0.35, 0.60],
    'Yoga Arogya Polyclinic'    => [15,   0.45, 0.65],
    'Psychological Disorders Clinic' => [8, 0.30, 0.50],
    'Naturopathy Centre'        => [10,   0.30, 0.55],
    'Panchakarma Centre'        => [10,   0.35, 0.60],
    '50-Bed Multi-Speciality Hospital' => [50, 0.65, 0.70],
    'Guest House'               => [42,   0.20, 0.45],
    'Pragyeshwar Mahadev Temple'=> [500,  0.50, 0.55],
    'Smriti Upwan & Herbal Garden' => [200, 0.30, 0.40],
    'Solar Observatory & Vedhshala' => [30, 0.15, 0.40],
    'Cattle Farm'               => [50,   0.60, 0.70],
    'Gayatri Vidyapeeth School' => [1200, 0.70, 0.75],
    'Railway Reservation Counter' => [10, 0.20, 0.60],
    'Srijna (Earn while Learn)' => [30,   0.25, 0.55],
    'Yajnashala'                => [100,  0.25, 0.45],
    'Prakritik Ahar Kendra'     => [40,   0.35, 0.50],
    'Grocery Store'             => [20,   0.30, 0.50],
];

$hourFactor = [8 => 0.4, 9 => 0.75, 10 => 1.0, 11 => 0.95, 12 => 0.70, 13 => 0.55, 14 => 0.80, 15 => 0.70, 16 => 0.45];
$dayFactor  = [0 => 0.15, 1 => 1.05, 2 => 1.0, 3 => 1.15, 4 => 1.0, 5 => 0.90, 6 => 0.40];

// resource name => id
$rid = [];
$idx = 1;
foreach ($resources as $r) { $rid[$r[0]] = $idx++; }

$rows = [];
foreach ($usageCfg as $name => [$cap, $baseProb, $userFrac]) {
    for ($d = 59; $d >= 0; $d--) {
        $dt = new DateTimeImmutable("today -{$d} days");
        $dow = (int)$dt->format('w');
        $dayF = $dayFactor[$dow];
        for ($h = 8; $h <= 16; $h++) {
            $prob = $baseProb * $dayF * $hourFactor[$h];
            if (mt_rand() / mt_getrandmax() > $prob) continue;
            $users = max(2, (int)round($cap * $userFrac * (0.6 + mt_rand() / mt_getrandmax() * 0.8)));
            $util = round($users / $cap * 100, 2);
            $rows[] = "({$rid[$name]}, '{$dt->format('Y-m-d')}', '{$h}:00:00', '" . ($h + 1) . ":00:00', {$users}, {$util}, NOW())";
        }
    }
}

$out[] = "INSERT INTO usage_records (resource_id, date, start_time, end_time, users_count, utilization_percentage, created_at) VALUES";
foreach ($rows as $i => $row) {
    $out[] = $row . ($i < count($rows) - 1 ? "," : ";");
}
$out[] = "";

/* ------------------------------------------------------------------ */
/* BOOKINGS                                                            */
/* ------------------------------------------------------------------ */

// user ids: 1 admin, 2 faculty, 3 student...
$bookings = [];
// Past completed bookings
$past = [
    [3, 'Study Room 1',           'approved->completed'],
    [3, 'Smart Classroom 1',      'approved->completed'],
    [3, 'Computer Lab 1',         'approved->completed'],
    [2, 'Seminar Hall 2',         'approved->completed'],
    [2, 'Auditorium 4',           'approved->completed'],
    [2, 'Commercial Computer Lab','approved->completed'],
    [3, 'Central Library',        'approved->completed'],
    [4, 'Smart Classroom 2',      'approved->completed'],
    [6, 'Seminar Hall 3',         'approved->completed'],
    [7, 'Computer Lab 2',         'approved->completed'],
];
foreach ($past as $k => [$uid, $res, $_label]) {
    $daysAgo = 2 + $k * 2;
    $date = "(CURDATE() - INTERVAL {$daysAgo} DAY)";
    $expected = (int)round($usageCfg[$res][0] * $usageCfg[$res][2]);
    $bookings[] = "({$uid}, (SELECT id FROM resources WHERE name = '{$res}'), {$date}, '10:00:00', '11:00:00', {$expected}, 'Class / group session', 'completed', NOW() - INTERVAL {$daysAgo} DAY)";
}

// Today's bookings (approved) - demo student + faculty
$today = [
    [3, 'Smart Classroom 1', '09:00:00', '10:00:00', 40, 'Database systems revision'],
    [3, 'Study Room 1',      '13:00:00', '15:00:00', 12, 'Group project work'],
    [2, 'Computer Lab 3',    '14:00:00', '16:00:00', 32, 'Data structures lab'],
];
foreach ($today as [$uid, $res, $st, $et, $exp, $pur]) {
    $bookings[] = "({$uid}, (SELECT id FROM resources WHERE name = '{$res}'), CURDATE(), '{$st}', '{$et}', {$exp}, '{$pur}', 'approved', NOW())";
}

// Future approved
$future = [
    [3, 'Computer Lab 1',    1, '10:00:00', '12:00:00', 40, 'Machine learning workshop',   'approved'],
    [3, 'Central Library',   1, '11:00:00', '13:00:00', 60, 'Reading and research',        'approved'],
    [2, 'Auditorium 2',      2, '09:00:00', '11:00:00', 250, 'Guest lecture series',       'approved'],
    [2, 'Meeting Room 1',    3, '15:00:00', '16:00:00', 10, 'Department meeting',          'approved'],
    [4, 'Study Room 2',      2, '10:00:00', '12:00:00', 18, 'Team assignment',             'approved'],
];
foreach ($future as [$uid, $res, $daysAhead, $st, $et, $exp, $pur, $status]) {
    $bookings[] = "({$uid}, (SELECT id FROM resources WHERE name = '{$res}'), (CURDATE() + INTERVAL {$daysAhead} DAY), '{$st}', '{$et}', {$exp}, '{$pur}', '{$status}', NOW())";
}

// Pending requests (faculty for recommendation demo on CURDATE()+3, 10-11, 45 students)
$bookings[] = "(2, (SELECT id FROM resources WHERE name = 'Commercial Computer Lab'), (CURDATE() + INTERVAL 3 DAY), '10:00:00', '11:00:00', 45, 'Advanced programming practical', 'pending', NOW())";
$bookings[] = "(2, (SELECT id FROM resources WHERE name = 'Computer Lab 2'), (CURDATE() + INTERVAL 5 DAY), '10:00:00', '12:00:00', 38, 'AI practical session', 'pending', NOW())";
$bookings[] = "(6, (SELECT id FROM resources WHERE name = 'Smart Classroom 2'), (CURDATE() + INTERVAL 2 DAY), '11:00:00', '12:00:00', 45, 'Sanskrit tutorial', 'pending', NOW())";

// A rejected and a cancelled booking
$bookings[] = "(3, (SELECT id FROM resources WHERE name = 'Commercial Computer Lab'), (CURDATE() + INTERVAL 4 DAY), '10:00:00', '11:00:00', 45, 'Weekend coding session', 'rejected', NOW())";
$bookings[] = "(3, (SELECT id FROM resources WHERE name = 'Smart Classroom 3'), (CURDATE() + INTERVAL 1 DAY), '09:00:00', '10:00:00', 40, 'Quiz preparation', 'cancelled', NOW())";

$out[] = "INSERT INTO bookings (user_id, resource_id, date, start_time, end_time, expected_users, purpose, status, created_at) VALUES";
foreach ($bookings as $i => $b) {
    $out[] = $b . ($i < count($bookings) - 1 ? "," : ";");
}
$out[] = "";

/* ------------------------------------------------------------------ */
/* RECOMMENDATIONS                                                     */
/* ------------------------------------------------------------------ */

$out[] = "INSERT INTO recommendations (booking_id, requested_resource_id, recommended_resource_id, reason, score, status, created_at) VALUES";
$out[] = "((SELECT id FROM bookings WHERE user_id = 2 AND resource_id = (SELECT id FROM resources WHERE name = 'Commercial Computer Lab') AND date = (CURDATE() + INTERVAL 3 DAY) LIMIT 1),";
$out[] = " (SELECT id FROM resources WHERE name = 'Commercial Computer Lab'),";
$out[] = " (SELECT id FROM resources WHERE name = 'Computer Lab 3'),";
$out[] = " 'Sufficient capacity (50) for 45 students, available at requested time, lowest current utilization (38%) among suitable alternatives, no booking conflict.', 92.5, 'proposed', NOW()),";
$out[] = "((SELECT id FROM bookings WHERE user_id = 2 AND resource_id = (SELECT id FROM resources WHERE name = 'Computer Lab 2') AND date = (CURDATE() + INTERVAL 5 DAY) LIMIT 1),";
$out[] = " (SELECT id FROM resources WHERE name = 'Computer Lab 2'),";
$out[] = " (SELECT id FROM resources WHERE name = 'Computer Lab 3'),";
$out[] = " 'Requested lab unavailable at that time; Computer Lab 3 has sufficient capacity and is available.', 80.0, 'proposed', NOW());";
$out[] = "";

/* ------------------------------------------------------------------ */
/* COMPLAINTS                                                          */
/* ------------------------------------------------------------------ */

$out[] = "INSERT INTO complaints (user_id, resource_id, category, description, priority, status, created_at, resolved_at) VALUES";
$out[] = "(3, (SELECT id FROM resources WHERE name = 'Computer Lab 3'), 'Computer', 'Two systems in row 4 are not booting and show no display output.', 'medium', 'in_progress', NOW() - INTERVAL 2 DAY, NULL),";
$out[] = "(3, (SELECT id FROM resources WHERE name = 'Central Library'), 'Wi-Fi', 'Wi-Fi keeps dropping near the reference section on the ground floor.', 'low', 'reported', NOW() - INTERVAL 1 DAY, NULL),";
$out[] = "(2, (SELECT id FROM resources WHERE name = 'Smart Classroom 1'), 'Projector', 'Projector bulb is dim and colours are faded; difficult to read slides.', 'high', 'in_progress', NOW() - INTERVAL 3 DAY, NULL),";
$out[] = "(4, (SELECT id FROM resources WHERE name = 'Study Room 1'), 'AC', 'Air conditioner is making a loud noise and not cooling.', 'medium', 'reported', NOW() - INTERVAL 12 HOUR, NULL),";
$out[] = "(6, (SELECT id FROM resources WHERE name = 'Seminar Hall 3'), 'Lights', 'Two tube lights are flickering near the back rows.', 'low', 'resolved', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 6 DAY);";
$out[] = "";

/* ------------------------------------------------------------------ */
/* NOTIFICATIONS                                                       */
/* ------------------------------------------------------------------ */

$out[] = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES";
$out[] = "(3, 'Booking approved', 'Your booking for Smart Classroom 1 today 9:00-10:00 AM has been approved.', 'booking', 0, NOW() - INTERVAL 2 HOUR),";
$out[] = "(3, 'Booking submitted', 'Your booking request for Study Room 1 today 1:00-3:00 PM has been submitted for approval.', 'booking', 0, NOW() - INTERVAL 3 HOUR),";
$out[] = "(3, 'Booking rejected', 'Your request for the Commercial Computer Lab on the upcoming weekend was rejected due to a scheduling conflict.', 'booking', 1, NOW() - INTERVAL 1 DAY),";
$out[] = "(3, 'Maintenance alert', 'The Commercial Computer Lab will be under maintenance this Saturday from 9 AM to 1 PM.', 'maintenance', 0, NOW() - INTERVAL 2 DAY),";
$out[] = "(3, 'Announcement', 'Mid-semester exam scheduling: book classrooms and seminar halls well in advance.', 'announcement', 0, NOW() - INTERVAL 6 DAY),";
$out[] = "(3, 'Recommendation', 'Computer Lab 3 is recommended for your upcoming practical session - sufficient capacity and low utilization.', 'recommendation', 0, NOW() - INTERVAL 1 DAY),";
$out[] = "(3, 'Complaint update', 'Your complaint about Computer Lab 3 computers is now in progress.', 'complaint', 0, NOW() - INTERVAL 1 DAY),";
$out[] = "(2, 'Booking approved', 'Your booking for Computer Lab 3 today 2:00-4:00 PM has been approved.', 'booking', 0, NOW() - INTERVAL 1 HOUR),";
$out[] = "(2, 'Recommendation available', 'Smart recommendation generated for your computer lab request on the upcoming week.', 'recommendation', 0, NOW() - INTERVAL 3 HOUR),";
$out[] = "(2, 'Announcement', 'DSVV SmartCampus is live! Book auditoriums, seminar halls, labs and study spaces online.', 'announcement', 1, NOW() - INTERVAL 12 DAY),";
$out[] = "(2, 'Complaint update', 'Your complaint about the Smart Classroom 1 projector has been assigned and is in progress.', 'complaint', 0, NOW() - INTERVAL 2 DAY),";
$out[] = "(4, 'Booking approved', 'Your booking for Study Room 2 is approved.', 'booking', 0, NOW() - INTERVAL 1 DAY);";
$out[] = "";

$out[] = "SET FOREIGN_KEY_CHECKS = 1;";
$out[] = "";
$out[] = "-- End of DSVV SmartCampus schema and seed data";

file_put_contents(__DIR__ . '/smartcampus.sql', implode("\n", $out));
echo "Generated database/smartcampus.sql (" . strlen(implode("\n", $out)) . " bytes)\n";
