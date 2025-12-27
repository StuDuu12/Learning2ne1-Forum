# 📚 DOCUMENTATION - Student Discussion Forum

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Database Schema](#database-schema)
3. [File Structure](#file-structure)
4. [Core Functions](#core-functions)
5. [Algorithms](#algorithms)
6. [Security Measures](#security-measures)
7. [API Reference](#api-reference)

---

## Architecture Overview

### Technology Stack

-   **Backend:** PHP 7.4+ (Procedural Programming)
-   **Database:** MySQL 8.0+ with PDO
-   **Frontend:** HTML5, CSS3, Vanilla JavaScript
-   **Charts:** Chart.js 3.x
-   **Server:** Apache/Nginx (via Laragon/XAMPP)

### Design Principles

1. **Procedural PHP Only** - No OOP/Classes used
2. **Security First** - PDO Prepared Statements, XSS Protection
3. **User Experience** - Responsive design, smooth interactions
4. **Performance** - Optimized queries with proper indexing
5. **Scalability** - Modular function design

---

## Database Schema

### Existing Table

```sql
user (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    ho_ten VARCHAR(1000),
    username VARCHAR(100) UNIQUE,
    password VARCHAR(100), -- MD5 hash
    account_level INT DEFAULT 2 -- 0=Admin, 1=Teacher, 2=Student
)
```

### New Tables

#### posts

Stores all discussion posts

```sql
posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT, -- FK to user.id_user
    title VARCHAR(255),
    content TEXT,
    tags VARCHAR(255), -- Comma-separated: "HTML,CSS,JS"
    privacy ENUM('public', 'private'),
    status ENUM('solved', 'unsolved'),
    created_at TIMESTAMP,
    views INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES user(id_user)
)
```

#### attachments

File uploads linked to posts

```sql
attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT, -- FK to posts.id
    file_path VARCHAR(500),
    file_type VARCHAR(50), -- MIME type
    created_at TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id)
)
```

#### comments

Threaded comments with parent-child relationship

```sql
comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    user_id INT,
    content TEXT,
    parent_id INT, -- NULL for root comments
    created_at TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id),
    FOREIGN KEY (user_id) REFERENCES user(id_user),
    FOREIGN KEY (parent_id) REFERENCES comments(id)
)
```

#### likes

Universal like system for posts and comments

```sql
likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    target_id INT, -- Post or Comment ID
    target_type ENUM('post', 'comment'),
    created_at TIMESTAMP,
    UNIQUE KEY (user_id, target_id, target_type),
    FOREIGN KEY (user_id) REFERENCES user(id_user)
)
```

#### polls & poll_options & poll_votes

Survey system

```sql
polls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    question VARCHAR(500),
    created_at TIMESTAMP
)

poll_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    poll_id INT,
    option_text VARCHAR(255)
)

poll_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    option_id INT,
    user_id INT,
    created_at TIMESTAMP,
    UNIQUE KEY (option_id, user_id)
)
```

#### user_interests

Tracks user engagement with tags for recommendations

```sql
user_interests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    tag VARCHAR(50),
    score INT DEFAULT 0, -- Increments on view
    updated_at TIMESTAMP,
    UNIQUE KEY (user_id, tag)
)
```

---

## File Structure

### Core Files

-   **config.php** - Database connection, session management, constants
-   **functions.php** - All helper functions (procedural)
-   **style.css** - Global styling with CSS variables

### Pages

-   **index.php** - Homepage with feed (Trending + Recommendations)
-   **login.php** - Login/Register form
-   **logout.php** - Session destruction
-   **create_post.php** - Post creation with files & polls
-   **post.php** - Post detail with interactions
-   **dashboard.php** - Statistics dashboard with Chart.js
-   **profile.php** - User profile page
-   **navbar.php** - Navigation component

### Utilities

-   **ajax.php** - AJAX handler for async operations
-   **start.html** - Setup guide
-   **README.md** - Project documentation
-   **sample_data.sql** - Demo data

---

## Core Functions

### Authentication Functions

#### `is_logged_in()`

```php
function is_logged_in() {
    return isset($_SESSION['user_id']);
}
```

Returns `true` if user is logged in.

#### `get_current_user($pdo)`

```php
function get_current_user($pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
```

Returns current user's data or `null`.

#### `check_permission($required_level)`

```php
function check_permission($required_level) {
    if (!is_logged_in()) return false;
    return $_SESSION['account_level'] <= $required_level;
}
```

Checks if user has required permission level.

### Post Functions

#### `get_post_by_id($pdo, $post_id)`

Retrieves post with user information.

#### `get_trending_posts($pdo, $limit = 5)`

Returns top posts by likes in last 7 days.

#### `get_recommended_posts($pdo, $user_id = null, $limit = 10)`

Returns personalized recommendations based on user interests.

### Interaction Functions

#### `get_like_count($pdo, $target_id, $target_type)`

Returns total likes for a post or comment.

#### `has_user_liked($pdo, $user_id, $target_id, $target_type)`

Checks if user has liked a specific item.

#### `track_user_interest($pdo, $user_id, $tags_string)`

Increments interest scores for tags when user views a post.

### Utility Functions

#### `safe_output($string)`

```php
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

Sanitizes output to prevent XSS attacks.

#### `format_mentions($content)`

```php
function format_mentions($content) {
    return preg_replace('/@(\w+)/', '<a href="profile.php?username=$1">@$1</a>', $content);
}
```

Converts `@username` to clickable links.

#### `time_ago($timestamp)`

Converts timestamp to human-readable format (e.g., "2 hours ago").

---

## Algorithms

### 1. Trending Algorithm

**Purpose:** Identify popular posts based on recent engagement.

**Logic:**

```php
function get_trending_posts($pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.ho_ten, u.username,
               COUNT(l.id) as like_count
        FROM posts p
        JOIN user u ON p.user_id = u.id_user
        LEFT JOIN likes l ON l.target_id = p.id AND l.target_type = 'post'
        WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND p.privacy = 'public'
        GROUP BY p.id
        ORDER BY like_count DESC, p.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
```

**Features:**

-   Only considers last 7 days
-   Ranks by like count
-   Falls back to creation date if equal likes
-   Only public posts

### 2. Recommendation Algorithm

**Purpose:** Suggest relevant posts based on user behavior.

**Logic:**

```php
function get_recommended_posts($pdo, $user_id = null, $limit = 10) {
    if ($user_id) {
        // Get user's top interest
        $stmt = $pdo->prepare("
            SELECT tag FROM user_interests
            WHERE user_id = ?
            ORDER BY score DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $top_interest = $stmt->fetchColumn();

        if ($top_interest) {
            // Find posts with matching tags
            $stmt = $pdo->prepare("
                SELECT p.*, u.ho_ten, u.username
                FROM posts p
                JOIN user u ON p.user_id = u.id_user
                WHERE p.privacy = 'public'
                      AND p.tags LIKE ?
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute(["%$top_interest%", $limit]);
            return $stmt->fetchAll();
        }
    }

    // Fallback: Latest public posts
    // ...
}
```

**Features:**

-   Uses user_interests table
-   Finds top-scored tag
-   Matches posts with that tag
-   Fallback to latest posts for new users

### 3. Interest Tracking

**Purpose:** Learn user preferences automatically.

**Logic:**

```php
function track_user_interest($pdo, $user_id, $tags_string) {
    $tags = explode(',', $tags_string);
    foreach ($tags as $tag) {
        $tag = trim($tag);
        $stmt = $pdo->prepare("
            INSERT INTO user_interests (user_id, tag, score)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE score = score + 1
        ");
        $stmt->execute([$user_id, $tag]);
    }
}
```

**Triggers:**

-   When user views a post
-   Increments score for each tag
-   Used by recommendation algorithm

---

## Security Measures

### 1. SQL Injection Prevention

✅ **PDO Prepared Statements** used everywhere

```php
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
```

### 2. XSS Prevention

✅ **htmlspecialchars()** on all output

```php
echo safe_output($user_input);
```

### 3. CSRF Protection

✅ Session-based authentication
✅ No GET requests for state changes

### 4. Access Control

✅ Permission checks before sensitive operations

```php
if ($_SESSION['user_id'] != $post['user_id']) {
    die('Unauthorized');
}
```

### 5. File Upload Security

✅ MIME type validation
✅ File size limits (5MB)
✅ Unique filenames

```php
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
if (!in_array($file['type'], $allowed_types)) {
    // Reject
}
```

---

## API Reference

### AJAX Endpoints (ajax.php)

#### Toggle Like

```javascript
POST ajax.php
{
    action: 'toggle_like',
    target_id: 123,
    target_type: 'post'
}

Response:
{
    success: true,
    liked: true,
    count: 5
}
```

#### Vote Poll

```javascript
POST ajax.php
{
    action: 'vote_poll',
    option_id: 45
}

Response:
{
    success: true,
    message: 'Đã ghi nhận phiếu bầu'
}
```

#### Add Comment

```javascript
POST ajax.php
{
    action: 'add_comment',
    post_id: 123,
    content: 'Great post!',
    parent_id: null // or comment_id for reply
}

Response:
{
    success: true,
    message: 'Đã thêm bình luận',
    comment: { /* comment data */ }
}
```

---

## Best Practices

### Code Style

1. **Function Naming:** Use snake_case (e.g., `get_user_posts()`)
2. **Variables:** Descriptive names (`$user_id`, not `$uid`)
3. **Comments:** Explain complex logic
4. **Indentation:** 4 spaces

### Database Queries

1. Always use prepared statements
2. Index frequently queried columns
3. Use JOINs efficiently
4. Limit results when possible

### Error Handling

1. Try-catch for transactions
2. User-friendly error messages
3. Log errors for debugging

### Performance

1. Minimize database queries
2. Use caching where appropriate
3. Optimize image sizes
4. Lazy load when possible

---

## Future Enhancements

### Phase 2

-   [ ] Real-time notifications (WebSocket)
-   [ ] Search functionality (Elasticsearch)
-   [ ] User following system
-   [ ] Email notifications
-   [ ] Mobile responsive improvements

### Phase 3

-   [ ] REST API for mobile app
-   [ ] Advanced analytics
-   [ ] Moderator tools
-   [ ] Badge/Achievement system
-   [ ] Dark mode

---

## Troubleshooting

### Common Issues

**Problem:** "Connection failed"
**Solution:** Check config.php database credentials

**Problem:** "Call to undefined function"
**Solution:** Ensure functions.php is included

**Problem:** "Upload failed"
**Solution:** Check uploads/ folder permissions

**Problem:** "Session not working"
**Solution:** Verify session_start() is called

---

## Contact & Support

For issues or questions:

1. Check this documentation
2. Review sample_data.sql for examples
3. Test with demo accounts

**Made with ❤️ for Educational Purposes**

---

_Last Updated: December 2025_
