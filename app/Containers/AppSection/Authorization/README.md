# Authorization Container

This container handles role-based access control (RBAC) and permissions management for the application.

## Architecture Overview

The Authorization container follows a clean architecture pattern with dedicated controllers, actions, and routes for authorization features:

### Controllers Structure
Controllers are organized in the `UI/API/Controllers/` directory, each handling specific authorization functionality with comprehensive OpenAPI documentation.

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
    $this->authorize('permission', $resource);
    $result = $action->run($request);
    Applog::info('Action completed', ['context' => $context]);
    return Response::ok($result);
} catch (AuthorizationException $e) {
    return Response::forbidden($e->getMessage());
} catch (Exception $e) {
    report($e);
    Applog::error('Action failed', ['error' => $e->getMessage()]);
    return Response::serverError($e->getMessage());
}
```

### Response Standards
- Use `Response` facade for consistent API responses
- Include proper HTTP status codes
- Log exceptions using `report()` function
- Include `Applog` function calls for audit trails
- Implement policy checks for authorization

### Route Organization
- One route file per feature/controller
- Versioned route files (`.v1.private.php`)
- Descriptive route names following Laravel conventions
- Appropriate middleware application for authorization

## Core Authorization Features

### Role Management
- **SUPER_ADMIN**: Complete system access and permission management
- **ADMIN**: Administrative access to application features
- **USER**: Standard user permissions for assigned resources

### Permission System
- Resource-based permissions
- Action-based access control
- Hierarchical role inheritance
- Dynamic permission assignment

### Policy Implementation
- Laravel policy classes for resource authorization
- Consistent authorization checks across controllers
- Centralized permission logic
