# Laravel Backend Deployment Guide - Render.com

## Production Configuration Summary

### 🌐 Access Points
- **API Base URL**: https://communiti-server.onrender.com
- **Database Management**: 
  - Host: sql7.freesqldatabase.com
  - Database: sql7789809
  - User: sql7789809
  - Password: GUufRJwtjU
  - Port: 3306

## 📁 Updated Files for Production

### Environment Configuration
- `.env.production` - Production environment variables
- `.env.docker` - Updated with external database settings
- `docker-compose.render.yml` - Simplified for Render deployment

### Docker Configuration
- `Dockerfile` - Optimized for production
- `docker/start.sh` - Updated for external database with timeout
- `docker/apache/000-default.conf` - Updated ServerName for production domain

### Render Configuration
- `render.yaml` - Render-specific deployment configuration

## 🚀 Deployment Steps

### Option 1: Deploy to Render.com (Recommended)

1. **Connect Repository to Render**
   - Go to [Render Dashboard](https://dashboard.render.com)
   - Click "New" → "Web Service"
   - Connect your GitHub repository

2. **Configure Service Settings**
   - **Name**: `communiti-server`
   - **Environment**: `Docker`
   - **Region**: Choose closest to your users
   - **Branch**: `main` or `dev`
   - **Dockerfile Path**: `./Dockerfile`

3. **Set Environment Variables in Render Dashboard**
   ```
   APP_NAME=Laravel
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://communiti-server.onrender.com
   APP_KEY=[Generate in Render or use: php artisan key:generate --show]
   
   DB_CONNECTION=mysql
   DB_HOST=sql7.freesqldatabase.com
   DB_PORT=3306
   DB_DATABASE=sql7789809
   DB_USERNAME=sql7789809
   DB_PASSWORD=GUufRJwtjU
   
   CACHE_DRIVER=file
   SESSION_DRIVER=file
   QUEUE_CONNECTION=sync
   
   JWT_SECRET=[Generate a random 32-character string]
   ```

4. **Deploy**
   - Click "Create Web Service"
   - Render will automatically build and deploy your application

### Option 2: Local Docker Testing

1. **Test with Updated Configuration**
   ```bash
   # Build and run with external database
   docker-compose -f docker-compose.render.yml up --build -d
   
   # Check logs
   docker logs laravel-backend-production
   
   # Test API
   curl https://communiti-server.onrender.com/api/health
   ```

## 🔧 Important Notes

### Database Configuration
- **External MySQL**: Using sql7.freesqldatabase.com
- **No Local Database**: Removed MySQL and Redis containers
- **Connection Timeout**: Added 60-second timeout for database connections
- **File-based Caching**: Using file cache instead of Redis

### Security Considerations
- **APP_DEBUG=false**: Disabled for production
- **APP_KEY**: Must be generated and set securely
- **JWT_SECRET**: Required for authentication
- **HTTPS**: Render provides SSL automatically

### File Uploads
- **Storage**: Files are stored in `/storage/app/public`
- **Symbolic Link**: Created automatically via `php artisan storage:link`
- **Access URL**: `https://communiti-server.onrender.com/storage/filename`

## 🧪 Testing Endpoints

### Health Check
```bash
curl https://communiti-server.onrender.com/api/health
```

### File Upload Test (with PATCH method)
```bash
curl -X PATCH \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "profile_image=@path/to/image.jpg" \
  https://communiti-server.onrender.com/api/users/1
```

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Failed**
   - Check if external database is accessible
   - Verify credentials in environment variables

2. **File Upload Issues**
   - Use PATCH method instead of PUT
   - Check file permissions in storage directory

3. **JWT Authentication**
   - Ensure JWT_SECRET is set
   - Generate with: `openssl rand -base64 32`

### Logs
```bash
# View application logs in Render dashboard
# Or if running locally:
docker logs laravel-backend-production -f
```

## 📝 Environment Variables Quick Reference

```env
# Core Application
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://communiti-server.onrender.com
APP_KEY=[Generate with: php artisan key:generate --show]

# Database (External)
DB_CONNECTION=mysql
DB_HOST=sql7.freesqldatabase.com
DB_PORT=3306
DB_DATABASE=sql7789809
DB_USERNAME=sql7789809
DB_PASSWORD=GUufRJwtjU

# Cache & Session (File-based)
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# JWT Authentication
JWT_SECRET=[32-character random string]
```

## ✅ Deployment Checklist

- [ ] Repository connected to Render
- [ ] Environment variables configured
- [ ] Database credentials verified
- [ ] JWT_SECRET generated
- [ ] APP_KEY generated
- [ ] Storage permissions configured
- [ ] API endpoints tested
- [ ] File upload functionality verified (PATCH method)
