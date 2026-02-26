# Authentication Container

This container handles user authentication, email verification, and password reset functionality using Laravel Passport and Laravel's built-in authentication features.

## Architecture Overview

The Authentication container follows a clean architecture pattern with dedicated controllers, actions, and routes for each feature:

### Controllers Structure
- `EmailVerificationController.php` - Handles email verification functionality
- `PasswordResetController.php` - Manages password reset operations  
- `RegisterUserController.php` - Handles user registration (invokable controller)
- `RevokeTokenController.php` - Manages token revocation

### Routes Structure
Routes are organized by feature in versioned files:
- `EmailVerification.v1.private.php` - Email verification routes
- `PasswordReset.v1.private.php` - Password reset routes
- `RegisterUser.v1.private.php` - User registration route
- `RevokeToken.v1.private.php` - Token revocation routes
- `Passport.v1.private.php` - OAuth/Passport routes

### API Documentation
All controllers include comprehensive OpenAPI/Swagger attributes for automatic API documentation generation. Documentation attributes are applied at the controller level following project standards.

## Development Standards

### Error Handling Pattern
All controllers follow a consistent error handling pattern:

```php
try {
    // Action execution
    $result = $action->run($request);
    return Response::ok($result);
} catch (AuthorizationException $e) {
    return Response::forbidden($e->getMessage());
} catch (DBCallException $e) {
    report($e);
    return Response::serverError($e->getMessage());
} catch (Exception $e) {
    report($e);
    return Response::serverError($e->getMessage());
}
```

### Response Standards
- Use `Response` facade for consistent API responses
- Include proper HTTP status codes
- Log exceptions using `report()` function  
- Return meaningful error messages

### Route Organization
- One route file per feature/controller
- Versioned route files (`.v1.private.php`)
- Descriptive route names following Laravel conventions
- Appropriate middleware application

## Configuration

The authentication system is configured through `Configs/appSection-authentication.php`:

### Client Configuration
```php
'clients' => [
    'web' => [
        'id' => env('CLIENT_WEB_ID'),
        'secret' => env('CLIENT_WEB_SECRET'),
    ],
]
```
- **CLIENT_WEB_ID**: OAuth client ID for web application authentication
- **CLIENT_WEB_SECRET**: OAuth client secret for web application authentication

## Authentication Features

### User Registration

#### API Endpoint: `POST /register`
**Controller**: `RegisterUserController` (invokable)  
**Route File**: `RegisterUser.v1.private.php`

The registration flow is handled by `Actions/RegisterUserAction.php`:

1. **Registration Check**: Validates if user registration is enabled via `AUTH_REG_ENABLED` config
2. **User Creation**: Creates user with provided data using UserDTO
3. **Role Assignment**: Automatically assigns `Role::USER` to new users
4. **Email Verification**: Triggers Laravel's `Registered` event for email verification
5. **Error Handling**: Comprehensive exception handling with proper HTTP response codes

**Registration can be disabled** by setting `AUTH_REG_ENABLED=false` in your environment variables.

#### Request Format
```json
{
    "first_name": "John",
    "last_name": "Doe", 
    "email": "john.doe@example.com",
    "password": "secretpassword",
    "password_confirmation": "secretpassword"
}
```

#### Response Codes
- **200 OK**: User registered successfully (returns UserResource)
- **422 Validation Error**: Invalid data or duplicate email
- **500 Internal Server Error**: System errors

### OAuth Token Authentication

#### API Endpoints (Laravel Passport)
**Route File**: `Passport.v1.private.php`

- **Issue Token** (`POST /oauth/token`): Exchange credentials for access token
- **Refresh Token** (`POST /oauth/token/refresh`): Refresh expired access token  
- **Revoke Token**: Handled by `RevokeTokenController`

### Token Configuration
```php
'tokens-expire-in' => env('API_TOKEN_EXPIRES', 1440),
'refresh-tokens-expire-in' => env('API_REFRESH_TOKEN_EXPIRES', 43200),
```
- **API_TOKEN_EXPIRES**: Access token expiration time in minutes (default: 1440 = 1 day)
- **API_REFRESH_TOKEN_EXPIRES**: Refresh token expiration time in minutes (default: 43200 = 30 days)

### Registration Settings
```php
'user_registration_enabled' => env('AUTH_REG_ENABLED', false),
```
- **AUTH_REG_ENABLED**: Controls whether users can self-register (default: false)

### URL Configuration
```php
'api-url' => env('API_URL', 'http://localhost:8000'),
'app-url' => env('APP_URL', 'http://localhost:8000'),
'github-url' => env('GITHUB_REPO_URL', 'https://github.com/Safety-Shield/system-template'),
```
- **API_URL**: Backend API URL for internal service communication
- **APP_URL**: Application base URL
- **GITHUB_REPO_URL**: Repository URL for documentation or support links

### Email Verification

#### API Endpoints
**Controller**: `EmailVerificationController`  
**Route File**: `EmailVerification.v1.private.php`

