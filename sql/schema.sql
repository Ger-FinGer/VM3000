CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE project_weeks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(20) NOT NULL UNIQUE,
    periods_per_day INT NOT NULL DEFAULT 6,
    teacher_initial_password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE project_week_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_week_id INT NOT NULL,
    day_date DATE NOT NULL,
    FOREIGN KEY (project_week_id) REFERENCES project_weeks(id) ON DELETE CASCADE
);

CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    initials VARCHAR(10) NOT NULL UNIQUE,
    password_hash VARCHAR(255) DEFAULT NULL
);

CREATE TABLE week_teacher_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_week_id INT NOT NULL,
    teacher_id INT NOT NULL,
    quota INT DEFAULT NULL,
    homeroom_class_id INT DEFAULT NULL,
    password_set TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_week_teacher (project_week_id, teacher_id),
    FOREIGN KEY (project_week_id) REFERENCES project_weeks(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (homeroom_class_id) REFERENCES classes(id) ON DELETE SET NULL
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_week_day_id INT NOT NULL,
    period_index INT NOT NULL,
    class_id INT NOT NULL,
    room_id INT DEFAULT NULL,
    description TEXT,
    FOREIGN KEY (project_week_day_id) REFERENCES project_week_days(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);

CREATE TABLE booking_teachers (
    booking_id INT NOT NULL,
    teacher_id INT NOT NULL,
    PRIMARY KEY (booking_id, teacher_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE teacher_day_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_week_id INT NOT NULL,
    project_week_day_id INT NOT NULL,
    teacher_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    UNIQUE KEY uniq_teacher_day (project_week_id, project_week_day_id, teacher_id),
    FOREIGN KEY (project_week_id) REFERENCES project_weeks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_week_day_id) REFERENCES project_week_days(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);

CREATE TABLE class_day_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_week_id INT NOT NULL,
    project_week_day_id INT NOT NULL,
    class_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    UNIQUE KEY uniq_class_day (project_week_id, project_week_day_id, class_id),
    FOREIGN KEY (project_week_id) REFERENCES project_weeks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_week_day_id) REFERENCES project_week_days(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

INSERT INTO admins (email, password_hash) VALUES
('admin@example.com', '$2y$12$OiXtZ5JKwj.m5jv7Ol/sZOo8GaUBaFsOWLgYQ7AG7YtnC35ZwDzQi');

INSERT INTO classes (name) VALUES
('5A'), ('6B'), ('7C');

INSERT INTO rooms (name) VALUES
('R101'), ('R202'), ('LAB1');

INSERT INTO project_weeks (name, slug, periods_per_day, teacher_initial_password_hash) VALUES
('Projektwoche Beispiel', 'TSS', 6, '$2y$12$pH/iJLPTDUFsqZ8nzg/sqe3eMHuemBYTuoDYSPaJcdoqLH8TKZ6.S');

INSERT INTO project_week_days (project_week_id, day_date) VALUES
(1, '2024-06-10'),
(1, '2024-06-11'),
(1, '2024-06-12'),
(1, '2024-06-13'),
(1, '2024-06-14');
