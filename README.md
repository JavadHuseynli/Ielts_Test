# İngilis Dili Test Sistemi (English Language Test System)

A comprehensive web-based educational exam platform designed for administering and managing English language proficiency tests (IELTS-focused) at Bakı Biznes Universiteti (Baku Business University).

## Overview

This system provides a complete solution for creating, administering, and evaluating English language exams with support for multiple question types, automated grading, and detailed analytics. The platform serves multiple user roles including administrators, department heads, teachers, and students.

## Features

### For Administrators & Teachers
- **User Management**: Create and manage student, teacher, and administrative accounts
- **Group Management**: Organize students into groups for targeted exam administration
- **Exam Creation**: Schedule and configure exams with customizable settings
- **Question Bank**: Manage a comprehensive question library with three question types:
  - Multiple choice (A/B/C/D format)
  - Open-ended written responses
  - Matching/vocabulary exercises
- **Reading & Listening Materials**: Upload and manage comprehension materials
- **Results & Analytics**:
  - View individual student performance
  - Generate group statistics
  - Export results to PDF and DOCX formats
  - Interactive charts and leaderboards
- **Archive Management**: Archive and restore questions and results

### For Students
- **Exam Dashboard**: View available and upcoming exams
- **Interactive Exam Interface**:
  - Support for reading comprehension with file attachments
  - Listening comprehension with audio files
  - Built-in exam timer with countdown
  - Auto-save functionality
  - Real-time answer submission
- **Performance Tracking**: View past exam results and scores
- **User-Friendly Interface**: Clean, responsive design with Azerbaijani language support

## Technologies Used

### Backend
- **PHP 7.4+**: Server-side logic
- **MySQL/MariaDB**: Database management
- **PDO**: Database abstraction and security
- **Composer**: Dependency management
- **PHPWord**: Document generation for Word exports
- **FPDF**: PDF report generation

### Frontend
- **HTML5**: Structure
- **Tailwind CSS**: Utility-first styling framework
- **Alpine.js**: Lightweight JavaScript framework for interactivity
- **Chart.js**: Data visualization and analytics
- **Google Fonts (Inter)**: Typography
- **Material Icons**: UI iconography

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ielts_Test
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   - Create a MySQL database named `edu_system`
   - Import the database schema:
     ```bash
     mysql -u root -p edu_system < edu_system.sql
     ```

4. **Configure Database Connection**
   - Edit `/includes/db.php` with your database credentials:
     ```php
     private $host = 'localhost';
     private $db = 'edu_system';
     private $user = 'your_username';
     private $pass = 'your_password';
     ```

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 json/
   ```

6. **Start the Server**
   - For development using PHP built-in server:
     ```bash
     php -S localhost:8000
     ```
   - Or configure your Apache/Nginx virtual host to point to the project directory

7. **Access the Application**
   - Open your browser and navigate to `http://localhost:8000`
   - Default login credentials should be configured in your database

## Project Structure

```
ielts_Test/
├── index.php                 # Entry point - redirects based on user role
├── login.php                 # Authentication page
├── composer.json             # PHP dependencies
├── edu_system.sql            # Database schema and initial data
│
├── assets/                   # Static resources
│   ├── css/style.css        # Custom styles
│   └── js/script.js         # Custom JavaScript
│
├── includes/                 # Shared backend components
│   ├── db.php               # Database connection class
│   ├── auth.php             # Authentication functions
│   ├── permissions.php      # Role-based access control
│   ├── header.php           # HTML header template
│   ├── footer.php           # HTML footer template
│   └── fpdf/                # PDF generation library
│
├── admin/                    # Administrative panel
│   ├── dashboard.php        # Admin dashboard
│   ├── exams.php            # Exam management
│   ├── questions.php        # Question bank management
│   ├── exam_results.php     # Results viewing and analytics
│   ├── download_results_pdf.php      # PDF export
│   ├── download_results_kafedra_doc.php  # DOCX export
│   └── ... (additional admin pages)
│
├── student/                  # Student interface
│   ├── dashboard.php        # Student dashboard
│   ├── exam.php             # Exam taking interface
│   ├── exams.php            # Available exams list
│   └── exams_details.php    # Exam details
│
├── uploads/                  # File uploads (reading/listening materials)
├── json/                    # JSON data storage
└── vendor/                  # Composer dependencies
```

## Database Schema

The system uses 12 main tables:

- **users**: User accounts (admin, prorektor, kafedra, muellim, student)
- **student_group**: Student groupings
- **subjects**: Exam subjects with timer configurations
- **exams**: Exam instances with scheduling
- **question_types**: Question classification
- **question_files**: Reading and listening materials
- **question_read**: Base question records
- **multiple_questions**: Multiple choice questions
- **open_questions**: Free response questions
- **matching_questions**: Matching format questions
- **answers**: Student responses with auto-grading
- **scores**: Calculated exam scores

## User Roles

The system supports five user roles with different permission levels:

1. **Admin**: Full system access, user management, system configuration
2. **Prorektor** (Vice-Rector): High-level administrative access
3. **Kafedra** (Department Head): Department-level management and reporting
4. **Muellim** (Teacher): Exam creation, question management, grading
5. **Student**: Exam taking and result viewing

## Usage

### Creating an Exam

1. Log in as an administrator or teacher
2. Navigate to "Exams" section
3. Click "Create New Exam"
4. Select subject, student group, and date
5. Add questions from the question bank
6. Set exam duration and parameters
7. Activate the exam

### Taking an Exam

1. Log in as a student
2. View available exams on the dashboard
3. Click "Start Exam" when ready
4. Answer questions within the time limit
5. Submit answers (auto-saved throughout)
6. View results after grading

### Generating Reports

1. Navigate to "Exam Results"
2. Select the exam and student/group
3. Choose export format (PDF or DOCX)
4. Download the generated report

## Development

### Recent Updates

- Multi-user type interface implementation
- Database structure optimization
- System design renewal
- Online exam functionality enhancements

### Branch Information

- Main branch: `mains`
- Current status: Clean working tree

## Configuration

### Environment Settings

- **Error Reporting**: Enabled for development (disable in production)
- **Session Management**: PHP sessions for authentication
- **File Uploads**: Configure max upload size in `php.ini` if needed
- **Timezone**: Set in PHP configuration as needed

### Customization

- **UI Language**: Currently in Azerbaijani, can be localized
- **Exam Timer**: Configured per subject in the database
- **Question Scoring**: Customizable per question type
- **Frontend Theme**: Modify Tailwind classes in templates

## Security Considerations

- Uses PDO prepared statements to prevent SQL injection
- Session-based authentication
- Password hashing recommended (update legacy authentication)
- Role-based access control for all administrative functions
- Input validation on form submissions

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Modern mobile browsers

## Troubleshooting

### Common Issues

**Database Connection Errors**
- Verify database credentials in `/includes/db.php`
- Ensure MySQL service is running
- Check database exists and is properly imported

**Permission Errors**
- Ensure `uploads/` directory has write permissions
- Check web server user permissions

**Missing Dependencies**
- Run `composer install` to install PHP dependencies
- Verify PHP version meets requirements

## License

Educational project for Bakı Biznes Universiteti.

## Credits

Developed for administering English language proficiency assessments at Baku Business University.

---

**Version**: 1.0
**Last Updated**: December 2025
