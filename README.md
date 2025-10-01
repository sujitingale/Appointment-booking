# 🏥 MediCare - Appointment Booking System

A professional web-based appointment booking system built with PHP and MySQL. This system allows patients to book appointments with doctors and enables doctors to manage their appointments efficiently.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Features

### For Patients
- 👤 User registration and authentication
- 📅 Browse and book appointments with doctors
- 🔍 View appointment history
- ✏️ Update profile information
- 🔔 Receive appointment notifications
- ❌ Cancel appointments
- 🔐 Password reset functionality

### For Doctors
- 👨‍⚕️ Doctor profile management
- 📋 View all appointment requests
- ✅ Approve/Reject appointment requests
- ✔️ Mark appointments as completed
- 📊 Dashboard with statistics
- 📅 View today's schedule
- 🔔 Send notifications to patients

### General Features
- 🎨 Modern and responsive UI design
- 🔒 Secure authentication system
- 💾 Session management
- 📱 Mobile-friendly interface
- 🌈 Beautiful gradient color scheme
- ⚡ Fast and lightweight

## 🛠️ Technologies Used

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Icons:** Font Awesome 6.4.0
- **Server:** Apache (XAMPP/WAMP/MAMP)

## 📋 Prerequisites

Before you begin, ensure you have the following installed:
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache Server (XAMPP/WAMP/MAMP recommended)
- Web browser (Chrome, Firefox, Safari, etc.)

## 🚀 Installation

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/medicare-appointment-system.git
cd medicare-appointment-system
```

### Step 2: Set Up the Database

1. Start your Apache and MySQL servers (XAMPP/WAMP)
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create a new database named `appointment_system`
4. Import the database schema:
   - Navigate to the `database` folder
   - Import `schema.sql` file

### Step 3: Configure Database Connection

1. Open `config/database.php`
2. Update the database credentials if needed:
```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Your MySQL password
define('DB_NAME', 'appointment_system');
```

### Step 4: Move Project to Web Server

**For XAMPP:**
```bash
# Copy the project to htdocs folder
cp -r medicare-appointment-system C:/xampp/htdocs/Project
```

**For WAMP:**
```bash
# Copy the project to www folder
cp -r medicare-appointment-system C:/wamp64/www/Project
```

### Step 5: Access the Application

Open your web browser and navigate to:
```
http://localhost/Project/
```

## 📁 Project Structure

```
medicare-appointment-system/
│
├── assets/
│   └── css/
│       └── style.css              # Main stylesheet
│
├── config/
│   └── database.php               # Database configuration
│
├── database/
│   └── schema.sql                 # Database schema
│
├── doctor/
│   ├── appointments.php           # Doctor appointment management
│   └── update-appointment.php     # Update appointment status
│
├── index.php                      # Landing page
├── login.php                      # User login
├── register.php                   # User registration
├── dashboard.php                  # User dashboard
├── profile.php                    # User profile management
├── appointments.php               # View appointments
├── book-appointment.php           # Book new appointment
├── appointment-details.php        # Appointment details
├── appointment-success.php        # Booking confirmation
├── cancel-appointment.php         # Cancel appointment
├── forgot-password.php            # Password reset request
├── logout.php                     # User logout
├── test-connection.php            # Database connection test
├── setup-database.php             # Database setup utility
├── diagnose.php                   # System diagnostics
└── README.md                      # Project documentation
```

## 🗄️ Database Schema

### Tables

1. **users** - Stores user information (patients and doctors)
2. **doctor_profiles** - Additional information for doctors
3. **appointments** - Appointment records
4. **notifications** - User notifications
5. **doctor_availability** - Doctor availability schedules

## 🎯 Usage

### For Patients

1. **Register an Account**
   - Visit the homepage
   - Click "Register Now"
   - Fill in your details and select "Patient"
   - Submit the form

2. **Book an Appointment**
   - Login to your account
   - Click "Book Appointment"
   - Select a doctor and time slot
   - Confirm your booking

3. **Manage Appointments**
   - View all your appointments in the dashboard
   - Cancel appointments if needed
   - View appointment details

### For Doctors

1. **Register as Doctor**
   - Visit the registration page
   - Select "Doctor" as user type
   - Complete your profile with specialization

2. **Manage Appointments**
   - Login to your account
   - Navigate to "Manage Appointments"
   - Approve or reject appointment requests
   - Mark completed appointments

3. **View Schedule**
   - Check today's appointments in the dashboard
   - View upcoming appointments
   - Filter appointments by status

## 🔐 Default Credentials

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**Note:** Change the default password after first login for security.

## 🎨 Screenshots

### Homepage
Beautiful landing page with gradient design and feature highlights.

### Dashboard
Personalized dashboard with statistics and quick actions.

### Appointment Management
Easy-to-use interface for managing appointments.

## 🐛 Troubleshooting

### Database Connection Error
```
ERROR: Could not connect. SQLSTATE[HY000] [2002]
```
**Solution:** 
- Ensure MySQL is running
- Check database credentials in `config/database.php`
- Verify the database exists

### Page Not Found
```
Not Found - The requested URL was not found
```
**Solution:**
- Ensure the project is in the correct folder (htdocs/www)
- Check Apache is running
- Verify the URL path

### Blank Page
**Solution:**
- Enable PHP error reporting
- Check PHP error logs
- Ensure all required PHP extensions are enabled

## 🔧 Configuration

### Change MySQL Port
If your MySQL runs on a different port (e.g., 3307):
```php
// In config/database.php
define('DB_PORT', '3307');
```

### Enable Email Notifications
Update the email configuration in the notification functions to send real emails instead of displaying reset links.

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add some feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Your Name**
- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your.email@example.com

## 🙏 Acknowledgments

- Font Awesome for icons
- PHP community for excellent documentation
- All contributors who help improve this project

## 📞 Support

If you encounter any issues or have questions:
- Open an issue on GitHub
- Contact: your.email@example.com

## 🔮 Future Enhancements

- [ ] Email notification system
- [ ] SMS reminders
- [ ] Video consultation integration
- [ ] Payment gateway integration
- [ ] Multi-language support
- [ ] Advanced search and filters
- [ ] Rating and review system
- [ ] Medical records management
- [ ] Prescription management
- [ ] Analytics dashboard

## 📊 Version History

- **v1.0.0** (2025-01-01)
  - Initial release
  - Basic appointment booking functionality
  - User authentication
  - Doctor appointment management
  - Responsive design

---

Made with ❤️ by [Your Name]

**⭐ If you find this project useful, please consider giving it a star!**
