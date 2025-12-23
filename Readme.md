# IELTS Online Examination System

A comprehensive web-based platform for conducting IELTS (International English Language Testing System) examinations online. This system provides a complete solution for administrators to create and manage exams, and for students to take tests with automatic grading and instant feedback.

## Features

### Administrator Panel
- **Dashboard**: View system statistics including total students, groups, subjects, and exams
- **User Management**: Create and manage admin and student accounts
- **Group Management**: Organize students into cohorts/classes
- **Subject Management**: Add subjects with configurable time limits
- **Question Bank Management**:
  - Create multiple question types (multiple choice, open-ended, matching)
  - Upload reading materials (TXT files)
  - Upload audio files (MP3 for listening comprehension)
  - Set individual question scoring
- **Exam Scheduling**: Schedule exams for specific student groups
- **Results Dashboard**: View aggregate results and individual student responses

### Student Portal
- **Personal Dashboard**: View upcoming exams and past performance
- **Exam Interface**:
  - Timed tests with countdown timer
  - Display reading materials and audio files
  - Support for multiple question types
  - Auto-submit on time expiration
- **Results Review**: View detailed exam results with correct answers

### Supported Question Types
1. Multiple Choice (4 options: A/B/C/D)
2. Matching (vocabulary, phrases)
3. Open-ended (short answer)
4. True/False statements
5. Fill-in-the-blank

## Technologies Used

### Backend
- PHP 7+
- MySQL 8.0 with UTF-8 character set
- PDO (PHP Data Objects)
- Object-Oriented Programming

### Frontend
- HTML5
- Bootstrap 5.3.0
- Bootstrap Icons 1.10.0
- Vanilla JavaScript

### Server Requirements
- Web Server (Apache/Nginx)
- PHP 7.0 or higher
- MySQL 5.7 or higher
- PDO extension enabled

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd ielts_Test
```

### 2. Database Setup

#### Option A: Automatic Setup
The database will be created automatically when you first access the application.

#### Option B: Manual Import
```bash
mysql -u your_username -p < edu_system.sql
```

### 3. Configure Database Connection

Edit `/includes/db.php` with your database credentials:

```php
private $host = "your_host";      // Default: 172.18.250.21:3306
private $dbname = "edu_system";
private $username = "your_username";  // Default: admins
private $password = "your_password";  // Default: 23234455
```

### 4. Set File Permissions

Ensure the uploads directory is writable:

```bash
chmod 755 uploads/
```

### 5. Create Admin Account

Access the admin creation page:
```
http://your-domain/create_admin.php
```

Or use the default credentials:
- **Username**: admin
- **Password**: admin123

## Usage Guide

### For Administrators

1. **Login**: Navigate to `/login.php` and enter admin credentials
2. **Create Student Groups**: Go to Groups section and create cohorts
3. **Add Subjects**: Create subjects with exam duration (in minutes)
4. **Upload Materials**:
   - Upload reading texts (.txt files)
   - Upload listening audio (.mp3 files)
5. **Create Questions**:
   - Select question type
   - Enter question text and options
   - Set correct answer and scoring
6. **Schedule Exams**:
   - Select subject and student group
   - Set exam date
   - Change status to "in_progress" when ready
7. **View Results**: Check exam results and student answers after completion

### For Students

1. **Login**: Access `/login.php` with student credentials
2. **View Exams**: Check available exams on the dashboard
3. **Take Exam**:
   - Click on an active exam
   - Read instructions and materials
   - Answer all questions within time limit
   - Submit exam before timer expires
4. **Review Results**: View scores and correct answers after exam completion

## Project Structure

```
ielts_Test/
├── admin/                      # Administrator panel
│   ├── dashboard.php          # Statistics overview
│   ├── users.php              # User management
│   ├── groups.php             # Group management
│   ├── subjects.php           # Subject management
│   ├── questions.php          # Question creation
│   ├── exams.php              # Exam scheduling
│   ├── exam_results.php       # Results dashboard
│   └── student_answers.php    # Student responses
├── student/                    # Student portal
│   ├── dashboard.php          # Student overview
│   ├── exams.php              # Exam listing
│   ├── exam.php               # Exam interface
│   └── exams_details.php      # Results review
├── includes/                   # Core files
│   ├── db.php                 # Database class
│   ├── auth.php               # Authentication
│   ├── header.php             # Navigation template
│   ├── footer.php             # Footer template
│   └── logout.php             # Logout handler
├── assets/                     # Static resources
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── uploads/                    # Upload directory
│   └── [reading/audio files]
├── index.php                   # Entry point
├── login.php                   # Authentication page
├── create_admin.php            # Admin setup utility
└── edu_system.sql             # Database schema
```

## Database Schema

### Core Tables
- **users**: Admin and student accounts
- **student_group**: Student cohorts
- **subjects**: Courses with time limits
- **exams**: Exam sessions
- **question_types**: Question format types
- **question_files**: Reading/listening materials
- **question_read**: Individual questions
- **multiple_questions**: Multiple choice options
- **open_questions**: Open-ended questions
- **matching_questions**: Matching/vocabulary questions
- **answers**: Student responses
- **scores**: Scoring records

## Security Notes

For production deployment, consider the following:

1. **Environment Variables**: Move database credentials to `.env` file
2. **Password Security**: All passwords are hashed using bcrypt
3. **Input Validation**: Enhance validation and sanitization
4. **HTTPS**: Use SSL/TLS certificates
5. **File Upload Security**: Validate file types and sizes
6. **Session Security**: Configure secure session settings

## File Upload Specifications

### Reading Materials
- Format: .txt
- Maximum size: 10MB
- Location: `/uploads/reading/`

### Audio Files
- Format: .mp3
- Maximum size: 10MB
- Location: `/uploads/audio/`

## Default Credentials

### Administrator
- Username: `admin`
- Password: `admin123`

**Important**: Change default credentials immediately after first login.

## Troubleshooting

### Database Connection Error
- Verify database credentials in `/includes/db.php`
- Ensure MySQL service is running
- Check database user permissions

### File Upload Issues
- Verify `/uploads` directory exists and is writable
- Check PHP upload_max_filesize and post_max_size settings
- Ensure file extensions are allowed (.txt, .mp3)

### Session Errors
- Ensure PHP session support is enabled
- Check session save path permissions
- Verify session cookie settings

## Development

### Adding New Question Types
1. Add entry to `question_types` table
2. Create corresponding question table
3. Update question creation form in `/admin/questions.php`
4. Update exam interface in `/student/exam.php`

### Customizing Exam Timer
Edit the subject's timer value in the subjects table (value in minutes).

## License

This project is developed for educational purposes.

## Support

For issues and questions, please contact the system administrator or refer to the project documentation.

---

**Last Updated**: December 2025
**Version**: 1.0
