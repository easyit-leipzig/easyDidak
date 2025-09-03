-- ======================
-- Tabellenstruktur
-- ======================
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL,
    name VARCHAR(50)
);

CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    school_form VARCHAR(10),
    grade INT,
    valid_until DATE NULL
);

CREATE TABLE sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject_id INT NOT NULL,
    room_id INT NOT NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id)
);

CREATE TABLE session_students (
    session_id INT,
    student_id INT,
    PRIMARY KEY (session_id, student_id),
    FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    FOREIGN KEY (student_id) REFERENCES students(student_id)
);

-- ======================
-- Stammdaten
-- ======================
INSERT INTO subjects (code, name) VALUES
('MAT', 'Mathematik'),
('PHY', 'Physik');

INSERT INTO rooms (name) VALUES
('Rm. 3 SH Leipzig-Lausen');

-- ======================
-- Schüler:innen
-- ======================
INSERT INTO students (first_name, last_name, school_form, grade, valid_until) VALUES
('Romy', 'Domann', 'GR', 4, '2025-09-01'),
('Ari', 'Fischer', 'GR', 5, '2025-09-01'),
('Lukas', 'Hillpert', 'RS', 9, NULL),
('Anna', 'Lademann', 'GR', 5, '2025-09-01'),
('Sistine', 'Lauschke', 'GR', 4, '2025-09-01'),
('Anna-Sophie', 'Canitz', 'GYM', 10, NULL),
('Gustav', 'Fleischer', 'GYM', 11, NULL),
('Luise', 'Schaff', 'GYM', 10, NULL),
('Lotte Marie', 'Wicher', 'GYM', 10, NULL),
('Simos', 'Giannakidis', 'GYM', 10, '2025-09-02'),
('Sarah', 'Kahle', 'GYM', 6, NULL),
('Kiara', 'Kuznik', 'GR', 4, NULL),
('Mira', 'Moritz', 'GR', 5, '2025-11-30'),
('Laila', 'Stockmann', 'GR', 4, NULL),
('Paula', 'Juraschek', 'GYM', 8, NULL),
('Karl', 'König', 'GYM', 7, '2025-09-02'),
('Carlotta Erika', 'Körber', 'GYM', 9, '2025-10-31'),
('Selina', 'Möwes', 'RS', 9, NULL),
('Lia', 'Schubert', 'GYM', 8, NULL),
('Noah', 'Freiberg', 'GYM', 12, NULL),
('Zoey', 'Schönherr', 'GYM', 7, NULL),
('Maruschka', 'Gottschlich', 'GYM', 11, NULL),
('Helena', 'Mußdorf', 'GYM', 11, '2025-09-30'),
('Felix', 'Schölzel', 'GYM', 8, NULL),
('Juni Florentine', 'Schölzel', 'GYM', 7, NULL),
('Jalia', 'Wagner', 'GYM', 10, NULL),
('Ida', 'Johnsen', 'GYM', 9, NULL),
('Svea', 'Ziegler', 'GR', 3, '2025-09-04'),
('Maja', 'Deutlich', 'RS', 7, NULL),
('Louis', 'Grobe', 'GYM', 10, NULL),
('Pia', 'Ponader', 'GYM', 7, NULL),
('Felix', 'Scheithauer', 'GYM', 8, '2025-09-30');

-- ======================
-- Sessions (01.09.25 – 05.09.25)
-- ======================
INSERT INTO sessions (session_date, start_time, end_time, subject_id, room_id) VALUES
('2025-09-01', '15:35:00', '17:05:00', 1, 1), -- MAT
('2025-09-01', '17:10:00', '18:40:00', 1, 1), -- MAT
('2025-09-02', '15:35:00', '17:05:00', 1, 1), -- MAT
('2025-09-02', '17:10:00', '18:40:00', 1, 1), -- MAT
('2025-09-03', '15:35:00', '17:05:00', 1, 1), -- MAT
('2025-09-03', '17:10:00', '18:40:00', 1, 1), -- MAT
('2025-09-04', '15:35:00', '17:05:00', 1, 1), -- MAT
('2025-09-04', '17:10:00', '18:40:00', 2, 1), -- PHY
('2025-09-05', '15:35:00', '17:05:00', 1, 1); -- MAT

-- ======================
-- Teilnehmer pro Session
-- ======================
-- 01.09.25
INSERT INTO session_students VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),
(2,6),(2,7),(2,8),(2,9);

-- 02.09.25
INSERT INTO session_students VALUES
(3,10),(3,11),(3,12),(3,13),(3,14),
(4,15),(4,16),(4,17),(4,18),(4,19);

-- 03.09.25
INSERT INTO session_students VALUES
(5,20),(5,3),(5,16),(5,21),
(6,22),(6,17),(6,23),(6,24),(6,25),(6,26);

-- 04.09.25
INSERT INTO session_students VALUES
(7,2),(7,20),(7,27),(7,19),(7,28),
(8,6),(8,29),(8,30),(8,31),(8,32);

-- 05.09.25
INSERT INTO session_students VALUES
(9,10),(9,16),(9,8),(9,21);
