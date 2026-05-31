# Distributed System Architecture - Online Exam System

This project is built using a **Distributed Client-Server Architecture**. This means the application logic and data (the server) are centralized on one machine, while multiple users (the clients) can access and interact with it simultaneously from different locations.

## Key Distributed Concepts Applied

### 1. Network Transparency
By using the dynamic `config.php` system, the application no longer thinks it lives only on `localhost`. 
- **Auto-Discovery**: The `BASE_URL` is automatically detected based on the IP address or hostname used to access the site.
- **Resource Linking**: Assets (CSS, JS) and internal redirects use this dynamic base to ensure they load correctly on remote client PCs.

### 2. Statelessness (Mostly)
The system uses **PHP Sessions** for authentication. In a fully distributed enterprise system (like a cluster of servers), we would move these sessions to a central **Redis** or **Memcached** store. 
- Currently, this system supports **Vertical Scaling** (handling many users on one powerful server).
- For **Horizontal Scaling** (multiple servers), a Load Balancer would be placed in front.

### 3. Data Consistency
The MySQL database acts as the **Single Source of Truth**.
- **Concurrency**: When multiple students take an exam at the same time, the database manages locks to ensure scores and results are saved accurately without overlapping.

### 4. Node Visibility
The dashboard footer provides real-time information about the current **Server Node**:
- **Hostname**: The name of the server PC.
- **Network ID**: The IP address clients use to connect.
- **Session Fingerprint**: Helps identify the unique connection to the server.

## Future Distributed Roadmap
1. **API Layer**: Moving to a RESTful API would allow different client types (Mobile Apps, Desktop Apps) to consume the same data.
2. **Database Reclustering**: Using Master-Slave replication to handle millions of students.
3. **CDN Integration**: Serving static assets (images, styles) from edge locations to reduce latency for global users.
