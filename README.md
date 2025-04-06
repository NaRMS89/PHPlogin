# Sitin Data Web Application

A web application for managing lab sit-in data, reservations, and resources.

## Features

### Admin Dashboard
- **Sit-in Data**: View and export sit-in data with filtering options
- **Feedback Reports**: View student feedback
- **Lab Resources/Materials**: Upload and manage lab resources (PDFs, documents, links)
- **Lab Schedules**: View and manage lab room schedules
- **Leaderboard**: Track student lab usage points and add admin points

### User Dashboard
- **Profile Management**: Update personal information and profile picture
- **Lab Reservation**: Make reservations for lab rooms
- **Lab Schedules**: View lab room availability and schedules
- **History**: View past sit-in sessions

## Database Setup

1. Import the database schema:
   ```
   mysql -u your_username -p your_database_name < database_setup.sql
   ```

2. The following tables will be created:
   - `reservations`: Stores lab room reservations
   - `lab_resources`: Stores uploaded lab resources
   - `student_points`: Tracks student points for the leaderboard
   - `points_log`: Logs point additions with reasons
   - `feedback`: Stores student feedback

## File Structure

- `ADMIN/`: Admin dashboard files
  - `admin_dashboard.php`: Main admin dashboard
  - `sitin_data.php`: Sit-in data view with export options
  - `upload_resource.php`: Handles resource uploads
  - `delete_resource.php`: Handles resource deletion
  - `add_admin_points.php`: Handles adding admin points

- `USER/`: User dashboard files
  - `dashboard.php`: Main user dashboard
  - `make_reservation.php`: Handles lab reservations

- `includes/`: Common files
  - `database.php`: Database connection
  - `header.php`: Common header
  - `footer.php`: Common footer

- `uploads/`: Uploaded files
  - `resources/`: Lab resources

## Export Options

The Sit-in Data page supports the following export formats:
- PDF: Export data as a PDF document
- Excel: Export data as an Excel spreadsheet with lab and purpose filters
- CSV: Export data as a CSV file
- Print: Print the data table

## Lab Resources

Supported resource types:
- PDF documents
- External links
- Text documents

Maximum file size: 5MB

## Leaderboard Points

Points are calculated based on:
- Lab usage: 3 points per sit-in session
- Admin points: Additional points awarded by administrators

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx)
- Modern web browser with JavaScript enabled 