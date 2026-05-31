# Deployment Guide: Using the System on Multiple PCs

To use this Online Exam System across different computers on your network (like a real school or office setup), follow these steps:

## 1. Prepare the Server (Host PC)
The computer running XAMPP is the "Server".
1. **Find your IP Address**: 
   - Open Command Prompt and type `ipconfig`.
   - Look for "IPv4 Address" (e.g., `192.168.1.10`).
2. **Enable Apache & MySQL**: Open XAMPP Control Panel and Start both modules.
3. **Open Firewall**: 
   - Ensure Windows Firewall allows "Apache HTTP Server" through.
   - You may need to create an "Inbound Rule" for Port 80.

## 2. Connect from Client PCs
On any other computer connected to the same Wi-Fi/LAN:
1. Open a web browser.
2. In the address bar, type your Server's IP followed by the project folder:
   `http://192.168.1.10/oline exam/login.html`
3. The system will automatically detect the connection and everything will work seamlessly.

## 3. Real Use Case: Deployment to the Cloud
If you want to use this over the internet (Global Distribution):
1. **Domain Name**: Buy a domain (e.g., `www.your-exam-site.com`).
2. **Web Hosting**: Upload these files to a Linux server (using cPanel or similar).
3. **Database**: Import your `online_exam_system.sql` to the production MySQL server.
4. **Config**: Update `config.php` with your production database credentials.

---
*Note: This project is configured to handle multiple concurrent student connections automatically.*
