# Notification Container

This container handles notification management functionality including listing user notifications, marking notifications as read, and providing notification-related operations.

## Architecture Overview

The Notification container follows a clean architecture pattern with dedicated controllers, actions, and routes for notification management features:

### Controllers Structure
Controllers are organized in the `API/Controllers/` directory, each handling specific notification functionality with comprehensive OpenAPI documentation.

### Routes Structure
Routes are organized by feature in versioned files within `API/Routes/`:
- Versioned route files follow pattern `*.v1.private.php`
- Each feature has its own route file for better organization
- Routes use array syntax `[ControllerClass::class, 'method']`

### API Documentation
All controllers include comprehensive OpenAPI/Swagger attributes for automatic API documentation generation. Documentation attributes are applied at the controller level following project standards.

## Development Standards

### Error Handling Pattern
All controllers follow a consistent error handling pattern:

```php
try {
    // Action execution with policy checks
    $notifications = $action->run();
    return Response::create($notifications, NotificationTransformer::class)->ok();
} catch (AuthorizationException $e) {
    return Response::forbidden("You are not authorized to list notifications");
} catch (DBCallException $e) {
    report($e);
    return Response::serverError($e->getMessage());
} catch (Exception $e) {
    report($e);
    return Response::serverError();
}
```

### Response Standards
- Use `Response` facade for consistent API responses
- Include proper HTTP status codes
- Log exceptions using `report()` function
- Include `Applog` function calls for notification action audit trails
- Implement policy checks for notification resource authorization
- Use NotificationTransformer for consistent notification data output

### Route Organization
- One route file per feature/controller
- Versioned route files (`.v1.private.php`)
- Descriptive route names following Laravel conventions
- Appropriate middleware application for user authentication

## Core Notification Features

### Notification Management
- User notification listing with pagination
- Mark notifications as read functionality
- Notification data transformation
- Real-time notification handling

### Notification Types
The system supports different notification types:
- **Info Notifications**: General informational messages
- **Critical Notifications**: Important alerts and warnings

### Notification Actions
- `ShowNotificationsAction` for retrieving user notifications
- `UpdateNotificationsReadAtAction` for marking notifications as read

### Notification Transformers
- `NotificationTransformer` for API response formatting
- Consistent notification data representation
- Include notification metadata and timestamps

## Notification Model Features

### Core Attributes
Based on Laravel's notification database structure:
- `id`: UUID primary key
- `type`: Notification type classification
- `notifiable_type` & `notifiable_id`: Polymorphic relationship to user
- `data`: JSON payload containing notification content
- `read_at`: Timestamp when notification was marked as read
- `created_at` & `updated_at`: Standard Laravel timestamps

### Database Structure
The notifications table follows Laravel's standard notification schema:
- UUID-based primary keys for better distribution
- Polymorphic relationship support for different notifiable entities
- JSON data storage for flexible notification content
- Nullable read_at timestamp for read status tracking

### Notification Channels
- **Database Channel**: Stores notifications in the database
- **Mail Channel**: Sends email notifications for unread critical notifications
- **Queue Support**: All notifications implement ShouldQueue for background processing

## Available Notification Classes

### InfoNotification
Located in `App\Ship\Notifications\InfoNotification`
- Used for general informational messages
- Queued for background processing
- Returns 'info' as database type
- Stores title and message in data payload

### CriticalNotification
Located in `App\Ship\Notifications\CriticalNotification`
- Used for critical alerts and warnings
- Queued for background processing
- System re-sends the notification if it is not read after a certain amount of time
- Returns 'critical' as database type
- Stores title and message in data payload

### MailNotification
Located in `App\Ship\Notifications\MailNotification`
- Used for sending email notifications for unread critical notifications
- Automatically triggered by the mail resend system
- Uses Laravel's mail channel with customizable templates
- Includes personalized greeting and app branding

## API Endpoints

### GET /notifications
Lists paginated notifications for the authenticated user
- **Authentication**: Required (Bearer token)
- **Response**: Paginated notification collection
- **Ordering**: Latest notifications first

### POST /notifications
Marks specified notifications as read
- **Authentication**: Required (Bearer token)
- **Request Body**: Array of notification IDs
- **Response**: Success confirmation

## Usage Examples

### Sending a Notification
```php
use App\Ship\Notifications\InfoNotification;

$user = Auth::user();
$user->notify(new InfoNotification(
    title: 'Welcome!',
    message: 'Thank you for joining our platform.'
));
```

### Sending a Critical Notification
```php
use App\Ship\Notifications\CriticalNotification;

$user = Auth::user();
$user->notify(new CriticalNotification(
    title: 'Security Alert',
    message: 'Unusual login activity detected on your account.'
));
```

### Listing User Notifications
```php
use App\Containers\AppSection\Notification\Actions\ShowNotificationsAction;

$action = app(ShowNotificationsAction::class);
$notifications = $action->run();
```

### Marking Notifications as Read
```php
use App\Containers\AppSection\Notification\Actions\UpdateNotificationsReadAtAction;

$action = app(UpdateNotificationsReadAtAction::class);
$action->run(['notification-id-1', 'notification-id-2']);
```

### Sending Direct Mail Notification
```php
use App\Ship\Notifications\MailNotification;

$user = Auth::user();
$user->notify(new MailNotification(
    title: 'Account Security Alert',
    message: 'Your password was recently changed. If this wasn\'t you, please contact support immediately.'
));
```

## Automated Mail Notification System

### Critical Notification Mail Resend Feature
The system includes an automated mail notification feature for unread critical notifications:

#### How It Works
1. **Background Job**: `ResendUnreadCriticalNotifications` job runs periodically (recommended: hourly)
2. **Delay Configuration**: Configurable delay before sending mail notifications
3. **Targeted Sending**: Only sends emails for critical notifications that remain unread
4. **Active Users Only**: Only processes notifications for active users

#### Configuration
The mail delay is configured in `app/Ship/Configs/notifications.php`:
```php
return [
    'mail_delay' => env('MAIL_NOTIFICATIONS_DELAY', 3), // Hours
];
```

Set the `MAIL_NOTIFICATIONS_DELAY` environment variable to customize the delay (default: 3 hours).

#### Job Implementation
The `ResendUnreadCriticalNotifications` job:
- Queries all active users
- Finds unread critical notifications within the configured time window
- Sends `MailNotification` for each unread critical notification
- Uses precise time windows to avoid duplicate sends

#### Time Window Logic
```php
// Finds notifications created between (mail_delay + 1) and mail_delay hours ago
// Example: If mail_delay = 3 hours, finds notifications from 4-3 hours ago
$user->unreadNotifications()
    ->where('type', 'critical')
    ->whereBetween('created_at', [
        now()->subHours($mailDelay),
        now()->subHours($mailDelay + 1)
    ])
```

#### Mail Template
The `MailNotification` provides:
- Personalized subject line with app name
- Greeting with user's first name
- Original notification message
- App-branded footer

#### Scheduling
Added to the Laravel bootstrap app.php file, using the withSchedule function:
```php
$schedule->job(ResendUnreadCriticalNotifications::class)->hourly();
```

## Integration with Laravel Notifications

This container leverages Laravel's built-in notification system:
- Extends Laravel's `DatabaseNotification` model
- Uses Laravel's notification database migration
- Supports Laravel's notification channels (database, mail)
- Compatible with Laravel's queue system
- Follows Laravel's notification conventions

## Security Considerations

- All notification endpoints require authentication
- Users can only access their own notifications
- Notification content is stored as JSON for flexibility
- Background queue processing for performance
- Proper error handling and logging for debugging