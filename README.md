# 🗓️ Content Scheduler

A simplified content scheduling application that allows users to create, manage, and schedule posts across multiple social media platforms.

## 🚀 Features

- User authentication (Login/Register) using **Laravel Sanctum**
- Post creation with title, content, image, and platform selection
- Schedule posts for future publication
- View posts in calendar and list view with status filters
- Platform management (Twitter, Instagram, LinkedIn, etc.)
- Rate limiting: Max 10 scheduled posts per day
- Background job to process and publish scheduled posts
- Basic analytics dashboard: Post success rate, platform breakdown
- Activity log: Tracks user actions
- Validation based on platform (e.g., character limits)
- Extensible design for adding new platforms or publishing logic

---

## 🧱 Tech Stack

- **Backend**: Laravel, PHP
- **Frontend**: JavaScript, React
- **Database**: MySQL
- **Authentication**: Laravel Sanctum

---

## 📦 Database Models

### Users

| Field     | Type       |
|-----------|------------|
| id        | integer    |
| name      | string     |
| email     | string     |
| password  | hashed     |

### Posts

| Field           | Type       |
|-----------------|------------|
| id              | integer    |
| title           | string     |
| content         | text       |
| image_url       | string     |
| scheduled_time  | datetime   |
| status          | enum (draft, scheduled, published) |
| user_id         | foreign key (users) |

### Platforms

| Field     | Type     |
|-----------|----------|
| id        | integer  |
| name      | string   |
| type      | enum (twitter, instagram, linkedin, etc.) |
| statue    | enum (active, inactive) |

### PostPlatform (pivot table)

| Field           | Type     |
|-----------------|----------|
| post_id         | foreign key (posts) |
| platform_id     | foreign key (platforms) |

### Activity_Log

| Field           | Type     |
|-----------------|----------|
| id              | integer |
| user_id         | foreign key (users) |
| action          | varchar(255) |
| entity_type     | varchar(255) |
| entity_id       | integer |
| description     | varchar(255) |

---

## 🛠️ Installation

```bash
git clone https://github.com/your-username/content-scheduler.git
cd content-scheduler
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm start
php artisan serve
