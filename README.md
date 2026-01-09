# Cloud control panel

A lightweight, self-hosted cloud management panel designed for simplicity and performance. Built with modern PHP and
containerized for easy deployment, it provides an intuitive interface for managing your personal cloud storage with
minimal resource overhead.

## ✨ Features

1. Minimal footprint - Low memory and CPU usage

2. Docker-first - Easy deployment with containerization
3. Modern stack - Built with PHP 8+ and clean architecture

4. File management - Upload, organize, and share files

5. Responsive UI - Pure CSS and vanilla JavaScript, no framework dependencies

## 🚀 Quick Start

### Prerequisites:

Docker and Docker Compose, Make utility

Configure Environment Variables

```bash

cp .env.example .env
nano .env
```

Build and Deploy

```bash

make build
make install 
make up
make migrate 
```

## 🌐 Access

Web Interface: http://localhost:8001 (or any configured port)

### ⚙️ Additional Commands

```bash

make bash 
# in docker environment 
composer analyse
composer test 
```

### 📄 Note: For production use, ensure to:

1. Change default credentials

2. Configure SSL/TLS

3. Set up regular backups

## License

This project is licensed under the MIT License - see the LICENSE file for details.