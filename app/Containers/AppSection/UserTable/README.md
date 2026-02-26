# UserTable Container

## Overview

The UserTable Container provides a user preference management system for storing and managing visible column preferences for different tables. Each user can customize which columns they want to see for specific tables (e.g., jobs, products) in their interface.

## Key Features

- **User-Specific Preferences**: Each user can have their own column preferences per table
- **Create or Update**: Single endpoint handles both creation and updates (upsert behavior)
- **Table-Based Organization**: Preferences are organized by table name
- **Access Control**: All authenticated users can manage their own preferences
- **Unique Constraint**: One preference record per user per table (enforced at database level)

## Data Model

### UserTable Fields

| Field | Type | Description | Validation |
|-------|------|-------------|------------|
| id | integer | Unique identifier | Auto-generated |
| user_id | uuid | Foreign key to users table | Required, references users.id |
| table_name | string | Name of the table (e.g., 'jobs', 'products') | Required, max 255 chars |
| columns | json | Array of visible column names | Required, array of strings |
| created_at | timestamp | Creation timestamp | Auto-generated |
| updated_at | timestamp | Last update timestamp | Auto-updated |

### Database Constraints

- Unique constraint on `[user_id, table_name]` - ensures one preference per user per table
- Foreign key constraint on `user_id` with cascade delete

## API Endpoints

All endpoints are prefixed with `/api/v1/users/me/preferences/table-lists/` and require authentication.

### 1. Get User Table Preferences

```
GET /api/v1/users/me/preferences/table-lists/{table_name}
```

**Permission Required**: `list-usertables`

**Path Parameters**:
- `table_name`: The name of the table (required, string)

**Response**: Object containing visible columns array

**Example Request**:
```bash
GET /api/v1/users/me/preferences/table-lists/jobs
Authorization: Bearer {token}
```

**Example Response** (200 OK):
```json
{
  "data": {
    "visible_columns": ["job_number", "customer", "status"]
  }
}
```

**Error Responses**:
- `404 Not Found`: Table preferences not found for the specified table
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User doesn't have permission

---

### 2. Create or Update User Table Preferences

```
PUT /api/v1/users/me/preferences/table-lists/{table_name}
```

**Permission Required**: `create-or-update-usertable`

**Path Parameters**:
- `table_name`: The name of the table (required, string)

**Request Body**:
- `visible_columns`: Array of column names (required, array of strings)

**Response**: Object containing the created or updated visible columns

**Example Request**:
```bash
PUT /api/v1/users/me/preferences/table-lists/jobs
Authorization: Bearer {token}
Content-Type: application/json

{
  "visible_columns": ["job_number", "status", "engineer"]
}
```

**Example Response** (200 OK):
```json
{
  "data": {
    "visible_columns": ["job_number", "status", "engineer"]
  }
}
```

**Behavior**:
- If preferences exist for the user and table_name: Updates the columns
- If preferences don't exist: Creates new preferences
- The `user_id` is automatically set from the authenticated user

**Validation Rules**:
- `visible_columns`: required|array
- `visible_columns.*`: string

**Error Responses**:
- `422 Unprocessable Entity`: Validation error
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User doesn't have permission

---

### 3. Delete User Table Preferences

```
DELETE /api/v1/users/me/preferences/table-lists/{table_name}
```

**Permission Required**: `delete-usertable`

**Path Parameters**:
- `table_name`: The name of the table (required, string)

**Response**: 204 No Content

**Example Request**:
```bash
DELETE /api/v1/users/me/preferences/table-lists/jobs
Authorization: Bearer {token}
```

**Example Response** (204 No Content):
```
(empty response body)
```

**Error Responses**:
- `404 Not Found`: Table preferences not found for the specified table
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User doesn't have permission

---

## Permissions

The UserTable container uses three permissions, all available to all user types:

| Permission | Display Name | Description | Default Roles |
|------------|--------------|-------------|---------------|
| `list-usertables` | List usertables | List usertables by table name and logged in user | super-admin, admin, user |
| `create-or-update-usertable` | Create or update usertable | Create or update usertable for logged in user by table name | super-admin, admin, user |
| `delete-usertable` | Delete usertable | Delete usertable by table name and logged in user | super-admin, admin, user |

All permissions are available to all authenticated users, allowing them to manage their own table preferences.

---

## Usage Examples

### Creating Table Preferences

```php
use App\Containers\AppSection\UserTable\Actions\CreateOrUpdateUserTableAction;
use App\Containers\AppSection\UserTable\Data\DTOs\UserTableDTO;

$userTableDTO = UserTableDTO::fromArray([
    'user_id' => Auth::user()->id,
    'table_name' => 'jobs',
    'columns' => ['job_number', 'customer', 'status'],
]);

$userTable = app(CreateOrUpdateUserTableAction::class)->run($userTableDTO);
```

