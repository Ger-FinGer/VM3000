ALTER TABLE rooms
    ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE bookings
    ADD COLUMN booking_type VARCHAR(20) NOT NULL DEFAULT 'normal';

CREATE TABLE IF NOT EXISTS slot_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    project_week_day_id INT NOT NULL,
    period_index INT NOT NULL,
    description TEXT,
    updated_by INT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_slot_plan (class_id, project_week_day_id, period_index),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (project_week_day_id) REFERENCES project_week_days(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS slot_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    project_week_day_id INT NOT NULL,
    period_index INT NOT NULL,
    room_id INT NOT NULL,
    updated_by INT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_slot_room (class_id, project_week_day_id, period_index),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (project_week_day_id) REFERENCES project_week_days(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS absences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_week_id INT NOT NULL,
    project_week_day_id INT NOT NULL,
    teacher_id INT NOT NULL,
    all_day TINYINT(1) NOT NULL DEFAULT 0,
    period_index INT DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_absence (project_week_id, project_week_day_id, teacher_id, period_index, all_day),
    FOREIGN KEY (project_week_id) REFERENCES project_weeks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_week_day_id) REFERENCES project_week_days(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
);
