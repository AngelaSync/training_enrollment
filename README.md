# Training Enrollment System

A multi-table PHP PDO web application for managing training courses, class schedules, students, and enrollments. Built for the **IPT (Integrative Programming and Technology)** laboratory activity.

A single administrator registers courses, schedules classes with limited slots, records students, enrolls them, cancels enrollments, and views summary reports. The system prevents over-enrollment and duplicate enrollments, and uses database transactions so that related operations succeed or fail together.

## Features

- **Courses**: add, edit, and delete courses (duplicate course codes are rejected)
- **Classes**: manage schedules, instructors, and available slots, listed with their course (JOIN)
- **Record Student**: one form that inserts the student, inserts the enrollment, and decrements the class slots in a single transaction
- **Enroll**: enroll an existing student into a class (blocks full classes and duplicate enrollments)
- **Enrollments**: list enrollments joined with student, class, and course details; cancel an enrollment (updates status and restores the slot in one transaction)
- **Reports**: overall totals and a per-class summary (LEFT JOIN, so classes with no enrollments still appear)
- Server-side validation on every form, and prepared statements for every query
- Output escaped with `htmlspecialchars` to prevent XSS

## Tech Stack

- PHP 8.2 (PDO, MySQL driver)
- MySQL / MariaDB
- Apache (XAMPP)
- HTML and CSS (maroon, brown, and cream theme)

## Database Schema

Four tables: `students`, `courses`, `classes`, and `enrollments`.

- `classes.course_id` references `courses.course_id`
- `enrollments.student_id` references `students.student_id`
- `enrollments.class_id` references `classes.class_id`
- `enrollments` is the junction table resolving the many-to-many relationship between students and classes
- `classes.slots` holds the **remaining** slots. It goes down on enrollment and back up on cancellation.

The full script is in `sql/schema.sql`.

## Folder Structure

```
training_enrollment/
|-- config/
|   |-- db.php            # Real connection settings (NOT committed)
|   `-- db.sample.php     # Template with placeholder credentials
|-- classes/
|   |-- Database.php              # PDO wrapper (Singleton)
|   |-- Pet.php                   # Unrelated PDO CRUD example from the lab
|   |-- Student.php
|   |-- Course.php
|   |-- ClassSection.php
|   `-- EnrollmentRepository.php  # Transactions + JOIN queries (Repository)
|-- admin/
|   |-- courses.php
|   |-- classes.php
|   |-- students.php      # Record student (multi-table form)
|   |-- enroll.php
|   |-- enrollments.php
|   `-- reports.php
|-- includes/
|   |-- header.php
|   `-- footer.php
|-- css/
|   `-- style.css
|-- sql/
|   `-- schema.sql
|-- .gitignore
`-- index.php
```

## Setup Instructions

1. **Install XAMPP** and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. **Get the code.** Clone this repository (or download it as a ZIP) into your web server root:
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/AngelaSync/training_enrollment.git
   ```
3. **Create the database.** Open `http://localhost/phpmyadmin`, go to the **Import** tab, choose `sql/schema.sql`, and click **Go**. This creates `training_db` and its four tables.
4. **Create your connection file.** Copy `config/db.sample.php` to `config/db.php`, then edit the credentials if yours are different. The XAMPP defaults are user `root` with an empty password.
5. **Run the app.** Open `http://localhost/training_enrollment/` in your browser.

`config/db.php` is listed in `.gitignore` so database credentials are never committed.

## Design Patterns Used

### Singleton: `Database`

`Database` extends PDO and can only be created through `Database::getInstance()`. Its constructor is private, so the whole application shares one connection.

**Why:** opening a new connection on every query is wasteful, and a single shared instance keeps configuration (error mode, fetch mode) in one place. `Database` also exposes reusable helpers (`insert`, `update`, `delete`, `getRow`, `getRows`), so common queries are not repeated across pages.

### Repository: `EnrollmentRepository`

`EnrollmentRepository` is the only class that knows how enrollments are stored. It owns the multi-step operations (`recordStudent`, `enroll`, `cancel`) and the JOIN queries (`allWithDetails`, `classSummary`, `totals`).

**Why:** the pages in `admin/` handle only requests and presentation, with no SQL in them. Data access lives in the classes, so the logic can change without touching the pages. Keeping each multi-step operation in one method also means a transaction cannot be forgotten or split across files.

## Transactions and Data Integrity

| Operation | Steps (all in one transaction) |
|---|---|
| `recordStudent` | check slots, insert student, insert enrollment, decrement slots |
| `enroll` | check slots, check for duplicate, insert enrollment, decrement slots |
| `cancel` | mark enrollment `cancelled`, restore one slot |

- If anything fails, or the class is full, the transaction is **rolled back** and no partial rows remain.
- The class row is locked with `SELECT ... FOR UPDATE`, so two simultaneous requests cannot take the last slot.
- `cancel` only restores a slot when the enrollment is still `active`, so cancelling twice cannot create extra slots.

## Security Notes

- All queries use PDO **prepared statements**.
- Inputs are validated on the server (required fields, lengths, email format, numeric ids).
- Output is escaped with `htmlspecialchars`.
- State-changing actions (delete, cancel) use **POST** forms, not links.
- Database credentials are kept out of version control.

## Screenshots

Screenshots demonstrating each feature (course management, class management, recording a student, enrollments list, cancellation, the rollback case, and database evidence) are included in the project documentation document.

## Author

[Your Name], IPT laboratory activity
