# User Container

This container handles user management functionality including user profiles, preferences, and user-related operations.

## Architecture Overview

The User container follows a clean architecture pattern with dedicated controllers, actions, and routes for user management features:

### Controllers Structure
Controllers are organized in the `UI/API/Controllers/` directory, each handling specific user functionality with comprehensive OpenAPI documentation.

### Routes Structure
Routes are organized by feature in versioned files within `UI/API/Routes/`:
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
    $this->authorize('permission', $user);
    $result = $action->run($request);
    Applog::info('User action completed', ['user_id' => $user->id, 'action' => 'action_name']);
    return Response::ok($result);
} catch (AuthorizationException $e) {
    return Response::forbidden($e->getMessage());
} catch (DBCallException $e) {
    report($e);
    Applog::error('Database error in user action', ['error' => $e->getMessage()]);
    return Response::serverError($e->getMessage());
} catch (Exception $e) {
    report($e);
    Applog::error('User action failed', ['error' => $e->getMessage()]);
    return Response::serverError($e->getMessage());
}
```

### Response Standards
- Use `Response` facade for consistent API responses
- Include proper HTTP status codes
- Log exceptions using `report()` function
- Include `Applog` function calls for user action audit trails
- Implement policy checks for user resource authorization
- Use UserTransformer for consistent user data output

### Route Organization
- One route file per feature/controller
- Versioned route files (`.v1.private.php`)
- Descriptive route names following Laravel conventions
- Appropriate middleware application for user authentication

## Core User Features

### User Profile Management
- User profile retrieval and updates
- Profile validation and data transformation
- User preference management
- Avatar and profile image handling
- Email address updates
- User role assignment and updates (requires `sync-roles` permission, added to super admins only)

### User Data Transfer Objects (DTOs)
- `UserDTO` for consistent data handling
- Validation and transformation of user data
- Type safety for user-related operations

### User Transformers
- `UserTransformer` for API response formatting
- Consistent user data representation
- Include/exclude sensitive information based on context

### User Policies
- Authorization for user resource access
- Profile update permissions
- User data visibility controls
- Admin vs user access differentiation
- Role update permissions (`sync-roles` required for role changes, added to super admins only)

## User Model Features

### Core Attributes
- Standard user attributes (name, email, timestamps)
- Email verification status
- Role assignments
- User preferences and settings

### Relationships
- Role associations
- Permission mappings
- User-specific data relationships

### Security Features
- Password hashing and validation
- Email verification implementation
- Account status management
- Security audit logging