### Listing Table Preferences

```php
use App\Containers\AppSection\UserTable\Actions\ListUserTablesAction;

$action = app(ListUserTablesAction::class);
$userTables = $action->run('jobs');
```

### Deleting Table Preferences

```php
use App\Containers\AppSection\UserTable\Actions\DeleteUserTableAction;

$action = app(DeleteUserTableAction::class);
$action->run('jobs');
```

---

## Business Logic

### Create or Update Behavior

The `CreateOrUpdateUserTableAction` implements an upsert pattern:
1. Checks if a preference exists for the user and table_name
2. If exists: Updates the `columns` field
3. If not exists: Creates a new record with the provided data

This ensures users always have exactly one preference per table, and the same endpoint can be used for both initial creation and updates.

### User Isolation

All operations automatically filter by the authenticated user's ID:
- Users can only view, create, update, or delete their own preferences
- The `user_id` is automatically set from `Auth::user()` in actions
- Repository queries filter by user_id to ensure data isolation

---

## Database Migration

Run the migration to create the user_tables table:

```bash
php artisan migrate
```

The migration creates:
- `user_tables` table with id, user_id, table_name, columns, timestamps
- Unique constraint on `[user_id, table_name]`
- Foreign key constraint on `user_id` with cascade delete

---

## Testing

Unit and functional tests are available in the `Tests` directory:

- **Unit Tests**: Test individual actions and tasks
- **Functional Tests**: Test API endpoints with authentication

### Running Tests

```bash
# Run all UserTable tests
php artisan test --filter UserTable

# Run specific test file
php artisan test app/Containers/AppSection/UserTable/Tests/Unit/Actions/ListUserTablesActionTest.php

# Run functional tests only
php artisan test app/Containers/AppSection/UserTable/Tests/Functional

# Run unit tests only
php artisan test app/Containers/AppSection/UserTable/Tests/Unit
```

### Test Coverage

The UserTable container has comprehensive test coverage including:

- **Creation Tests**: Basic creation, empty columns, single column, many columns
- **Update Tests**: Updating existing preferences, create-or-update behavior
- **Delete Tests**: Successful deletion, authorization checks, non-existent preference handling
- **List Tests**: Empty list, filtering by table name, user isolation
- **Authorization Tests**: All endpoints tested with different user roles
- **Validation Tests**: Invalid data, missing fields, wrong types

---

## Container Structure

```
UserTable/
├── Actions/              # Business logic actions
│   ├── ListUserTablesAction.php
│   ├── CreateOrUpdateUserTableAction.php
│   └── DeleteUserTableAction.php
├── API/
│   ├── Controllers/
│   │   └── UserTableController.php
│   ├── Requests/        # Validation and authorization
│   │   ├── GetUserTableRequest.php
│   │   ├── CreateOrUpdateUserTableRequest.php
│   │   └── DeleteUserTableRequest.php
│   ├── Routes/
│   │   └── UserTableCRUD.v1.private.php
│   └── Transformers/
│       └── UserTableTransformer.php
├── Data/
│   ├── DTOs/
│   │   └── UserTableDTO.php
│   ├── Factories/
│   │   └── UserTableFactory.php
│   ├── Migrations/
│   │   └── 2026_01_05_132034_create_user_tables_table.php
│   └── Repositories/
│       └── UserTableRepository.php
├── Enums/
│   └── UserTableGate.php
├── Models/
│   └── UserTable.php
├── Policies/
│   └── UserTablePolicy.php
├── Providers/
│   └── UserTableServiceProvider.php
├── Tasks/
│   ├── FindUserTableByUserIdAndTableNameTask.php
│   ├── ListUserTablesTask.php
│   ├── CreateOrUpdateUserTableTask.php
│   └── DeleteUserTableTask.php
├── Tests/
│   ├── Functional/API/
│   │   ├── GetUserTableTest.php
│   │   ├── CreateOrUpdateUserTableTest.php
│   │   └── DeleteUserTableTest.php
│   └── Unit/
│       ├── Actions/
│       │   ├── ListUserTablesActionTest.php
│       │   ├── CreateOrUpdateUserTableActionTest.php
│       │   └── DeleteUserTableActionTest.php
│       └── Tasks/
│           ├── FindUserTableByUserIdAndTableNameTaskTest.php
│           ├── ListUserTablesTaskTest.php
│           ├── CreateOrUpdateUserTableTaskTest.php
│           └── DeleteUserTableTaskTest.php
├── composer.json
├── permissions.json
└── README.md
```

---

## Notes

- Preferences are user-specific and table-specific
- The unique constraint `[user_id, table_name]` ensures one preference per user per table
- All operations automatically use the authenticated user's ID
- The create-or-update endpoint provides a convenient upsert pattern
- Empty columns arrays are allowed (user wants to hide all columns)
- The container follows Apiato's standard structure and conventions