##### Send Verification Notification
- **Endpoint**: `POST /email/verification-notification`
- **Method**: `sendVerificationNotification()`
- **Middleware**: `auth:api`, `throttle:6,1`
- **Purpose**: Send email verification notification to authenticated user

##### Verify Email
- **Endpoint**: `POST /email/verify/{id}/{hash}`
- **Method**: `verifyEmail()`
- **Middleware**: `auth:api`, `signed`
- **Purpose**: Verify user email using signed verification link

#### Email Verification Process

1. **Send Verification** (`SendAction`):
   - Validates user authentication
   - Checks if email is already verified
   - Sends verification notification email
   - Includes proper error handling and response codes

2. **Generate Verification URL** (`GenerateUrlAction`):
   - Creates temporary signed route with 60-minute expiration
   - Uses user's hashed ID and email hash for security
   - Generates API endpoint with verification parameters

3. **Verify Email** (`VerifyAction`):
   - Validates signed URL parameters
   - Marks email as verified in database
   - Returns appropriate success/error responses

#### Response Codes
- **202 Accepted**: Verification notification sent
- **200 OK**: Email verified successfully
- **400 Bad Request**: Invalid verification link
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Email already verified
- **429 Too Many Requests**: Rate limit exceeded

### Password Reset

#### API Endpoints
**Controller**: `PasswordResetController`  
**Route File**: `PasswordReset.v1.private.php`

##### Forgot Password
- **Endpoint**: `POST /forgot-password`
- **Method**: `forgotPassword()`
- **Middleware**: `guest`
- **Purpose**: Send password reset link to user email

##### Reset Password  
- **Endpoint**: `POST /reset-password`
- **Method**: `resetPassword()`
- **Middleware**: `guest`
- **Purpose**: Reset user password using token from email

#### Password Reset Process

1. **Forgot Password Request** (`ForgotPasswordAction`):
   - Validates email address format and existence
   - Uses Laravel's `Password::sendResetLink()`
   - Handles throttling and validation errors
   - Generates reset token and sends email

2. **Reset URL Generation** (`GenerateUrlAction`):
   - Creates API endpoint with token and email parameters
   - Combines with frontend URL for user-friendly reset flow
   - Token expires based on configuration settings

3. **Reset Password** (`ResetPasswordAction`):
   - Validates reset token and email combination
   - Enforces password complexity requirements
   - Updates user password in database
   - Invalidates the reset token

#### Request Formats

**Forgot Password:**
```json
{
    "email": "user@example.com"
}
```

**Reset Password:**
```json
{
    "token": "abc123def456ghi789",
    "email": "user@example.com",
    "password": "NewPassword123!",
    "password_confirmation": "NewPassword123!"
}
```

#### Response Codes
- **202 Accepted**: Reset link sent successfully
- **200 OK**: Password reset successfully  
- **422 Validation Error**: Invalid data or expired token
- **500 Internal Server Error**: System errors

## Frontend URL Handling

The `Web.php` app class defines frontend URLs that must be handled by your frontend application:

### verifyEmailUrl()
```php
public function verifyEmailUrl(): string
{
    return $this->url() . '/email/verify';
}
```

**Frontend Route: `/email/verify`**
- **Purpose**: Handle email verification links
- **Query Parameter**: `verification_url` - Contains the API endpoint to verify the email
- **Implementation**: Frontend should extract the `verification_url` parameter and make an API call to complete verification
- **User Experience**: Show success/error message after verification attempt

### resetPasswordUrl()
```php
public function resetPasswordUrl(): string
{
    return $this->url() . '/reset-password';
}
```

**Frontend Route: `/reset-password`**
- **Purpose**: Handle password reset links
- **Query Parameter**: `reset_url` - Contains the API endpoint to reset the password
- **Implementation**: 
  1. Extract the `reset_url` parameter
  2. Display password reset form
  3. Submit new password to the extracted API endpoint
- **User Experience**: Password reset form with validation and success/error feedback

### Base URL Configuration
The base URL is configured in `config/apiato.php`:
```php
'apps' => [
    'web' => [
        'url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:3000')),
    ],
]
```
- **FRONTEND_URL**: Primary frontend application URL
- **APP_URL**: Fallback application URL
- **Default**: `http://localhost:3000` for development

### URL Flow Example

**Email Verification:**
1. API generates: `https://api.example.com/email/verify?expires=123&signature=abc&id=456&hash=def`
2. Frontend URL becomes: `https://frontend.example.com/email/verify?verification_url=https%3A//api.example.com/email/verify%3Fexpires%3D123%26signature%3Dabc%26id%3D456%26hash%3Ddef`
3. Frontend extracts and calls the verification URL

**Password Reset:**
1. API generates: `https://api.example.com/password/reset?token=xyz&email=user@example.com`
2. Frontend URL becomes: `https://frontend.example.com/reset-password?reset_url=https%3A//api.example.com/password/reset%3Ftoken%3Dxyz%26email%3Duser%40example.com`
3. Frontend extracts URL, shows form, and submits to reset endpoint
